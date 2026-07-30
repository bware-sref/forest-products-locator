#!/bin/bash

# After checkout, move this file to /usr/local/bin or similar.

# I can't decide if I want to replace the specifics with placeholders.
# Seems prudent if we're committing this to the repo.

# SITE_NAME is the name of the root project directory on the server which 
# houses the "releases" directory and other persistent, shared resources such # as .env and storage
# E.g., /srv/www/htdocs/${SITE_NAME}
SITE_NAME=""

# SYMLINK_NAME is the name used for the symlink to the most recent release, e.g., "current"
SYMLINK_NAME="current"

# WWW_BASE_PATH is the main parent directory for websites hosted on this server.
# e.g., /srv/www/htdocs, /var/www/public_html, etc.
WWW_BASE_PATH="/srv/www/htdocs"

# SITE_PATH is parent of the symlink to the repo directory.
SITE_PATH="${WWW_BASE_PATH}/${SITE_NAME}"
# REPO_PATH is the local path to the checked out repo we're comparing against origin.
# The last portion of REPO_PATH is the symlink mentioned above.
REPO_PATH="${SITE_PATH}/${SYMLINK_NAME}"
# BRANCH is the branch in the origin repo we're comparing against.
# E.g., development, qa, main, et al.
BRANCH=""
# DEPLOY_SCRIPT is the script we execute if there are changes that need to be deployed.
# The default below assumes you copied the deploy script to /usr/local/bin.
DEPLOY_SCRIPT="/usr/local/bin/deploy-from-github.sh"

echo "github-update-check running as '$(whoami)'"
echo "changing to repo directory '${REPO_PATH}'..."

cd $REPO_PATH || exit 1

# do him fetch
git fetch origin $BRANCH

# compare local and remote hashes
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse @{u})

if [ $LOCAL != $REMOTE ]; then
	echo "Changes detected in remote branch '${BRANCH}'. Triggering deployment..."
	# deployment script needs to run in SITE_PATH!
	cd $SITE_PATH || exit 1
	$DEPLOY_SCRIPT
else
	echo "No changes."
fi
