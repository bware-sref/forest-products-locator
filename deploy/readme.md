# Deployment!

## What we got here...
- deploy/
    - __deploy.conf__ - configs for the deployment script
    - __readme.md__ - that's us!
    - __bin/__
        - __deploy-from-github.sh__ - script to checkout from github, build the front-end, juggle files as needed and create symlinks, restart the job queue worker,  restart the SSR node server, and finally update the webroot
        - __github-update-check.sh__ - checks github for updates to the specified branch
    - __supervisor/__
        - __inertia-ssr.conf__ - config file for a supervisor task that manages the InertiaJS SSR server