# WhoGoHost push-to-deploy setup

This uses the same approach as Fxtbot: GitHub Actions builds the application and uploads it over **SFTP** on every push to `main`. Node.js and Composer are not needed on the hosting server. You can also run **Deploy to WhoGoHost** manually from GitHub's Actions tab, selecting `main`.

## 1. Confirm hosting access

Ask WhoGoHost support to confirm that your plan provides:

- SFTP access from GitHub-hosted runners, including hostname, port, username and password authentication. FTP/FTPS credentials are not interchangeable with SFTP.
- cPanel Terminal or SSH access for Laravel's setup and database migrations. [WhoGoHost's instructions](https://whogohostgh.whogohost.com/host/index.php?rp=%2Fknowledgebase%2F631%2FAccessing-Terminal-on-cPanel.html) say to contact support if Terminal is missing.
- PHP **8.4**, both for the website and command line, with Laravel's required extensions and `pdo_mysql` enabled.
- MySQL/MariaDB, cron jobs, HTTPS, writable `storage` and `bootstrap/cache`, and a domain document root pointing to the application's `public` directory.

Do not enable this workflow with plain FTP credentials. If your plan cannot provide SFTP, this deployment method needs changing first.

## 2. Prepare a dedicated directory and domain

The examples below use cPanel username `CPANEL_USER` and application directory `/home/CPANEL_USER/optimize`. Replace these with your real values. Do not use your account home directory or a directory containing other sites: deployment deletes obsolete application files.

In cPanel Terminal:

```sh
mkdir -p /home/CPANEL_USER/optimize
touch /home/CPANEL_USER/optimize/.deploy-target
```

The marker must exist in the SFTP destination or deployment stops before uploading. Set the domain's document root to `/home/CPANEL_USER/optimize/public` using cPanel Domains or ask support to do it. Only `public` should be web-accessible; do not expose the Laravel project root or rely on its root `.htaccess` rewrite as a security boundary. If your main domain is fixed to `public_html`, ask support for a supported mapping to this `public` directory before proceeding.

Create a database and database user in cPanel, grant that user privileges on that database, and save the exact names (including cPanel prefixes). Enable HTTPS for your domain.

## 3. Add GitHub production secrets

In repository **Settings → Environments**, create `production`. Restrict deployment branches to `main`. For fully automatic deployment, do not require manual reviewer approval. Add these **environment secrets**:

| Secret | Value |
| --- | --- |
| `SFTP_HOST` | Hosting server hostname, without `sftp://` or a path |
| `SFTP_PORT` | Port supplied by WhoGoHost, often `22` |
| `SFTP_USERNAME` | SFTP-enabled hosting username |
| `SFTP_PASSWORD` | That account's SFTP password |
| `SFTP_REMOTE_PATH` | Dedicated application directory as seen by SFTP, e.g. `/home/CPANEL_USER/optimize` |
| `SFTP_KNOWN_HOSTS` | Verified OpenSSH `known_hosts` entry/entries for this server and port |

Paths must be absolute, without spaces or `.`/`..` segments. A jailed SFTP account may see a different path than cPanel Terminal: confirm it with your SFTP client and ensure `.deploy-target` is visible there.

On your Mac, collect the server's public host keys:

```sh
ssh-keyscan -p YOUR_PORT YOUR_HOST > whogohost-known-hosts
ssh-keygen -lf whogohost-known-hosts
```

**Verify the fingerprints with WhoGoHost through a trusted channel before using them.** Scanning alone does not establish trust. Paste the verified file contents into `SFTP_KNOWN_HOSTS`; retain the `[hostname]:port` format for nonstandard ports. Never paste passwords or your production `.env` into chat or commit them.

## 4. Upload and initialize Laravel

Commit and push the workflow to `main` only after the secrets and target directory are ready. In GitHub **Actions → Deploy to WhoGoHost**, check that the upload succeeds. The site will not be ready until the following initialization is complete.

Using cPanel File Manager, create `.env` inside the application directory by copying the contents of this repository's `.env.example` (environment files are deliberately not uploaded). Configure at least:

```dotenv
APP_NAME="NP Digital"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_DOMAIN

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=YOUR_CPANEL_DATABASE
DB_USERNAME=YOUR_CPANEL_DATABASE_USER
DB_PASSWORD="YOUR_DATABASE_PASSWORD"

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

Also configure your real SMTP settings (`MAIL_MAILER=smtp`, host, port, credentials and sender address). Keep `.env` readable only by your hosting user/PHP process (normally mode `600`). Use the production website's HTTPS URL, not the example domain above.

In cPanel Terminal, confirm `php -v` reports PHP 8.4. If it does not, ask support for the PHP 8.4 CLI binary and substitute its full path for `php` in all commands and cron entries below.

```sh
cd /home/CPANEL_USER/optimize
php artisan key:generate --no-interaction
php artisan migrate --force --no-interaction
php artisan db:seed --class=MembershipLevelSeeder --force --no-interaction
php artisan storage:link --no-interaction
php artisan optimize:clear --no-interaction
```

Generate `APP_KEY` **once** for a new installation. When moving an existing site, import its database, copy its existing `.env`/`APP_KEY` and uploads instead; do not regenerate its key. Back up the database before migrations. Do not run `migrate:fresh` or the default `DatabaseSeeder` in production (it creates a test user). Membership seeding creates the tiers used by this application; admin accounts and warehouse product records still need your normal application setup/import process. The workflow uploads bundled warehouse images, not database records.

Ensure PHP can write to `storage` and `bootstrap/cache` using cPanel's ownership/permissions tools; do not use `777`. Visit the homepage and `/up`, then check login and image loading. `/up` confirms Laravel boots, not that every database or mail operation works.

## 5. Configure scheduled work

In **cPanel → Cron Jobs**, run the scheduler every minute:

```cron
* * * * * cd /home/CPANEL_USER/optimize && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler owns the application's daily-task reset checks; do not add a separate reset cron. For database-queued jobs, ask support about a supervised worker. If your shared plan cannot run one, use a short-lived worker every minute:

```cron
* * * * * cd /home/CPANEL_USER/optimize && php artisan queue:work --stop-when-empty --max-time=50 --tries=3 >> storage/logs/queue-cron.log 2>&1
```

Jobs must fit your hosting execution limits; long-running jobs may require a worker-capable hosting plan. Monitor failed jobs and rotate the cron log.

## Subsequent deployments and limitations

- Push to `main`; GitHub builds `vendor` and Vite assets, then mirrors the application. No server-side Git checkout, Node.js or Composer is required.
- `.env*`, runtime `storage`, the storage symlink, SQLite database files, `bootstrap/cache`, `.user.ini` and `public/.well-known` are preserved. Bundled warehouse images are uploaded only when missing; existing files are never overwritten by that image upload. Other obsolete application files are deleted, so keep backups and use a dedicated directory.
- **SFTP cannot execute Artisan.** After code uploads, run `php artisan optimize:clear --no-interaction`; run `php artisan migrate --force --no-interaction` whenever migrations change. If you enable optimization, rebuild it on the server with `php artisan optimize --no-interaction` afterward, never on the GitHub runner. Restart any persistent queue workers with `php artisan queue:restart --no-interaction`.
- This is an in-place upload, not an atomic or zero-downtime deployment. For database changes or large releases, back up first, run `php artisan down --no-interaction` before pushing, wait for the upload, run migrations and clear caches, then `php artisan up --no-interaction`. If upload fails, keep maintenance mode enabled until the application is repaired or restored. There is no automatic rollback.
- The workflow replaces `public/.htaccess`. If cPanel adds PHP-handler directives there, ask support to configure the PHP version outside that deployed file or agree on how those directives will be maintained before deploying.
- A green workflow confirms the build and SFTP transfer, not server initialization or application health. Check GitHub Actions logs and `storage/logs` for failures. No live deployment has been performed merely by adding these files.
