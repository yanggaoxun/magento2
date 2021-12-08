#!/bin/bash
cd /usr/share/nginx/html/magento2
git pull
rm -rf var/view_preprocessed/pub/static/frontend/*
rm -rf pub/static/frontend/*
php bin/magento s:s:d -f en_US
#chmod +x *.sh
#sed -i "s/\r//" *.sh
echo " Complete "
