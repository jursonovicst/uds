#!/bin/bash

date
export DATA='{"messagetype": "dnsquery","query": {"name": "service.example.com","type": "A","class": "IN"},"clientinfo": {"ip": "1.2.3.4"}}'
export CONTENT_LENGTH=${#DATA}
export SCRIPT_FILENAME=$(pwd)/$1
export REQUEST_METHOD=POST
export SERVER_NAME=bind_dlz
export SERVER_PORT=0
export SERVER_PROTOCOL=http
export CONTENT_TYPE="Content-Type: application/json"
seq 1 10000 | xargs -n 1 -P 50 -I {} sh -c "echo -n '$DATA' | cgi-fcgi -bind -connect /tmp/py.sock" > /dev/null
date
