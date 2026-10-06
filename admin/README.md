# Admin

Browser URLs under `/admin` are not PHP files in this folder. `admin/.htaccess` sends each request to `admin/route-controller.php`. That script can open a session when the database is down, then hands the path to `Tsugi\Controllers\Admin`. The screen code is in `lib/src/Controllers/Admin/`. Templates are in `lib/src/Controllers/templates/Admin/`.

A folder screen such as `/admin/key` redirects to `/admin/key/`. Relative links on that page (`key-detail`, `user-detail`, and the others) only stay inside the folder when the browser URL ends in `/`. The folder names that redirect are listed in `route-controller.php`.

Do not add an admin page by putting a PHP file here. Add a controller route.

## Subfolders

These directories hold command-line maintenance scripts. Their names do not match an admin URL, so a request for the screen does not land on the scripts.

| Folder | Shell scripts | Screen |
| --- | --- | --- |
| `blob-maint/` | Blob cleanup and migration. See `blob-maint/README.md`. | `/admin/blob_status`, `/admin/blob_move`, `/admin/blob_clean` |
| `expire-maint/` | PII and login expiry batches. See `expire-maint/README.md`. | `/admin/expire/` |
| `install/` | `php admin/install/update.php` updates installed modules. | `/admin/modules/` |

## PHP files here

`route-controller.php` is the front controller. The other PHP files in this folder are maintenance. The admin UI includes them after the passphrase gate, or you run them from the shell.

- `upgrade.php` — `php admin/upgrade.php`, and the Upgrade Database action.
- `migrate-setup.php` and `migrate-run.php` — included while an upgrade applies a schema file.
- `sanity.php` — included while the console boots, to check PHP and `config.php`.
- `sanity-db.php` — forwards to the copy at the Tsugi root.
