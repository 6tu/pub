# -------- 安装 strongswan
# https://pkgs.org/download/strongswan
# https://wiki.strongswan.org/projects/strongswan/wiki/UserDocumentation
# http://wiki.seanmadden.net/networking/configure_strongswan_as_an_ipsec_vpn
# https://centos.pkgs.org/7/epel-x86_64/strongswan-5.6.3-1.el7.x86_64.rpm.html
# https://www.tecmint.com/how-to-enable-epel-repository-for-rhel-centos-6-5/
# wget http://dl.fedoraproject.org/pub/epel/7/x86_64/Packages/e/epel-release-7-11.noarch.rpm
# wget http://dl.fedoraproject.org/pub/epel/7/x86_64/Packages/s/strongswan-5.6.3-1.el7.x86_64.rpm
# wget http://dl.fedoraproject.org/pub/epel/7/x86_64/Packages/s/strongswan-charon-nm-5.6.3-1.el7.x86_64.rpm
# wget http://dl.fedoraproject.org/pub/epel/7/x86_64/Packages/s/strongswan-libipsec-5.6.3-1.el7.x86_64.rpm
# wget http://dl.fedoraproject.org/pub/epel/7/x86_64/Packages/s/strongswan-tnc-imcvs-5.6.3-1.el7.x86_64.rpm
# wget http://dl.fedoraproject.org/pub/epel/7/SRPMS/Packages/s/strongswan-5.6.3-1.el7.src.rpm
# rpm -Uvh strongswan*rpm

yum install -y epel-release
yum -y update
yum install -y wget curl dos2unix zip unzip
yum install -y gmp-devel openssl-devel zlib-devel bzip2-devel xz-devel libcurl-devel
wget http://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm
rpm -Uvh epel-release*rpm
yum install -y strongswan-charon-nm strongswan-libipsec strongswan-tnc-imcvs strongswan ipsec-tools  xl2tpd certbot

strongswan version
chkconfig --add strongswan
systemctl enable strongswan
systemctl enable strongswan
systemctl start strongswan

domain=ysuo.org

# 使用前必须打开80端口
curl https://get.acme.sh | sh
source ~/.bashrc
acme.sh --issue -d ${domain} -w /var/www/
/bin/cp -rf /root/.acme.sh/${domain}/fullchain.cer /etc/pki/tls/certs/localhost.crt
# /bin/cp -rf /root/.acme.sh/${domain}/${domain}.cer  
/bin/cp -rf /root/.acme.sh/${domain}/${domain}.key  /etc/pki/tls/private/localhost.key

#  --standalone参数，使用前80端口不能被占用；  renew --apache 参数用于当前WEB服务器
certbot certonly --non-interactive --agree-tos --standalone --preferred-challenges http --email zhongxiaolee@gmail.com -d ${domain}
mkdir -p /etc/letsencrypt
echo 'rsa-key-size = 4096
renew-hook = /usr/sbin/ipsec reload && /usr/sbin/ipsec secrets
' > /etc/letsencrypt/cli.ini
/bin/cp -rf /etc/letsencrypt/live/${domain}/chain.pem   /etc/strongswan/ipsec.d/cacerts/chain.pem
/bin/cp -rf /etc/letsencrypt/live/${domain}/cert.pem    /etc/strongswan/ipsec.d/certs/cert.pem
/bin/cp -rf /etc/letsencrypt/live/${domain}/privkey.pem /etc/strongswan/ipsec.d/private/privkey.pem

# -------- 更新系统，改变时区和系统语言
systemctl stop firewalld
systemctl mask firewalld
yum install -y iptables-services

# yum install -y kde-l10n-Chinese
timedatectl set-timezone "Asia/Shanghai"
localectl  set-locale LANG=zh_CN.UTF8
cat /etc/redhat-release /etc/issue > /etc/issue.tmp
/bin/cp -rf /etc/issue.tmp /etc/issue
/bin/cp -rf /etc/issue.tmp /etc/issue.net
rm -rf /etc/issue.tmp

# 建立所需目录
test -d ~/shell || mkdir -p ~/shell
test -d ~/ss    || mkdir -p ~/ss
test -d ~/vpn   || mkdir -p ~/vpn
test -d ~/glibc || mkdir -p ~/glibc
test -d ~/soft  || mkdir -p ~/soft
# 下载所需 shell 文件
cd ~/shell
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/linux/centos/dependencies.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/teddysun/shadowsocks_install/master/shadowsocks-all.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/linux/vpn/ikev2vpn.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/linux/centos/glibc.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/linux/xampp/lampp.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/linux/centos/denyssh.sh
chmod +x *.sh && dos2unix *.sh

