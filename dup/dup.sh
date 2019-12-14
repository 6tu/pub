#!/bin/sh

User=daemon
Group=daemon
duppath=`pwd`

clear
echo -e '\n  在下面输入待处理的目录以便设置属性\n'
echo -n "  输入绝对路径,如 /path/to/target:"
read path
owner=$(ls -l ${path}|sed -n '2p' |awk -F " " '{print $3}')
group=$owner
path=${path}-update

/opt/lampp/bin/php -f $duppath/duplicate-2017.3.29.php

chown -R $owner:$group $path
chmod -R 755 $path

