
echo Building LINUX-LOCAL docker environment
docker-compose build --force-rm

echo Docker Container start
docker-compose up -d

echo Building Vendor for PHP
docker-compose exec php bash -c "apt-get update && apt-get install git && git config core.fileMode false && cd /var/www/html composer install -v && exit"
cp ..\.env.example ..\.env
docker-compose exec php bash -c "php artisan key:generate"
docker-compose exec php bash -c "ln -s /var/www/html/storage/app/public/uploads /var/www/html/public/uploads"

