# uds

Prototype for c and PHP interface over unix domain socket. Goal is to let a PHP script answer DNS queries ad hoc, therefore a interface is needed between 


```
[host]---DNS lookup-->[resolver]---DNS QUERY-->[bind9]---lookup()-->[DLZ]---lookup()-->[PHP]---geoip()-->[GTS]
```


## srv.php

simulates `[PHP]---geoip()-->[GTS]` with dummy answers.




## Protocol


I am not sure yet, JSON?
``` c
dlz_lookup(const char *zone, const char *name, void *dbdata,
	   dns_sdlzlookup_t *lookup, dns_clientinfomethods_t *methods,
	   dns_clientinfo_t *clientinfo)
    

```


``` json
{
 "messagetype": "dnsquery",
 "query": {
 "name": "service.example.com",
 "type": "A",
 "class": "IN"
},
"clientinfo": {
 "ip": "1.2.3.4"
 }
}
```


``` json
{
	"messagetype": "dnsanswer",
	"answers": [{
		"name": "www.bme.hu",
		"type": "A",
		"class": "IN",
		"ttl": 5,
		"address": "10.1.1.1"
	}, {
		"name": "www.bme.hu",
		"type": "A",
		"class": "IN",
		"ttl": 5,
		"address": "10.1.1.2"
	}]
}```
