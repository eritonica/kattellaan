kattellaan
==========

Kattellaan is dating site.

A jQuery/Bootstrap single-page frontend (`index.html`, `js/kattellaan.js`) backed by a PHP API (`php/api.php`) and a MySQL/MariaDB database.

Running locally
---------------

### Requirements

- PHP 8 with the `mysqli` extension (`mbstring`, `fileinfo` and `openssl` are recommended)
- MySQL or MariaDB

On Windows they can be installed with winget:

```powershell
winget install --id PHP.PHP.8.4 -e
winget install --id MariaDB.Server -e
```

winget's PHP comes without a `php.ini`. Copy `php.ini-development` to `php.ini` in the PHP folder,
then uncomment `extension_dir = "ext"` and `extension=mysqli` (and `mbstring`, `fileinfo`, `openssl`).
You can check the result with `php -m`.

On Debian/Ubuntu: `sudo apt install php-cli php-mysql mariadb-server`.

### 1. Start the database

If MariaDB is installed as a service, it is already running. Otherwise start it in a terminal and leave it open:

```powershell
& "C:\Program Files\MariaDB 13.0\bin\mariadbd.exe" --defaults-file="C:\Program Files\MariaDB 13.0\data\my.ini" --console
```

### 2. Create the database and a user

```sql
CREATE DATABASE kattellaan CHARACTER SET utf8mb4;
CREATE USER 'kattellaan'@'localhost' IDENTIFIED BY 'choose-a-password';
CREATE USER 'kattellaan'@'127.0.0.1' IDENTIFIED BY 'choose-a-password';
GRANT ALL ON kattellaan.* TO 'kattellaan'@'localhost';
GRANT ALL ON kattellaan.* TO 'kattellaan'@'127.0.0.1';
```

(For example, run `mariadb -u root` and paste the statements above.)

### 3. Store the credentials and create the tables

Run these from the repository root:

```sh
php passwd-gen.php kattellaan choose-a-password   # writes .passwd (git-ignored)
php php/create_tables.php
```

### 4. Start the website

```sh
php -S localhost:8080 -t .
```

Open <http://localhost:8080/>.

### 5. Distance calculation (optional)

Distances between users are calculated by a background worker. Each run calculates one
missing distance. When there is nothing left to do it sleeps for 30 minutes, so you can stop it at any time.

```sh
./daemon.sh                      # Linux / Git Bash: runs the worker in a loop
php php/do_distance_work.php     # or a single run
```

Local development behavior
--------------------------

When the site is not running on the production server, it switches into local mode automatically:

- **Mail** (welcome mail, password recovery, invites) is written to `.mail.log` instead of being sent.
- **Geocoding:** Google's geocoding API requires an API key. On `localhost`, `js/kattellaan-dev.js` routes
  geocoding requests to `php/geocode-stub.php`, which always answers with a fixed location (Helsinki).
- **Uploads** are stored in `uploads/` (git-ignored).

Configuration
-------------

All settings are in `php/config.php`. When the production paths exist, they are used. Each setting can be
overridden with an environment variable:

| Variable | Default |
| --- | --- |
| `KATTELLAAN_PASSWD_FILE` | `/home/temek/kattellaan/.passwd`, else `.passwd` in the repository |
| `KATTELLAAN_UPLOAD_DIR` | `/home/temek/kattellaan-live/uploads`, else `uploads/` |
| `KATTELLAAN_DB_HOST` | `127.0.0.1` |
| `KATTELLAAN_DB_NAME` | `kattellaan` |
| `KATTELLAAN_LOCAL` | `1` unless `/home/temek/kattellaan` exists |
| `KATTELLAAN_MAIL_LOG` | `.mail.log` in the repository |

`php/dbwrapper/db.php` is a reimplementation of the original `dbwrapper` submodule, which was removed in 2014
and is no longer available.
