# uds

Prototype for c and PHP interface over unix domain socket. Goal is to let a PHP script answer DNS queries ad hoc, therefore a interface is needed between 

direkt PHP server:
```
[host]---DNS lookup-->[resolver]---DNS QUERY-->[bind9]---lookup()-->[DLZ]---lookup()-->[PHP]---geoip()-->[GTS]
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


```json
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


```json
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





``` c
typedef isc_result_t
(*dns_sdlzlookupfunc_t)(	const char *zone,					--example.com
const char *name,					--service
					void *driverarg,						--argument will be returned unmodified to the callback
				void *dbdata,						-- may be used to pass back a "view specific" memory structure for use later in the various callback methods

dns_sdlzlookup_t *lookup,				--
dns_clientinfomethods_t *methods,		--
dns_clientinfo_t *clientinfo			--
);







dlz_lookup(const char 			*zone					example.com
const char 			*name					service
void 					*dbdata				argument will be returned unmodified to the callback
dns_sdlzlookup_t 		*lookup
struct dns_sdlzlookup {					structdns__sdlzlookup.html

        unsigned int				magic;	was ist das?
        dns_sdlz_db_t				*sdlz;	was ist das?
        ISC_LIST(dns_rdatalist_t)	lists;	was ist das?
        ISC_LIST(isc_buffer_t)		buffers;	was ist das?
        dns_name_t				*name;	was ist das?
        ISC_LINK(dns_sdlzlookup_t)	link;	was ist das?
        isc_mutex_t				lock;	was ist das?
        dns_rdatacallbacks_t		callbacks; was ist das?
        unsigned int				references;was ist das?
};
dns_clientinfomethods_t	*methods
	typedef struct dns_clientinfomethods {
uint16_t					version;	was ist das?
uint16_t					age;		was ist das?
dns_clientinfo_sourceip_t		sourceip;
} dns_clientinfomethods_t;
dns_clientinfo_t			*clientinfo)
    



struct dns_sdlzlookup {
        /* Unlocked */
        unsigned int                    magic;
        dns_sdlz_db_t                   *sdlz;
        ISC_LIST(dns_rdatalist_t)       lists;
        ISC_LIST(isc_buffer_t)          buffers;
        dns_name_t                      *name;
        ISC_LINK(dns_sdlzlookup_t)      link;
        isc_mutex_t                     lock;
        dns_rdatacallbacks_t            callbacks;
        /* Locked */
        unsigned int                    references;
};
```
