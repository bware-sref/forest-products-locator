#!/bin/bash
# Laravel deployment script

# Usage:
# Expected to be invoked from a directory containing a configuration file.
# The name of the configuration file is specified via the CONFIG_FILE variable.

# exit on any error or undefined variable
set -eu

# we update this when deployment complete so if an error occurs we can decide whether to rollback or not
DEPLOYMENT_COMPLETE=0

# highest migration batch number recorded before this deployment ran migrations
# empty means we haven't reached the migration step, so there's nothing to roll back
PRE_MIGRATION_BATCH=""

# set once basset:fresh has overwritten the shared Basset cache with this release's assets
BASSET_REBUILT=0

# rollback state: set as the deployment puts the site into maintenance mode and stops the queue worker,
# and by the error handler once it has pointed 'current' back at the previous release
MAINTENANCE_ENABLED=0
WORKER_STOPPED=0
SYMLINK_RESTORED=0

# print the highest batch number in the migrations table (0 if there are no migrations)
get_migration_batch() {
	/usr/bin/php artisan tinker --no-ansi --execute='echo \Illuminate\Support\Facades\DB::table("migrations")->max("batch") ?? 0;'
}

# roll back any migrations applied by this deployment
# must run from the new release directory: the down() methods for new migrations only exist there
# returns non-zero if rollback was needed and failed
rollback_migrations() {
	if [[ -z "${PRE_MIGRATION_BATCH}" ]]; then
		echo "Migrations had not run yet. No database rollback needed."
		return 0
	fi

	cd "$NEW_RELEASE_PATH" || return 1

	local CURRENT_BATCH
	if ! CURRENT_BATCH=$(get_migration_batch) || [[ ! "$CURRENT_BATCH" =~ ^[0-9]+$ ]]; then
		echo "Error: unable to determine current migration batch ('${CURRENT_BATCH:-}')." >&2
		return 1
	fi

	if [[ "$CURRENT_BATCH" -le "$PRE_MIGRATION_BATCH" ]]; then
		echo "No migrations were applied during this deployment. No database rollback needed."
		return 0
	fi

	# 'migrate --force' puts every migration it runs into a single new batch,
	# so any batch above the one we recorded belongs to this deployment
	local BATCH
	for (( BATCH=CURRENT_BATCH; BATCH>PRE_MIGRATION_BATCH; BATCH-- )); do
		echo "Rolling back migration batch ${BATCH}..."
		if ! /usr/bin/php artisan migrate:rollback --batch="$BATCH" --force; then
			echo "Error: rollback of migration batch ${BATCH} failed." >&2
			return 1
		fi
	done
	echo "Database migrations rolled back."
}

# point the 'current' symlink back at the previous release if this deployment already switched it
restore_symlinks() {
	local CURRENT_LINK="${SITE_PATH:-}/current"

	if [[ -z "${SITE_PATH:-}" || ! -L "$CURRENT_LINK" || "$(readlink "$CURRENT_LINK")" != "${NEW_RELEASE_PATH:-}" ]]; then
		echo "'current' symlink was not switched to the failed release. No symlinks to restore."
		return 0
	fi

	if [[ -n "${PREVIOUS_RELEASE_PATH:-}" && -d "$PREVIOUS_RELEASE_PATH" ]]; then
		echo "Restoring 'current' symlink to previous release '${PREVIOUS_RELEASE_PATH}'..."
		ln -nfs "$PREVIOUS_RELEASE_PATH" "$CURRENT_LINK" || return 1
		SYMLINK_RESTORED=1
	else
		# first deployment: there's nothing to go back to, so don't leave 'current' pointing at a directory we're about to delete
		echo "No previous release to restore. Removing 'current' symlink..."
		rm -f "$CURRENT_LINK" || return 1
	fi
}

# get the queue worker and SSR running on the previous release again
# they run from 'current' under Supervisor, and a process's working directory is resolved when it starts,
# so anything started while 'current' pointed at the failed release has to be restarted
restore_services() {
	if [[ "${SYMLINK_RESTORED:-0}" -eq "0" && "${WORKER_STOPPED:-0}" -eq "0" ]]; then
		return 0
	fi

	if [[ -z "${PREVIOUS_RELEASE_PATH:-}" || ! -d "$PREVIOUS_RELEASE_PATH" ]]; then
		echo "No previous release to run the queue worker and SSR from."
		return 0
	fi

	# stop-then-start works whether the worker is currently running or was stopped by this deployment
	# (the 'stop' errors harmlessly if it's already stopped)
	echo "Restarting queue worker on the previous release..."
	sudo supervisorctl stop laravel-worker > /dev/null 2>&1
	sudo supervisorctl start laravel-worker || echo "Warning: failed to start queue worker. Run 'sudo supervisorctl start laravel-worker' manually." >&2

	if [[ "${SYMLINK_RESTORED:-0}" -gt "0" ]]; then
		# same approach as the deployment: stop SSR and let Supervisor restart it from 'current'
		echo "Restarting Inertia SSR on the previous release..."
		(cd "$PREVIOUS_RELEASE_PATH" && /usr/bin/php artisan inertia:stop-ssr) || echo "Warning: failed to stop Inertia SSR. Run 'sudo supervisorctl restart inertia-ssr' manually." >&2
	fi
}

# take the site out of maintenance mode if this deployment put it there
# must run before the failed release directory is removed, since that's the artisan we use
bring_site_up() {
	if [[ "${MAINTENANCE_ENABLED:-0}" -eq "0" ]]; then
		return 0
	fi

	echo "Taking site out of maintenance mode..."
	if (cd "$NEW_RELEASE_PATH" && /usr/bin/php artisan up); then
		MAINTENANCE_ENABLED=0
	else
		echo "Warning: failed to take site out of maintenance mode. Run 'php artisan up' from '${SITE_PATH}/current' manually." >&2
	fi
}

# rebuild the shared Basset cache from the previous release if this deployment already rebuilt it
# Basset stores cached vendor files at paths relative to the release root, so without this the
# previous release would keep serving the failed release's copies out of shared storage
restore_basset_cache() {
	if [[ "${BASSET_REBUILT:-0}" -eq "0" ]]; then
		return 0
	fi

	if [[ -z "${PREVIOUS_RELEASE_PATH:-}" || ! -d "$PREVIOUS_RELEASE_PATH" ]]; then
		echo "No previous release to rebuild the Basset cache from."
		return 0
	fi

	# basset:fresh shells out to 'php artisan', which resolves against the working directory, hence the cd
	echo "Rebuilding Backpack Basset cache for the previous release..."
	(cd "$PREVIOUS_RELEASE_PATH" && /usr/bin/php artisan basset:fresh) || echo "Warning: failed to rebuild Basset cache. Run 'php artisan basset:fresh' from '${PREVIOUS_RELEASE_PATH}' manually." >&2
}

# crude error handler
handle_error() {
	echo "An error occurred on line ${1}: ${BASH_COMMAND} failed."
	if [[ "${DEPLOYMENT_COMPLETE}" -gt "0" ]]; then
		echo "Error occurred after successful deployment. No need to rollback."
		exit 0
	fi

	# don't let set -e abort the handler partway through cleanup
	set +e

	# restore the previous release first so the site is serving known-good code as soon as possible
	if ! restore_symlinks; then
		echo "Error: failed to restore 'current' symlink. Point it at '${PREVIOUS_RELEASE_PATH:-<previous release>}' manually:" >&2
		echo "  ln -nfs ${PREVIOUS_RELEASE_PATH:-<previous release>} ${SITE_PATH:-<site path>}/current" >&2
	fi

	restore_basset_cache
	restore_services

	if ! rollback_migrations; then
		echo "Database rollback failed. Keeping failed release directory '${NEW_RELEASE_PATH}' so its migrations can be rolled back manually:" >&2
		echo "  cd ${NEW_RELEASE_PATH} && php artisan migrate:status && php artisan migrate:rollback --batch=<batch> --force" >&2
		echo "A migration that failed partway through may also have left partial schema changes (MySQL DDL is not transactional)." >&2
		if [[ "${MAINTENANCE_ENABLED:-0}" -gt "0" ]]; then
			# the database may not match the previous release's code, so a 503 is safer than serving it
			echo "Leaving the site in maintenance mode. Once the database is fixed, run 'php artisan up' from '${SITE_PATH}/current'." >&2
		fi
		exit 1
	fi

	bring_site_up

	if [[ -n "${NEW_RELEASE_PATH:-}" && -d "$NEW_RELEASE_PATH" ]]; then
		echo "Removing failed release directory '${NEW_RELEASE_PATH}'..."
		# cd out first: the handler may have been triggered while inside the release directory
		cd /
		sudo rm -rf "$NEW_RELEASE_PATH"
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

# how long to wait after entering maintenance mode for in-progress web requests to finish before migrating
REQUEST_DRAIN_SECONDS=${REQUEST_DRAIN_SECONDS:-5}

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

# remember what 'current' points to so we can restore it if this deployment fails
# empty on a first deployment, when there is no 'current' symlink yet
PREVIOUS_RELEASE_PATH=""
if [[ -L "${SITE_PATH}/current" ]]; then
	PREVIOUS_RELEASE_PATH=$(readlink "${SITE_PATH}/current")
fi
echo "Previous release: '${PREVIOUS_RELEASE_PATH:-none}'"

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

#
# Everything above only touches the new release directory, so the live site keeps serving the previous release.
# Everything from here to 'artisan up' changes shared state (database, Basset cache, 'current', running services),
# so it happens in maintenance mode. That keeps any code from running against a database that doesn't match it,
# for the few seconds between migrating and switching releases (and while rolling back, if anything fails).
#

# storage/ is shared across releases and the file maintenance driver writes storage/framework/down,
# so running 'down' from the new release also takes down the live (previous) release
# if someone already put the site in maintenance mode on purpose, leave it that way when we're done
if [[ -f "${STORAGE_PATH}/framework/down" ]]; then
	echo "Site is already in maintenance mode. It will be left in maintenance mode after deployment."
else
	echo "Putting site into maintenance mode..."
	# set before running it so the error handler still tries 'up' if 'down' fails partway
	MAINTENANCE_ENABLED=1
	/usr/bin/php artisan down
fi

# new requests now get a 503, but requests already in progress run to completion
# give them a moment to finish before we change the database out from under them
echo "Waiting ${REQUEST_DRAIN_SECONDS} seconds for in-progress requests to finish..."
sleep "$REQUEST_DRAIN_SECONDS"

# maintenance mode stops the worker from starting new jobs, but not one already running
# 'supervisorctl stop' sends TERM, which lets the worker finish its current job before exiting,
# and doesn't return until it has (or until stopwaitsecs in laravel-worker.conf runs out and Supervisor kills it)
echo "Stopping queue worker (waits for the current job to finish)..."
WORKER_STOPPED=1
sudo supervisorctl stop laravel-worker
echo "Queue worker stopped."

# record the current migration batch so we can roll back this deployment's migrations if a later step fails
echo "Recording current migration batch..."
# use a temp variable so PRE_MIGRATION_BATCH stays empty (no rollback attempted) unless we got a valid number
MIGRATION_BATCH_OUTPUT=$(get_migration_batch)
if [[ ! "$MIGRATION_BATCH_OUTPUT" =~ ^[0-9]+$ ]]; then
	echo "Error: unexpected migration batch value '${MIGRATION_BATCH_OUTPUT}'." >&2
	false
fi
PRE_MIGRATION_BATCH="$MIGRATION_BATCH_OUTPUT"
echo "Current migration batch: ${PRE_MIGRATION_BATCH}"

# run DB migrations
echo "Running DB migrations..."
/usr/bin/php artisan migrate --force

# storage/ is a shared symlink across every release (see above), and Backpack's Basset
# cache lives inside it -- without this, a new release's vendor/CSS changes would keep
# getting served from whatever a previous release last compiled.
echo "Rebuilding Backpack Basset cache..."
/usr/bin/php artisan basset:fresh
# the shared cache now holds this release's vendor assets; if we fail from here on, the previous release needs it rebuilt
BASSET_REBUILT=1

# update current symlink BEFORE starting the queue worker and restarting SSR:
# Supervisor runs them from 'current', and a process resolves its working directory when it starts,
# so starting them first would leave them running the previous release's code
# (we stay in the new release directory so the artisan commands below still work)
echo "Updating current symlink to point to latest release '${NEW_RELEASE}'..."
ln -nfs $NEW_RELEASE_PATH "${SITE_PATH}/current"

# start the worker on the new release; it won't pick up jobs until the site is back up
# (no 'queue:restart' needed: the worker was fully stopped, so no old worker is left to signal)
echo "Starting queue worker..."
sudo supervisorctl start laravel-worker
WORKER_STOPPED=0
echo "Queue worker started."

# Stop Inertia SSR.
# Supervisor will restart it.
echo "Stopping Inertia SSR..."
/usr/bin/php artisan inertia:stop-ssr
#sudo supervisorctl restart inertia-ssr
echo "Stopped Inertia SSR. Waiting for Supervisor to restart it..."
# immediately running inertia:check-ssr causes an error because the command exits with an error when ssr is not running
# but we can use 'until' to handle this because it suppresses errors on the command it executes!
SSR_TIMEOUT=30
SSR_ELAPSED=0
# until checks for exit code 0 (all good (or at least no bad))
until /usr/bin/php artisan inertia:check-ssr > /dev/null 2>&1; do
	if [ "$SSR_ELAPSED" -ge "$SSR_TIMEOUT" ]; then
		echo "Error: Inertia SSR failed to restart within ${SSR_TIMEOUT} seconds." >&2
		# a plain 'exit' bypasses the ERR trap, so call the handler directly to roll back and clean up
		handle_error $LINENO
	fi
	sleep 1
	SSR_ELAPSED=$((SSR_ELAPSED + 1))
done
echo "Inertia SSR restarted."

if [[ "$MAINTENANCE_ENABLED" -gt "0" ]]; then
	echo "Taking site out of maintenance mode..."
	/usr/bin/php artisan up
	MAINTENANCE_ENABLED=0
fi

# return to site directory
echo "Entering site directory '${SITE_PATH}'..."
cd $SITE_PATH

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
