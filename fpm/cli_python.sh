#!/bin/bash


start=$(date +%s)
seq 1 10000 |xargs -n 1 -P 50 -I {} sh -c 'echo "{\"messagetype\": \"dnsquery\",\"query\": {\"name\": \"service.example.com\",\"type\": \"A\",\"class\": \"IN\"},\"clientinfo\": {\"ip\": \"1.2.3.4\"}}" | SCRIPT_NAME=/dnsresponder.py SCRIPT_FILENAME=/home/cdn/uds/html/dnsresponder.py REQUEST_METHOD=POST SERVER_NAME="" SERVER_PORT="" SERVER_PROTOCOL="" CONTENT_LENGTH=126 cgi-fcgi -bind -connect /var/run/flup/flup.sock'
stop=$(date +%s)

echo ""
echo ""
echo "Performance: $(( 10000 / (stop - start))) request/sec"
