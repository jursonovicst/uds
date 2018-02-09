# uds

Prototype for c and PHP/Python interface. Goal is to let a script answer DNS queries ad hoc. 


client side:
```
[host]---DNS lookup-->[resolver]---DNS QUERY-->[bind9]---lookup()-->[DLZ]
```

server side with fastcgi/uds:
```
[DLZ]---fastcgi/uds-->[php-fpm]---php-internal-->[php script]-+
                    |                                         |
		    +>[python script with flup.server.fcgi]---+-rest-->[GTS]
```

server side with http:
```
[DLZ]---HTTP-->[nginx]---fastcgi/uds-->[php-fpm]---php-internal-->[php script]--+
                                    |                                           |
		                    +->[python script with flup.server.fcgi]----+rest-->[GTS]
```


## links

bind: http://bind-dlz.sourceforge.net/

dlz source: https://sourceforge.net/projects/bind-dlz/files/Bind%20DLZ/DLZ-0.7.0/ (sdlz_helper.txt and sdlz_interface.txt)


## Protocol

The scripts (both PHP and Python) returns an HTTP response structure with a json included:

```
Status: <HTTP response code> <HTTP response string>
Content-Type: application/json
Content-Length: <length of the json>

<json>
```


## json specification

``` c
dlz_lookup(const char *zone, const char *name, void *dbdata,
	   dns_sdlzlookup_t *lookup, dns_clientinfomethods_t *methods,
	   dns_clientinfo_t *clientinfo)
    

```

### lookup with clientipinfo
```json
{
	"messagetype": "lookup",
	"lookup": {
		"zone": "example.com",
		"name": "service"
	},
	"clientinfo": {
		"type": "ipv4|ipv6",
		"sourceip": "1.2.3.4"
	}
}
```

### lookup without clientipinfo
```json
{
	"messagetype": "lookup",
	"lookup": {
		"zone": "example.com",
		"name": "service"
	},
	"clientinfo": null
}
```

### resource record format

```json
{
	"messagetype": "resourcerecords",
	"rrs": [{
			"name": "1.2.3.4",
			"type": "A",
			"ttl": 300
		},
		{
			"name": "2001:0db8:85a3:0000:0000:8a2e:0370:7334",
			"type": "AAAA",
			"ttl": 300
		},
		{
			"name": "this.is.the.right.fqdn",
			"type": "CNAME",
			"ttl": 300
		}
	]
}
```


