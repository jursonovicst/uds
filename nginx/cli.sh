#!/bin/bash

date
seq 1 10000 |xargs -n 1 -P 50 -I {} curl --unix-socket /tmp/dnsresponder.sock -d '{"messagetype": "dnsquery","query": {"name": "service.example.com","type": "A","class": "IN"},"clientinfo": {"ip": "1.2.3.4"}}' -H "Content-Type: application/json" -s dnsresponder.php >/dev/null
date
