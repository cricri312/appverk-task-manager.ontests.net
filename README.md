docker-compose up --build -d

docker-compose exec php bash

php bin/console make:migration
php bin/console doctrine:migrations:migrate

php bin/console doctrine:fixtures:load

chmod -R 777 /var/www/html

php bin/console cache:clear