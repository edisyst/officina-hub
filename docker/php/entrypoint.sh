#!/bin/sh
set -e

# Attende che il DB sia pronto
echo "Attesa database..."
until php artisan migrate --force --no-interaction 2>/dev/null; do
    echo "Migrate fallita, riprovo tra 3 secondi..."
    sleep 3
done
echo "Database pronto."

# Configura il cron per lo scheduler Laravel
echo "* * * * * cd /var/www/html && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1" \
    | crontab -

# Avvia il demone cron
crond -l 2

# Avvia il queue worker in background (richiami scadenze + email commesse)
php artisan queue:work --queue=default --tries=3 --backoff=60 --sleep=3 --max-time=3600 &
QUEUE_PID=$!
echo "Queue worker avviato (PID $QUEUE_PID)."

# Avvia php-fpm in foreground (processo principale)
exec php-fpm
