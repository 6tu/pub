#!/bin/bash

if [ ! -f "/usr/bin/yum" ]; then
    apt install -y dos2unix virt-what
else
    yum install -y dos2unix virt-what
fi

test -d ~/vpn || mkdir ~/vpn
cd ~/vpn
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/certs/certs-init.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/certs/makecert.sh
#wget -q --no-check-certificate https://raw.githubusercontent.com/quericy/one-key-ikev2-vpn/master/one-key-ikev2.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/linux/vpn/quericy-one-key-ikev2.sh
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/linux/vpn/proxyndp.updown
wget -q --no-check-certificate https://raw.githubusercontent.com/6tu/code/master/linux/vpn/strongswan

chmod +x strongswan proxyndp.updown *.sh
dos2unix strongswan proxyndp.updown *.sh

bash ./certs-init.sh
bash ./makecert.sh
/bin/cp -rf  ~/certs/*_cert.crt ./server.cert.pem
/bin/cp -rf  ~/certs/*_csr_nopw.key ./server.pem
/bin/cp -rf  ~/certs/demoCA/cacert.pem ./ca.cert.pem
bash ./quericy-one-key-ikev2.sh

/bin/cp -rf strongswan /etc/init.d/strongswan
update-rc.d strongswan defaults
chkconfig --add strongswan
chkconfig strongswan on

ifc=`ls /sys/class/net`
echo $ifc>~/vpn/ifc.log
/bin/cp -rf  proxyndp.updown /usr/local/etc/strongswan.d/
if egrep "venet0" ~/vpn/ifc.log > /dev/null
then
    sed -i 's/IFACE=eth0/IFACE=venet0/g' /usr/local/etc/strongswan.d/proxyndp.updown
elseif egrep "ens3" ~/vpn/ifc.log > /dev/null
    sed -i 's/IFACE=eth0/IFACE=ens3/g' /usr/local/etc/strongswan.d/proxyndp.updown
else
    sed -i 's/IFACE=eth0/IFACE=eth0/g' /usr/local/etc/strongswan.d/proxyndp.updown
fi

