#!/bin/bash
# 从外部设定密码用 passout
# 确认已存在密码用 passin
# date +%s 从1970/1/1/00:00:00到目前经历的秒数

certspath=~/certs
cd ${certspath}

# 获取 IP 或者 域名
IP=`curl -s checkip.dyndns.com | cut -d' ' -f 6  | cut -d'<' -f 1`
if [ -z $IP ]; then
    IP=`curl -s ifconfig.me/ip`
fi
echo "" && echo ip : $IP && echo ""

time=`date +%Y%m%d%H%M%S`
echo "" && echo time : ${time} && echo ""

echo "please input the ip (or domain) of your VPS:"
read -p "ip or domain(default_value:${IP}):" vps_ip
if [ "$vps_ip" = "" ]; then
        vps_ip=$IP
fi
IP1=${IP}
function check_ip() {
    IP=$1
    VALID_CHECK=$(echo $IP|awk -F. '$1<=255&&$2<=255&&$3<=255&&$4<=255{print "yes"}')
    if echo $IP|grep -E "^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$">/dev/null; then
        if [ ${VALID_CHECK:-no} == "yes" ]; then
            san="IP.1 = $IP"
        fi
    else
        san="DNS.2 = $IP\nIP.1 = $IP1"
    fi
}
# Example
check_ip $vps_ip
echo ${san}
sed -i '368,$d' ${certspath}/conf/openssl-ike.conf
echo -e $san >> ${certspath}/conf/openssl-ike.conf
# echo IP.1 = $IP1 >> ${certspath}/conf/openssl-ike.conf
# echo DNS.2 = $vps_ip >> ${certspath}/conf/openssl-ike.conf

# 生成 CSR 和 KEY
cakeypw=`sed -n 1p ${certspath}/demoCA/private/cakeypw`
userpw=`openssl rand -base64 8`
touch ${certspath}/userpw
echo ${time} ${userpw} >> ${certspath}/userpw

echo "Certificate Signing Request"
openssl req -utf8 -sha512 -days 3653 -passout pass:${userpw} -newkey rsa:2048 \
        -keyout ${certspath}/user_csr.key -out ${certspath}/user_csr.pem \
        -subj "/C=CN/CN=$vps_ip" -config ${certspath}/conf/openssl-ike.conf

# 颁发证书
echo "issuing a certificate"
echo -e "y\ny\n"|openssl ca -extensions v3_req \
        -cert ${certspath}/demoCA/cacert.pem -keyfile ${certspath}/demoCA/private/cakey.pem \
        -passin pass:${cakeypw} -in ${certspath}/user_csr.pem -md sha256 -out ${certspath}/user_cert.crt \
        -days 3653 -config ${certspath}/conf/openssl-ike.conf

# openssl ca -extensions usr_cert -in ${certspath}/user_csr.pem -md sha256 -out ${certspath}/user_cert.crt \
#        -days 3653 -config ${certspath}/conf/openssl-ike.conf

# 去掉KEY 密码
openssl rsa -passin pass:${userpw} -in ${certspath}/user_csr.key -out ${certspath}/user_csr_nopw.key

# 重命名

test -d ${certspath}/"${vps_ip}" || mkdir -p  ${certspath}/"${vps_ip}"
/bin/cp -rf ${certspath}/"user_csr.key"       ${certspath}/"${vps_ip}"/${time}_key.pem
/bin/cp -rf ${certspath}/"user_csr_nopw.key"  ${certspath}/"${vps_ip}"/${time}_nopwkey.pem
/bin/cp -rf ${certspath}/"user_csr.pem"       ${certspath}/"${vps_ip}"/${time}_csr.pem
/bin/cp -rf ${certspath}/"user_cert.crt"      ${certspath}/"${vps_ip}"/${time}_cert.pem





