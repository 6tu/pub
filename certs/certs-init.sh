#!/bin/bash
# 从外部设定密码用 passout
# 认定已存在密码用 passin

certspath=~/certs
test -d ${certspath}                 || mkdir -p ${certspath}
test -d ${certspath}/conf            || mkdir -p ${certspath}/conf
test -d ${certspath}/demoCA          || mkdir -p ${certspath}/demoCA
test -d ${certspath}/demoCA/certs    || mkdir ${certspath}/demoCA/certs
test -d ${certspath}/demoCA/crl      || mkdir ${certspath}/demoCA/crl
test -d ${certspath}/demoCA/newcerts || mkdir ${certspath}/demoCA/newcerts
test -d ${certspath}/demoCA/private  || mkdir ${certspath}/demoCA/private

touch ${certspath}/demoCA/serial
touch ${certspath}/demoCA/index.txt
touch ${certspath}/demoCA/index.txt.attr
touch ${certspath}/demoCA/crlnumber
echo 00000000 > ${certspath}/demoCA/serial

cakeypw=`openssl rand -base64 8`
#cakeypw==`head  /dev/urandom  |  tr -dc A-Za-z0-9  | head -c 12`
echo ${cakeypw} > ${certspath}/demoCA/private/cakeypw

wget -q -P ${certspath}/demoCA/private/ --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/certs/cakey_nopw.pem
wget -q -P ${certspath}/demoCA/ --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/certs/cacert.pem
wget -q -P ${certspath}/conf/ --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/certs/openssl.conf
wget -q -P ${certspath}/conf/ --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/certs/openssl-ike.conf

openssl rsa -aes256 -passout pass:${cakeypw} -in ${certspath}/demoCA/private/cakey_nopw.pem -out ${certspath}/demoCA/private/cakey.pem

