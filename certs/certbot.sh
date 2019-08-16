#!/usr/bin/env bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

read -e -p "请输入域名，不能是IP:" domain

if [ ! -f "/usr/bin/yum" ]; then
  apt -y update
  apt -y install software-properties-common
  add-apt-repository ppa:certbot/certbot
  apt -y install certbot python3-pyasn1
else
  yum -y update
  yum -y install certbot
fi

certspath=/etc/letsencrypt/archive
test -d ${certspath} || mkdir -p ${certspath}

ipsecpath=/usr/local/etc/ipsec.d
test -d ${certspath} || ipsecpath=/etc/strongswan/ipsec.d

certbot certonly --non-interactive --agree-tos --rsa-key-size 4096 --email info@6tu.me --webroot -w /var/www -d ${domain}

echo 'rsa-key-size = 4096
renew-hook = /opt/lampp/lampp restartapache
' > /etc/letsencrypt/cli.ini
certbot renew --dry-run

/bin/cp -rf ${certspath}/${domain}/fullchain1.pem   /opt/lampp/etc/ssl.crt/server.crt
/bin/cp -rf ${certspath}/${domain}/privkey1.pem     /opt/lampp/etc/ssl.key/server.key

/opt/lampp/lampp restartapache

echo 'rsa-key-size = 4096
renew-hook = /usr/local/sbin/ipsec restart
' > /etc/letsencrypt/cli.ini
certbot renew --dry-run

/bin/cp -rf ${certspath}/${domain}/chain1.pem   ${ipsecpath}/cacerts/chain.pem
/bin/cp -rf ${certspath}/${domain}/cert1.pem    ${ipsecpath}/certs/cert.pem
/bin/cp -rf ${certspath}/${domain}/privkey1.pem ${ipsecpath}/private/privkey.pem

/usr/local/sbin/ipsec restart


