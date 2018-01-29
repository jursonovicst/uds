#!/bin/bash

echo '{"messagetype": "dnsquery","query": {"name": "service.example.com","type": "A","class": "IN"},"clientinfo": {"ip": "1.2.3.4"}}' | SCRIPT_NAME=/dnsresponder.php \
SCRIPT_FILENAME=/home/cdn/uds/html/dnsresponder.php \
REQUEST_METHOD=POST \
CONTENT_LENGTH=126 \
cgi-fcgi -bind -connect /var/run/php/php7.0-fpm.sock




#echo '{"messagetype": "dnsquery","query": {"name": "service.example.com","type": "A","class": "IN"},"clientinfo": {"ip": "1.2.3.4"}}' | cgi-fcgi -bind -connect /var/run/php/php7.0-fpm.sock
#/var/log/www.access.log
