# Deployment!

## What we got here...
- deploy/
    - __deploy.conf__ - configs for the deployment script
    - __readme.md__ - that's us!
    - __bin/__
        - __deploy-from-github.sh__ - script to checkout from github, build the release, then (in maintenance mode) run migrations, switch the webroot to the new release, and restart the job queue worker and SSR node server; rolls everything back if any step fails
        - __github-update-check.sh__ - checks github for updates to the specified branch
    - __supervisor/__
        - __inertia-ssr.conf__ - config file for a supervisor task that manages the InertiaJS SSR server
        - __laravel-worker.conf__ - config file for a supervisor task that manages the Laravel job queue worker

## How a deployment goes
1. Build the new release in `releases/<timestamp>` while the live site keeps serving the current release: clone, symlink the shared `.env` and `storage`, composer, `storage:link`, npm install and `build:ssr`, `optimize`.
2. Put the site in maintenance mode (`artisan down`). `storage` is shared, so this takes down the live release too. Wait `REQUEST_DRAIN_SECONDS` for in-progress requests to finish, then stop the queue worker (Supervisor lets it finish its current job first).
3. Run migrations, rebuild the Backpack Basset cache, and point the `current` symlink at the new release.
4. Start the queue worker and restart SSR on the new release, wait for SSR to come back, then `artisan up`.
5. Clean up failed releases and old releases beyond `RELEASES_TO_KEEP`.

The site returns 503s for the few seconds covered by steps 2–4, so no code ever runs against a database that doesn't match it. If the site was already in maintenance mode before deploying, it's left that way.

## If a deployment fails
The script undoes whatever it had done so far:
- points `current` back at the previous release
- rebuilds the Basset cache from the previous release
- gets the queue worker and SSR running on the previous release again
- rolls back any migrations the deployment applied (`migrate:rollback --batch=<n>`, using the failed release's `down()` methods)
- takes the site out of maintenance mode and deletes the failed release

If the migration rollback itself fails, the script stops there: the site is __left in maintenance mode__ and the failed release directory is kept, so you can fix the database by hand from that directory and then run `php artisan up`. The script prints the commands.

## Optional configs
These have defaults and can be added to `deploy.conf`:
- `FILE_USER` / `FILE_GROUP` - owner of the web-writable directories (default `ec2-user` / `apache`)
- `RELEASES_TO_KEEP` - successful releases to keep (default `3`)
- `REQUEST_DRAIN_SECONDS` - how long to wait after `artisan down` before migrating (default `5`)

## Supervisor notes
`laravel-worker.conf` sets `stopwaitsecs=70` so a stopped worker can finish its current job (default job timeout is 60 seconds) before Supervisor kills it. Keep it larger than any `--timeout` you give `queue:work`. After changing either Supervisor config on a server:

```
sudo cp <file>.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
```
