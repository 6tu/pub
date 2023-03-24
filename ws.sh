#!/bin/bash

time=`date +%Y%m%d%H%M%S`
apt update -y
apt install -y lrzsz unar zip unzip dos2unix
apt install -y wget curl net-tools
apt install -y openssl ca-certificates gnupg

apt install -y mysql-server
apt install -y mariadb-server
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

cd /opt
test -d /etc/apache2/ssl || mkdir -p /etc/apache2/ssl
test -d /home/htdocs     || mkdir -p /home/htdocs
test -d /home/tmp        || mkdir -p /home/tmp
test -d /opt/7z2201      || mkdir -p /opt/7z2201

wget https://7-zip.org/a/7z2201-linux-x64.tar.xz
tar -xvJf 7z2201-linux-x64.tar.xz -C ./7z2201/
/bin/cp -rf ./7z2201/7zz ./7z2201/7zzs /usr/bin/

wget --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/htdocs.zip
7zz x htdocs.zip
mv kod temp tz /home/htdocs/
chown -R www-data:www-data /home/htdocs

sed -i "s/\/var\/www\/html/\/home\/htdocs/g" /etc/apache2/sites-enabled/000-default.conf
sed -i "s/\/var\/www\/html/\/home\/htdocs/g" /etc/apache2/sites-enabled/001-default-ssl.conf
sed -i "s/\/var\/www\//\/home\/htdocs\//g" /etc/apache2/apache2.conf

systemctl restart apache2
systemctl stop mysql

# 制作安装证书
read -p "Enter domain: "  domain
echo "${domain}"

wget -O -  https://get.acme.sh | sh
# ~/.acme.sh/acme.sh --issue -d ${domain} -w /var/www/
~/.acme.sh/acme.sh --issue --apache -d ${domain} --force -m info@liuyun.org

/bin/cp -rf ~/.acme.sh/${domain}_ecc/*.key   /etc/apache2/ssl/
/bin/cp -rf ~/.acme.sh/${domain}_ecc/*.cer   /etc/apache2/ssl/


# https://certbot.eff.org/instructions?ws=nginx&os=ubuntufocal
# apt install -y snapd
# snap install core
# snap refresh core
# apt remove -y certbot
# apt install -y certbot
# snap install --classic certbot
# ln -s /snap/bin/certbot /usr/bin/certbot
# certbot --apache
