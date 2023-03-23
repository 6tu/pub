#!/usr/bin/env bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

domain=de.liuyun.org

apt install -y openssl libssl-dev strongswan libstrongswan
apt install -y libcharon-extra-plugins libstrongswan-extra-plugins

test -d /opt/vpn/cert || mkdir -p /opt/vpn/cert
cd /opt
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/vpn/vpn.zip
7zz x vpn.zip
cd /opt/vpn
chmod +x proxyndp.updown strongswan *.sh
dos2unix *.*

# 替换配置文件
mv /etc/ipsec.conf     /etc/ipsec.conf.1
mv /etc/ipsec.secrets  /etc/ipsec.secrets.1
/bin/cp -rf ipsec.conf ipsec.secrets /etc/
/bin/cp -rf proxyndp.updown /etc/strongswan.d/

# 制作安装证书
# bash ./certs-init.sh
# bash ./makecert.sh
wget -O -  https://get.acme.sh | sh
# ~/.acme.sh/acme.sh --issue -d ${domain} -w /var/www/
~/.acme.sh/acme.sh --issue --apache -d ${domain} --force -m info@liuyun.org
/bin/cp -rf ~/.acme.sh/${domain}_ecc/* /opt/vpn/

/bin/cp -rf ./${domain}.key   /etc/ipsec.d/private/server.pem
/bin/cp -rf ./${domain}.cer   /etc/ipsec.d/certs/server.cert.pem
/bin/cp -rf ./fullchain.cer       /etc/ipsec.d/cacerts/fullchain.cert.pem
/bin/cp -rf ./ca.cer              /etc/ipsec.d/cacerts/ca.cert.pem

mv ${domain}.* *.pem *.cer /opt/vpn/cert/


# 防火墙和路由转发
iptables -t nat -A POSTROUTING -s 10.10.2.1/24 -o eth0 -j MASQUERADE
# ip6tables -t nat -A POSTROUTING -s {IPv6}/112 -o eth0 -j MASQUERADE

echo net.ipv4.ip_forward=1 >> /etc/sysctl.conf
echo net.ipv6.conf.all.forwarding=1 >> /etc/sysctl.conf
echo net.ipv6.conf.all.proxy_ndp=1 >> /etc/sysctl.conf

sysctl -p


# 更改 /etc/strongswan.d/proxyndp.updown
ifc=`ls /sys/class/net`
echo $ifc >/opt/vpn/ifc.log





