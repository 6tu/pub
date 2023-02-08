#!/bin/bash


time=`date +%Y%m%d%H%M%S`
apt update -y
apt install -y lrzsz unar zip unzip wget curl net-tools openssl libssl-dev gnupg

apt install -y mysql-server mysql-client
apt install -y sqlite3 libsqlite3-dev

# 设定 mysql 密码和 phpmyadmin
mysqlpw=`openssl rand -base64 8`
echo ${mysqlpw} > /etc/mysql/mysqlpw.${time}
systemctl restart mysql
mysqladmin --user=root password ${mysqlpw}

apt install -y apache2 libapache2-mod-fcgid libapache2-mod-php spawn-fcgi php
apt install -y php-fpm php-cli php-common php-cgi 
apt install -y php-pdo php-mysql php-mysqli php-mysqlnd php-sqlite3 php-pgsql php-odbc
apt install -y php-gd php-curl php-intl php-tidy php-mbstring php-zip php-bz2
apt install -y php-json php-xml
apt install -y php-dev

a2enmod actions fcgid alias proxy_fcgi proxy

a2enmod ssl http2
a2ensite default-ssl.conf
systemctl reload apache2
mv /etc/apache2/sites-enabled/default-ssl.conf /etc/apache2/sites-enabled/001-default-ssl.conf
systemctl restart apache2

echo > /root/composer.json
apt install -y composer
composer update

test -d /etc/apache2/ssl || mkdir -p /etc/apache2/ssl
test -d /var/www/html || mkdir -p /var/www/html
test -d /home/tmp || mkdir -p /home/tmp
cd /var/www/html
wget --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/htdocs.zip
unzip -O CP936 htdocs.zip
chown -R www-data:www-data /var/www/html

mv /var/www/html/htdocs.zip /home/tmp/

# https://certbot.eff.org/instructions?ws=nginx&os=ubuntufocal

apt install -y snapd
snap install core
snap refresh core
apt remove -y certbot
# apt install -y certbot
snap install --classic certbot
ln -s /snap/bin/certbot /usr/bin/certbot
# certbot --apache
