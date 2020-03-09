#!/usr/bin/env bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

cd ~
domain=6tu.me
wwwpath=/home/www
sslpath=fanssl
wget -O -  https://get.acme.sh | sh
source ~/.bashrc
~/.acme.sh/acme.sh --upgrade --auto-upgrade
~/.acme.sh/acme.sh --issue --dns dns_he -d *.${domain} -d ${domain} --force

cd ~/.acme.sh
rm -rf ${sslpath} && mkdir ${sslpath}
cp -r \*.${domain}/* ${sslpath}
# * => ''
rename "s/\*.//" ${sslpath}/*
zip -r ${sslpath}.zip ${sslpath}
rm -rf ${sslpath}
mv ${sslpath}.zip $wwwpath
cd $wwwpath && rm -rf ${sslpath}
unzip ${sslpath}.zip

/bin/cp -rf ${sslpath}/${domain}.key /opt/lampp/etc/ssl.key/server.key
/bin/cp -rf ${sslpath}/${domain}.cer /opt/lampp/etc/ssl.crt/server.crt
/opt/lampp/lampp reloadapache

rm -rf ${sslpath}

# ============ remote
cd /home
domain=6tu.me
wget --no-check-certificate https://pub.6tu.me/fanssl.zip
unzip fanssl.zip
/bin/cp -rf fanssl/${domain}.key /opt/lampp/etc/ssl.key/server.key
/bin/cp -rf fanssl/${domain}.cer /opt/lampp/etc/ssl.crt/server.crt
/opt/lampp/lampp reloadapache
rm -rf fanssl.zip fanssl

