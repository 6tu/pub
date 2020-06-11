#!/usr/bin/env bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH
du -t 10M /var/log

find /var/log -name '*.gz'    -exec rm -rf {} \;
find /var/log -name '*.1'     -exec rm -rf {} \;
find /var/log -name '*-2*'    -exec rm -rf {} \;
find /var/log -name '*.log'    -exec touch >  {} \;
find /var/log -type f         -exec ls {} \;

touch > /var/log/messages
touch > /var/log/btmp
touch > /var/log/wtmp
touch > /opt/lampp/logs/access_log
touch > /opt/lampp/logs/error_log
touch > /opt/lampp/logs/php_error_log
touch > /opt/lampp/logs/ssl_request_log

journalctl --vacuum-size=10M
rm -rf /var/log/journal/*

