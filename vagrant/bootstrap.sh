#!/usr/bin/env bash

apt-get update
apt-get install -y apache2
if ! [ -L /var/www/html ]; then
  rm -rf /var/www/html
  ln -fs /vagrant/src/public /var/www/html
fi

cp /vagrant/vagrant/vhost.conf /etc/apache2/sites-enabled/000-default.conf
service apache2 start

mysql -u root < /vagrant/vagrant/user.sql
cd /vagrant/src;
vendor/bin/phinx migrate -e development