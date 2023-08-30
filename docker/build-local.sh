echo Building LINUX-LOCAL docker environment
docker-compose build --force-rm

echo Docker Container start
docker-compose up -d

echo Building Vendor for PHP
docker-compose exec php bash -c "apt-get update && cd /var/www/html && composer install"
docker-compose exec php bash -c "git config --global --add safe.directory /var/www/html && git config core.fileMode false && exit"
cp ../.env.example ../.env
docker-compose exec php bash -c "php artisan key:generate"
docker-compose exec php bash -c "php artisan migrate && exit"
