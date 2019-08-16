#!/usr/bin/env bash

PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

wget 
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

