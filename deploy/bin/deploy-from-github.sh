#!/bin/bash
# Laravel deployment script

# Usage:
# Expected to be invoked from a directory containing a configuration file.
# The name of the configuration file is specified via the CONFIG_FILE variable.

# exit on any error or undefined variable
set -eu

# we update this when deployment complete so if an error occurs we can decide whether to rollback or not
DEPLOYMENT_COMPLETE=0

# crude error handler
handle_error() {
	echo "An error occurred on line ${1}: ${BASH_COMMAND} failed."
	if [[ "${DEPLOYMENT_COMPLETE}" -gt "0" ]]; then
		echo "Error occurred after successful deployment. No need to rollback."
		exit 0
	fi

	if [[ -n "$NEW_RELEASE_PATH" && -d "$NEW_RELEASE_PATH" ]]; then
		echo "Pretending to removing failed release directory '${NEW_RELEASE_PATH}'..."
		#rm -rf $NEW_RELEASE_PATH
		sudo rm -rf $NEW_RELEASE_PATH
	else
		echo "Nothing to clean up!"
	fi
	exit 1
}

# trap errors and send them to handle_error with the line number where the error occurred
trap 'handle_error $LINENO' ERR

STAGES_COMPLETE=0


# 
# required configs
# FILE_USER and FILE_GROUP are technically optional because they have defaults
# however, they are still required to be present and we should fail without them so...
REQUIRED_CONFIGS=("SITE_NAME" "BRANCH" "REPO_URL" "FILE_USER" "FILE_GROUP")

# look for 'deploy.conf' in the current directory
CONFIG_FILE="$(pwd)/deploy.conf"

# Ensure config file exists in the current directory.
if [[ ! -f "$CONFIG_FILE" ]]; then
	echo "Error: no configuration file found in current directory." >&2
	exit 1
fi

# slurp up the configs
source "$CONFIG_FILE"

# default values for FILE_USER and FILE_GROUP
# the ssh key for github is set up for ec2-user
FILE_USER=${FILE_USER:-"ec2-user"}
# nginx runs as apache on this system
FILE_GROUP=${FILE_GROUP:-"apache"}

# releases to keep also has a default!
RELEASES_TO_KEEP=${RELEASES_TO_KEEP:-3}

# loop over the required configs to make sure none are empty
for REQ_CON in "${REQUIRED_CONFIGS[@]}"; do
	# indirect expansion to extract the value!
	# "${!variable_name}" evaluates to the value stored in variable_name
	# CONFIG_VALUE="${!REQ_CON}"

	if [[ -z "${!REQ_CON}" ]]; then
		echo "Error: required config '$REQ_CON' is empty or unset. Exiting..." >&2
		exit 1
	#else
		# verify the settings for testing
		#echo "${REQ_CON}: '${!REQ_CON}'"
	fi
done

# set up additional variables to make our lives easier
# this script will be more portable if we dynamically create SITE_PATH from current working directory
# instead of hard-coding the path
#SITE_PATH="/var/www/${SITE_NAME}"
SITE_PATH="/srv/www/htdocs/${SITE_NAME}"
RELEASES_PATH="${SITE_PATH}/releases"
STORAGE_PATH="${SITE_PATH}/storage"
ENV_PATH="${SITE_PATH}/.env"
FILE_OWNER="${FILE_USER}:${FILE_GROUP}"
# releases named for timestamps in the format YYYYMMDDHHMMSS
NEW_RELEASE=$(date +"%Y%m%d%H%M%S")
NEW_RELEASE_PATH="${RELEASES_PATH}/${NEW_RELEASE}"
NEW_BS_CACHE="${NEW_RELEASE_PATH}/bootstrap/cache"
NEW_RELEASE_STORAGE="${NEW_RELEASE_PATH}/storage"

# verify all our variables have the values we expect
MY_STUFF=("SITE_PATH" "RELEASES_PATH" "STORAGE_PATH" "ENV_PATH" "FILE_OWNER" "NEW_RELEASE" "NEW_RELEASE_PATH" "NEW_BS_CACHE" "NEW_RELEASE_STORAGE")
#for MY_THING in "${MY_STUFF[@]}"; do
	# echo "${MY_THING}: ${!MY_THING}"
#done

echo "Begin deploying release '${NEW_RELEASE}' from branch '${BRANCH}' to ${SITE_NAME}"

# some folks recommend putting the site in maintenance mode before deploying...

echo "Creating new release directory '${NEW_RELEASE_PATH}'..."
mkdir -p $NEW_RELEASE_PATH

# checkout a shallow clone of the specified branch
echo "Cloning branch '${BRANCH}' from ${REPO_URL} into ${NEW_RELEASE_PATH}..."
git clone --depth 1 -b $BRANCH $REPO_URL $NEW_RELEASE_PATH

# enter new release directory
echo "Entering release directory '${NEW_RELEASE_PATH}'..."
cd $NEW_RELEASE_PATH

# symlink the shared .env file
echo "Creating symlink to shared .env file '${ENV_PATH}'..."
ln -nfs $ENV_PATH .env

# remove the storage directory we just checked out
echo "Removing local storage directory '${NEW_RELEASE_STORAGE}'..."
rm -rf $NEW_RELEASE_STORAGE

# create symlink to shared storage
echo "Creating symlink to shared storage directory '${STORAGE_PATH}'..."
ln -nfs $STORAGE_PATH storage

# update permissions and ownership of directories that webserver needs write access to
echo "Updating owner and permissions to allow webserver write access to certain directories..."
sudo chown -R $FILE_OWNER $NEW_BS_CACHE $STORAGE_PATH
# g+rwX: give the group full read/write, plus execute only where it's a
# directory (or already executable) -- heals anything that ended up
# group-inaccessible since the last deploy, without making plain files
# executable. g+s on directories makes new files/dirs created inside
# inherit group=apache automatically going forward, regardless of who
# creates them.
sudo chmod -R g+rwX $NEW_BS_CACHE $STORAGE_PATH
sudo find $NEW_BS_CACHE $STORAGE_PATH -type d -exec chmod g+s {} \;

# install composer packages
echo "Installing composer packages..."
/usr/bin/composer install --no-interaction --prefer-dist --optimize-autoloader

# link laravel storage
echo "Linking Laravel storage..."
/usr/bin/php artisan storage:link

# run DB migrations
echo "Running DB migrations..."
/usr/bin/php artisan migrate --force

# install NPM packages
echo "Installing Node packages..."
/usr/bin/npm install

# run npm audit fix to update package vulnerabilities
# this should really happen in dev because it updates package.json which is stored in repo
#echo "Running 'npm audit fix' to update vulnerable packages..."
#npm audit fix --force

# adjust node head size before building front-end to (hopefull) avoid OOM issues
# rec for max-old-space-size is <memory - 512MB>
# server only has 916Mi, so 916-512 = 404, which probably won't do.
# let's just use 750MB and see what happens
export NODE_OPTIONS=--max-old-space-size=750

# build front-end assets
echo "Building front-end assets..."
# needs to be npm run build:ssr
/usr/bin/npm run build:ssr

# rebuild config and view caches
echo "Dumping and rebuilding caches..."
/usr/bin/php artisan optimize

# This will cause the queue workers to exit when any running jobs are complete.
echo "Sending graceful exit signal to queue workers..."
/usr/bin/php artisan queue:restart
echo "Graceful exit signal sent."
# some things suggest doing both queue:restart and restarting the supervisorctl worker
echo "Restarting job queue..."
sudo supervisorctl restart laravel-worker
echo "Job queue restarted."

# Stop Inertia SSR.
# Supervisor will restart it.
echo "Stopping Inertia SSR..."
/usr/bin/php artisan inertia:stop-ssr
#sudo supervisorctl restart inertia-ssr
echo "Stopped Inertia SSR. Supervisor should restart it soon..."
# immediately running inertia:check-ssr causes an error because the command exits with an error when ssr is not running
/usr/bin/php artisan inertia:check-ssr
echo "Inertia SSR restarted."

# return to site directory
echo "Entering site directory '${SITE_PATH}'..."
cd $SITE_PATH

# update current symlink
echo "Updating current symlink to point to latest release '${NEW_RELEASE}'..."
ln -nfs $NEW_RELEASE_PATH current

# record our success to better know what to clean up
echo "$NEW_RELEASE" >> $RELEASES_PATH/.successes

# change owner of new release
#sudo chown -R $FILE_USER:$FILE_GROUP $NEW_RELEASE

# update our flag that indicates the important part is done
# this lets us avoid keeping track of the line number where deployment has finished
DEPLOYMENT_COMPLETE=1
echo "Deployment complete. Cleaning up old stuff..."

echo "Deleting failed releases..."
cd $RELEASES_PATH
# if there are release directories that do NOT appear in .successes, delete them
if grep -qvf .successes <(ls -1)
then
	# display the directories we are about to delete
	grep -vf .successes <(ls -1)
	#grep -vf .successes <(ls -1) | xargs rm -rf
	sudo grep -vf .successes <(ls -1) | xargs rm -rf
else
	echo "No failed releases found."
fi

echo "Deleting old successful releases..."
RELEASES_TO_KEEP=$((RELEASES_TO_KEEP-1))
NUM_LINES_TO_DELETE=$(find . -maxdepth 1 -mindepth 1 -type d ! -name "$NEW_RELEASE" -printf '%T@\t%f\n' | head -n -"$RELEASES_TO_KEEP" | wc -l)
if [ "$NUM_LINES_TO_DELETE" != 0 ]; then
	# get the names to delete
	find . -maxdepth 1 -mindepth 1 -type d ! -name "$NEW_RELEASE" -printf '%T@\t%f\n' | sort -t $'\t' -g | head -n -"$RELEASES_TO_KEEP" | cut -d $'\t' -f 2-

	# remove those names from .successes
	find . -maxdepth 1 -mindepth 1 -type d ! -name "$NEW_RELEASE" -printf '%T@\t%f\n' | sort -t $'\t' -g | head -n -"$RELEASES_TO_KEEP" | cut -d $'\t' -f 2- | xargs -I {} sed -i -e '/{}/d' .successes

	# delete the old releases
	# find . -maxdepth 1 -mindepth 1 -type d ! -name "$NEW_RELEASE" -printf '%T@\t%f\n' | sort -t $'\t' -g | head -n -"$RELEASES_TO_KEEP" | cut -d $'\t' -f 2- | xargs rm -rf
	# the following fails because sudo only applies to the first command, but it's the final 'xargs rm -rf' that needs to run with sudo
	# Gemini says just insert sudo between xargs and rm
	#sudo find . -maxdepth 1 -mindepth 1 -type d ! -name "$NEW_RELEASE" -printf '%T@\t%f\n' | sort -t $'\t' -g | head -n -"$RELEASES_TO_KEEP" | cut -d $'\t' -f 2- | xargs rm -rf
	sudo find . -maxdepth 1 -mindepth 1 -type d ! -name "$NEW_RELEASE" -printf '%T@\t%f\n' | sort -t $'\t' -g | head -n -"$RELEASES_TO_KEEP" | cut -d $'\t' -f 2- | xargs sudo rm -rf
else
	RELEASES_TO_KEEP=$((RELEASES_TO_KEEP+1))
	TOTAL_STORED_RELEASES=$(find . -maxdepth 1 -mindepth 1 -type d printf '%T@\t%f\n' | wc -l)
	printf 'There are only %s successful releases, which is less than or equal to %s, the defined number to retain.' "$TOTAL_STORED_RELEASES" "$RELEASES_TO_KEEP"
fi

echo "Stored releases:"
find . -maxdepth 1 -mindepth 1 -type d -printf '%T@\t%f\n' | sort -nr | cut -f 2-

echo "Finished deploying ${NEW_RELEASE}."
echo "If we'd been timing it, this is where we'd say how long it took."
