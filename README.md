# uds

Prototype for c and PHP interface over unix domain socket. Goal is to let a PHP script answer DNS queries ad hoc, therefore a interface is needed between 

direkt PHP server:
```
[host]---DNS lookup-->[resolver]---DNS QUERY-->[bind9]---lookup()-->[DLZ]

[dlz]---fastcgi/uds-->[php-fpm]---php-internal-->[php script]
                   |
		   +->[python script with]

---lookup()-->[PHP]---geoip()-->[GTS]
```

over nginx:
```
[host]---DNS lookup-->[resolver]---DNS QUERY-->[bind9]---lookup()-->[DLZ]---lookup()-->[nginx]--fastcgi-->[PHP]---geoip()-->[GTS]
```


## links

bind: http://bind-dlz.sourceforge.net/

dlz source: https://sourceforge.net/projects/bind-dlz/files/Bind%20DLZ/DLZ-0.7.0/ (sdlz_helper.txt and sdlz_interface.txt)


## Protocol


I am not sure yet, JSON?
``` c
dlz_lookup(const char *zone, const char *name, void *dbdata,
	   dns_sdlzlookup_t *lookup, dns_clientinfomethods_t *methods,
	   dns_clientinfo_t *clientinfo)
    

```

lookup with clientipinfo
```json
{
	"messagetype": "sdlzlookup",
	"lookup": {
		"zone": "example.com",
		"name": "service",
		"type": "A|AAAA|ANY|etc...",
		"class": "IN"
	},
	"clientinfo": {
		"type": "ipv4|ipv6",
		"sourceip": "1.2.3.4"
	}
}
```

lookup without clientipinfo
```json
{
	"messagetype": "sdlzlookup",
	"lookup": {
		"zone": "example.com",
		"name": "service",
		"type": "A|AAAA|ANY|etc...",
		"class": "IN"
	},
	"clientinfo": null
}
```



```json
{
	"messagetype": "resourcerecords",
	"rrs": [{
			"name": "1.2.3.4|2001:0db8:85a3:0000:0000:8a2e:0370:7334|this.is.the.right.fqdn.example.com|etc...",
			"type": "A|AAAA|CNAME|etc...",
			"class": "IN",
			"ttl": 300
		},
		{
			"name": "1.2.3.4|2001:0db8:85a3:0000:0000:8a2e:0370:7334|this.is.the.right.fqdn.example.com|etc...",
			"type": "A|AAAA|CNAME|etc...",
			"class": "IN",
			"ttl": 300
		}
	]
}
```


