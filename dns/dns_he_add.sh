#!/usr/bin/env bash

PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

cd ~
wget -O -  https://get.acme.sh | sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/dns/acme.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/dns/dns_he.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/dns/sip.sh

# 引用脚本变量到当前shell中
source acme.sh  >> /dev/null 2>&1
source dns_he.sh
source sip.sh

# export HE_Username= and HE_Password=
# unset HE_Password
# record type: A /AAAA /CNAME /ALIAS /MX /NS /TXT

read -e -p "请输入HE.NET用户名:" dnsuser
read -e -p "请输入HE.NET密  码:" dnspass
read -e -p "请输入本机域名:" domain

HE_Username=$dnsuser
HE_Password=$dnspass
rec=$sip
rec_type=A

echo -e "add to dns: ${domain} => $rec => $rec_type \n"

dns_he_add $domain $rec $rec_type

rm -rf acme.sh
rm -rf dns_he.sh
rm -rf sip.sh
