#!/bin/sh

git fetch --all
git checkout --force origin/main
composer install
composer dump-env
yarn install
yarn build
php bin/console doctrine:migrations:migrate
