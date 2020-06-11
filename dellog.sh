#!/usr/bin/env bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

find /var/log -name '*.gz'    -exec rm -rf {} \;
find /var/log -name '*.1'     -exec rm -rf {} \;
find /var/log -name '*-2*'    -exec rm -rf {} \;
find /var/log -name '*.log'   -exec cp /dev/null {} \;
find /var/log -type f         -exec cp /dev/null {} \;
find /var/log -type f         -exec ls {} \;

cp /dev/null /var/log/messages
cp /dev/null /var/log/btmp
cp /dev/null /var/log/wtmp
cp /dev/null /opt/lampp/logs/access_log
cp /dev/null /opt/lampp/logs/error_log
cp /dev/null /opt/lampp/logs/php_error_log
cp /dev/null /opt/lampp/logs/ssl_request_log

journalctl --vacuum-size=10M
rm -rf /var/log/journal/*


du -t 10M /var/log
