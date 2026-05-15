#!/usr/bin/env bash

set -euo pipefail

echo "======================================"
echo "   Simple Laravel 12 Docker Setup    "
echo "======================================"

# ────────────────────────────────────────────────
# 1. Optional: Clean only stopped containers (safer)
#    Uncomment if you really want to force clean
# ────────────────────────────────────────────────
echo "Cleaning old stopped containers..."
docker stop $(docker ps -a -q) 2>/dev/null || true
docker container prune -f 2>/dev/null || true

# ────────────────────────────────────────────────
# 3. Start Docker services
# ────────────────────────────────────────────────
echo "Starting services..."
cd docker || { echo "Error: docker/ folder not found"; exit 1; }

docker compose -p laraveldev up -d --build --remove-orphans

cd ..

# Short wait (usually enough)
sleep 8
echo "→ Services started (waiting a bit more if needed...)"

# ────────────────────────────────────────────────
# 4. Laravel setup inside container
# ────────────────────────────────────────────────
echo "Configuring Laravel..."

docker compose -p laraveldev exec -T php bash -c '
    # Download .env.example if missing
    if [ ! -f .env.example ]; then
        echo "→ .env.example missing → downloading official one..."
        curl -s -o .env.example https://raw.githubusercontent.com/laravel/laravel/12.x/.env.example
    fi

    # Create .env if missing
    if [ ! -f .env ]; then
        cp .env.example .env
        echo "→ .env created"
    else
        echo "→ .env already exists"
    fi

    # Use file cache to avoid early database errors
    if ! grep -q "^CACHE_STORE=" .env; then
        echo "CACHE_STORE=file" >> .env
        echo "→ Added CACHE_STORE=file to .env (safe for initial setup)"
    fi
'

# Install/update dependencies
echo "→ Running composer install..."
docker compose -p laraveldev exec -T php composer install --prefer-dist --no-progress --optimize-autoloader

# Generate key
echo "→ Generating app key..."
docker compose -p laraveldev exec -T php php artisan key:generate --ansi --force

# Migrate + Seed (fresh database reset with example data)
# ────────────────────────────────────────────────
echo "→ Resetting database and seeding (migrate:fresh --seed)..."
docker compose -p laraveldev exec -T php php artisan migrate:fresh --seed --force --ansi

# Clear caches (helps after install)
docker compose -p laraveldev exec -T php php artisan optimize:clear

# ────────────────────────────────────────────────
# 5. Permissions (safe defaults)
# ────────────────────────────────────────────────
echo "→ Fixing permissions..."
docker compose -p laraveldev exec -T php chown -R www-data:www-data storage bootstrap/cache
docker compose -p laraveldev exec -T php chmod -R 775 storage bootstrap/cache

# Uncomment only if you have file permission issues on host (macOS/WSL)
# sudo chmod -R 777 ../src/storage ../src/bootstrap/cache 2>/dev/null || true

# ────────────────────────────────────────────────
# 6. Done!
# ────────────────────────────────────────────────
echo ""
echo "┌─────────────────────────────────────────────┐"
echo "│          Setup finished!                    │"
echo "└─────────────────────────────────────────────┘"
echo ""
echo "→ Open:          http://localhost"
echo "→ phpMyAdmin:    http://localhost:8080"
echo ""
echo "Quick commands:"
echo "  docker compose -p laraveldev logs -f         # see logs"
echo "  docker compose -p laraveldev exec php bash   # enter container"
echo "  docker compose -p laraveldev down            # stop"
echo ""