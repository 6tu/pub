
find /var/log -name '*.gz'    -exec rm -rf {} \;
find /var/log -name '*.1'     -exec rm -rf {} \;
find /var/log -name '*-2*'    -exec rm -rf {} \;
find /var/log -type f         -exec ls {} \;
touch > /var/log/messages
touch > /opt/lampp/logs/access_log
touch > /opt/lampp/logs/error_log
touch > /opt/lampp/logs/php_error_log
touch > /opt/lampp/logs/ssl_request_log

