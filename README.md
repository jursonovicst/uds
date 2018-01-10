# uds

Prototype for c and PHP interface over unix domain socket. Goal is to let a PHP script answer DNS queries ad hoc, therefore a interface is needed between 


```
[host]---DNS lookup-->[resolver]---DNS QUERY-->[bind9]---lookup()-->[DLZ]---lookup()-->[PHP]---geoip()-->[GTS]
```


## srv.php

simulates `[PHP]---geoip()-->[GTS]` with dummy answers.

