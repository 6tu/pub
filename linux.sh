#!/usr/bin/env bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

#=================================================
#	System Required: Debian/Ubuntu/CentOS
#	Description: System Initialization
#	Version: 0.0.1
#	Author: sdy
#	Blog: https://shideyun.com
#=================================================

#检查系统
check_sys(){
	if [[ -f /etc/redhat-release ]]; then
		release="centos"
	elif cat /etc/issue | grep -q -E -i "debian"; then
		release="debian"
	elif cat /etc/issue | grep -q -E -i "ubuntu"; then
		release="ubuntu"
	elif cat /etc/issue | grep -q -E -i "centos|red hat|redhat"; then
		release="centos"
	elif cat /proc/version | grep -q -E -i "debian"; then
		release="debian"
	elif cat /proc/version | grep -q -E -i "ubuntu"; then
		release="ubuntu"
	elif cat /proc/version | grep -q -E -i "centos|red hat|redhat"; then
		release="centos"
    fi
	#bit=`uname -m`
}

# 选择系统类型
echo "please choose the type of your VPS(Xen、KVM: 1  ,  OpenVZ: 2):"
read -p "your choice(1 or 2):" os_choice
if [ "$os_choice" = "1" ]; then
    os="1"
    os_str="Xen、KVM"
    else
        if [ "$os_choice" = "2" ]; then
            os="2"
            os_str="OpenVZ"
            else
            echo "wrong choice!"
            exit 1
        fi
fi

# 网卡名称
netname=ifconfig | grep  "Link" | awk '{print $1}'

sed -i 's/\(# *Port \)22/\22622/' /etc/ssh/sshd_config

echo "" && echo "======== sshd white list ========" && echo ""
cip=`who am i | grep -o '(.*)' | sed -ne "s/(\(.*\))/\1/p"`
echo "sshd:$cip" >> /etc/hosts.allow
echo "sshd:89.36.215.108" >> /etc/hosts.allow
echo "sshd:all" >> /etc/hosts.deny
chmod 666 /etc/hosts.*

# 清空iptables规则
iptables -t nat -F
iptables -t nat -X
iptables -t nat -Z
iptables -t nat -P PREROUTING ACCEPT
iptables -t nat -P POSTROUTING ACCEPT
iptables -t nat -P OUTPUT ACCEPT
iptables -t mangle -F
iptables -t mangle -X
iptables -t mangle -P PREROUTING ACCEPT
iptables -t mangle -P INPUT ACCEPT
iptables -t mangle -P FORWARD ACCEPT
iptables -t mangle -P OUTPUT ACCEPT
iptables -t mangle -P POSTROUTING ACCEPT
iptables -F
iptables -X
iptables -P FORWARD ACCEPT
iptables -P INPUT ACCEPT
iptables -P OUTPUT ACCEPT
iptables -t raw -F
iptables -t raw -X
iptables -t raw -P PREROUTING ACCEPT
iptables -t raw -P OUTPUT ACCEPT

iptables -A FORWARD -s 10.31.2.0/24  -j ACCEPT
iptables -t nat -A POSTROUTING -s 10.31.2.0/24 -o ${netname} -j MASQUERADE

if [ ! -f "/usr/bin/yum" ]; then
  apt -y update
  apt -y upgrade
  apt install -y wget curl libcurl3-dev vim dos2unix virt-what psmisc lsof gawk ca-certificates 
  apt install -y tar zip unzip bzip2 zlib1g-dev libbz2-dev
  apt install -y openssl libssl-dev libcurl4-openssl-dev libsasl2-dev
  apt install -y iptables-persistent
  iptables-save > /etc/sysconfig/iptables
  netfilter-persistent save
  netfilter-persistent reload
  wget -O -  https://get.acme.sh | sh
else
  yum -y update
  yum install epel-release -y
  yum install -y wget curl traceroute net-tools dos2unix zip unzip openssl psmisc virt-what lsof
  yum install -y iptables-services
  systemctl stop firewalld
  systemctl mask firewalld
  systemctl disable firewalld
  systemctl enable iptables.service
  service iptables save
  systemctl restart iptables.service
  systemctl restart sshd.service
  sed -i 's/SELINUX=enforcing/SELINUX=disabled/' /etc/selinux/config
  # service iptables save
  wget -O -  https://get.acme.sh | sh
fi
echo 3 > /proc/sys/vm/drop_caches

# 下载相关脚本
cd ~
test -d shell || mkdir -p shell
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/denyssh.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/dns/dns_he_add.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/lampp.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/vpn/ikev2vpn.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/certs/acmecert.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/pub/master/certs/dellog.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/teddysun/shadowsocks_install/master/shadowsocks-all.sh

chmod +x *.sh && dos2unix *.sh

clear && echo ""
cd ~ && bash denyssh.sh
cd ~ && bash dns_he_add.sh
cd ~ && bash lampp.sh
cd ~ && bash ikev2vpn.sh
cd ~ && bash acmecert.sh
cd ~ && bash dellog.sh

rm -rf ./shell/* && mv *.sh shell/
echo 3 > /proc/sys/vm/drop_caches
echo > ~/.bash_history
history -c




