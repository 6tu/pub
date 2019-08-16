#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

test -d /usr/local/cron || mkdir -p /usr/local/cron
cat > /usr/local/cron/sshdeny.sh << "EOF"
#!/bin/bash
DEFINE="1"
cat /var/log/secure|awk '/Failed/{print $(NF-3)}'|sort|uniq -c|awk '{print $2"="$1;}' > /tmp/sshDenyTemp.txt
for i in `cat /tmp/sshDenyTemp.txt`
do
    IP=`echo $i |awk -F= '{print $1}'`
    NUM=`echo $i|awk -F= '{print $2}'`
    if [ $NUM -gt $DEFINE ];
    then
        grep $IP /etc/hosts.deny > /dev/null
        if [ $? -gt 0 ];
        then
            echo $IP>/tmp/ip.txt
            IP=`sed 's/\.[0-9]\.[0-9]*$/\.0\.0\/16/' /tmp/ip.txt`
            echo "sshd:$IP" >> /etc/hosts.deny
        fi
    fi
done
echo > /var/log/secure
rm -rf /tmp/sshDenyTemp.txt
rm -rf /tmp/ip.txt
#echo sshd>> /root/ssh.log
EOF

function Install_cron()
{
    if [ "$PM" = "yum" ]; then
        yum -y install  vixie-cron crontabs
        log=/var/log/secure
        test -d /var/spool/cron || mkdir -p /var/spool/cron
        echo '*/10 * * * * /usr/local/cron/sshdeny.sh > /dev/null 2>&1' >> /var/spool/cron/root
        crontab /var/spool/cron/root
        chmod 600 /var/spool/cron/root
    elif [ "$PM" = "apt" ]; then
        apt -y update
        apt install -y cron
        log=/var/log/auth.log
        sed -i 's/secure/auth.log/g' /usr/local/cron/sshdeny.sh
        test -d /var/spool/cron/crontabs || mkdir -p /var/spool/cron/crontabs
        echo '*/10 * * * * /usr/local/cron/sshdeny.sh > /dev/null 2>&1' >> /var/spool/cron/crontabs/root
        crontab /var/spool/cron/crontabs/root
        chmod 600 /var/spool/cron/crontabs/root
    fi
}

if [ ! -f "/usr/bin/yum" ]; then
    PM=apt
else
    PM=yum
fi

Install_cron;
chmod +x /usr/local/cron/sshdeny.sh

echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
echo "Deny for SSH Cront have added success!"
echo "The task work by 10/min"
echo "If you want to allow one, please delete it from /etc/hosts.deny"
echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++"
