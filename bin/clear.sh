#!/bin/sh

php bin/console cache:clear
sudo chgrp -R www-data var/cache
