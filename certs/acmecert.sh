#!/usr/bin/env bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

# 用acme.sh申请证书
read -e -p "请输入HE.NET用户名:" dnsuser
read -e -p "请输入HE.NET密  码:" dnspass
read -e -p "请输入本机域名:" domain

# export HE_Username="" and HE_Password=""
HE_Username=$dnsuser
HE_Password=$dnspass

wget -O -  https://get.acme.sh | sh
source ~/.bashrc

~/.acme.sh/acme.sh --issue --dns dns_he -d ${domain}

/root/.acme.sh/acme.sh --installcert -d shideyun.com \
        --key-file /opt/lampp/etc/ssl.key/shideyun.com.key \
        --fullchain-file /opt/lampp/etc/ssl.crt/shideyun.com.crt \
        --reloadcmd "/opt/lampp/lampp reloadapache"
/opt/lampp/lampp reloadapache

ipsecpath=/usr/local/etc/ipsec.d
test -d ${certspath} || ipsecpath=/etc/strongswan/ipsec.d
/bin/cp -rf /root/.acme.sh/${domain}/chain1.pem   ${ipsecpath}/cacerts/chain.pem
/bin/cp -rf /root/.acme.sh/${domain}/cert1.pem    ${ipsecpath}/certs/cert.pem
/bin/cp -rf /root/.acme.sh/${domain}/privkey1.pem ${ipsecpath}/private/privkey.pem
ipsec restart


