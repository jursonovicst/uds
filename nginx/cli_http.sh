#!/bin/bash

start=$(date +%s)
seq 1 10000 |xargs -n 1 -P 50 -I {} curl -d '{"messagetype": "dnsquery","query": {"name": "service.example.com","type": "A","class": "IN"},"clientinfo": {"ip": "1.2.3.4"}}' -H "Host: dnsresponder" -H "Content-Type: application/json" http://127.0.0.1/dnsresponder.php
stop=$(date +%s)

echo ""
echo ""
echo "Performance: $(( 10000 / (stop - start))) request/sec"

