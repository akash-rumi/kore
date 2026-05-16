# KoreSearch - Setup Guide

**Stack:** Laravel 12 · PHP 8.2 · MySQL 8 · Nginx · Docker

---

## Prerequisites

Install these before starting:

| Tool           | Version                      | Download                                       |
| -------------- | ---------------------------- | ---------------------------------------------- |
| Docker Desktop | Latest                       | https://www.docker.com/products/docker-desktop |
| Docker Compose | Included with Docker Desktop | -                                              |
| Git            | Any                          | https://git-scm.com                            |

Verify installation:

```bash
docker --version
docker compose version
```

---

## Project Structure

```
kore/
├── docker/
│   ├── compose.yml          # Docker services definition
│   ├── nginx/
│   │   └── default.conf     # Nginx virtual host config
│   └── php/
│       └── Dockerfile       # PHP 8.2-FPM image with extensions
├── src/                     # ← Laravel application lives here
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── .env                 # Your environment config (not in git)
│   ├── .env.example         # Template
│   ├── artisan
│   └── composer.json
└── setup.sh                 # One-command setup script
```

---

## Docker Services

| Container    | Service     | Port                    |
| ------------ | ----------- | ----------------------- |
| `webserver`  | Nginx       | `http://localhost`      |
| `php`        | PHP 8.2-FPM | Internal (9000)         |
| `db`         | MySQL 8     | `localhost:3306`        |
| `phpmyadmin` | phpMyAdmin  | `http://localhost:8080` |

---

## Step 1 - Clone or Extract Project

```bash
# If using git:
git clone <your-repo-url> kore
cd kore

# If using zip/rar:
unzip koresearch.zip -d kore
cd kore
```

---

## Step 2 - Configure Environment

Copy the example environment file:

```bash
cp src/.env.example src/.env
```

Edit `src/.env` with your settings:

```env
APP_NAME=KoreSearch
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# IMPORTANT: Use 'db' not '127.0.0.1' - this is the Docker service name
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=akash
DB_PASSWORD=root

CACHE_STORE=file
SESSION_DRIVER=file

# Optional: AI course generation (get key at https://console.anthropic.com)
ANTHROPIC_API_KEY=
```

> **Important:** `DB_HOST` must be `db` (the Docker service name), not `127.0.0.1`.
> Using `127.0.0.1` means the PHP container tries to connect to itself - MySQL won't be found.

---

## Step 3 - Run Setup Script

From the project root (where `setup.sh` lives):

```bash
bash setup.sh
```

The script will:

1. Stop and remove old containers
2. Build the PHP Docker image
3. Start all 4 services (nginx, php, mysql, phpmyadmin)
4. Create `.env` if missing
5. Run `composer install`
6. Generate `APP_KEY`
7. Run `php artisan migrate:fresh --seed` (fresh DB + demo data)
8. Fix storage permissions

Full output looks like this:

```
======================================
   Simple Laravel 12 Docker Setup
======================================
Cleaning old stopped containers...
Starting services...
[+] Building 1.8s (13/13) FINISHED
[+] Running 4/4
 ✔ Container db          Started
 ✔ Container php         Started
 ✔ Container phpmyadmin  Started
 ✔ Container webserver   Started
Configuring Laravel...
→ .env already exists
→ Running composer install...
→ Generating app key...
→ Resetting database and seeding...
→ Fixing permissions...

┌─────────────────────────────────────────────┐
│          Setup finished!                    │
└─────────────────────────────────────────────┘

→ Open:          http://localhost
→ phpMyAdmin:    http://localhost:8080
```

---

## Step 4 - Open the App

| URL                     | What                    |
| ----------------------- | ----------------------- |
| `http://localhost`      | KoreSearch application  |
| `http://localhost:8080` | phpMyAdmin (DB browser) |

---

## Default Login Credentials

After seeding, these accounts are available:

| Role    | Email                    | Password   |
| ------- | ------------------------ | ---------- |
| Admin   | `admin@koresearch.com`   | `password` |
| Student | `student@koresearch.com` | `password` |

> Check `database/seeders/DatabaseSeeder.php` for the exact seeded accounts.

---

## Common Commands

### Enter the PHP container

```bash
docker compose -p laraveldev exec php bash
```

### Run Artisan commands

```bash
# From inside the container:
php artisan migrate
php artisan db:seed
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:list

# Or from host:
docker compose -p laraveldev exec php php artisan <command>
```

### View logs

```bash
# All services:
docker compose -p laraveldev logs -f

# PHP only:
docker compose -p laraveldev logs -f php

# Laravel logs (inside container):
tail -f storage/logs/laravel.log
```

### Stop services

```bash
docker compose -p laraveldev down
```

### Stop and remove volumes (full reset including DB):

```bash
docker compose -p laraveldev down -v
```

### Restart services

```bash
docker compose -p laraveldev restart
```

---

## File Storage (Thumbnails)

Course thumbnails are stored using Laravel's `public` disk. After setup, create the symlink inside the container:

```bash
docker compose -p laraveldev exec php php artisan storage:link
```

Thumbnails will then be accessible at `http://localhost/storage/thumbnails/`.

---

## AI Integration Setup (Optional)

To enable AI-powered course curriculum generation:

1. Get an API key at https://console.anthropic.com
2. Add it to `src/.env`:

```env
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxx
```

3. Clear config cache:

```bash
docker compose -p laraveldev exec php php artisan config:clear
```

When uploading a course from the Admin Dashboard, the system will automatically call Claude to generate 6 curriculum topics. The "✨ AI Suggest" button also generates a course description on the fly.

If the key is not set, the system falls back to category-based default topics silently.

---

## Troubleshooting

### `Could not open input file: artisan`

The `src/` folder is missing Laravel core files. Run the setup script again after ensuring all project files are in `src/`.

### `SQLSTATE: Connection refused` or `php_network_getaddresses`

`DB_HOST` in `.env` is set to `127.0.0.1`. Change to `DB_HOST=db`.

### `array_merge(): Argument #2 must be of type array, int given`

`config/app.php` still has Laravel 10 fluent provider/alias calls. Replace `providers` and `aliases` values with `[]`.

### `Target class [files] does not exist`

Vendor directory contains stale Laravel 10 packages. Inside the container:

```bash
rm -rf vendor composer.lock
composer install
```

### `Class not found` errors after upgrade

Run inside the container:

```bash
composer dump-autoload
php artisan optimize:clear
```

### Storage folder permission errors

```bash
docker compose -p laraveldev exec php chown -R www-data:www-data storage bootstrap/cache
docker compose -p laraveldev exec php chmod -R 775 storage bootstrap/cache
```

### Port 80 already in use

Another service is using port 80. Stop it or change the nginx port in `docker/compose.yml`:

```yaml
ports:
  - "8081:80" # use 8081 instead
```

---

## Resetting Everything

To completely wipe and restart from scratch:

```bash
# Stop all containers and delete volumes (database wiped)
docker compose -p laraveldev down -v

# Remove built images
docker rmi laraveldev-php 2>/dev/null || true

# Run setup fresh
bash setup.sh
```
