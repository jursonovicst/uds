#!/bin/bash

SCRIPT_NAME=/status \
SCRIPT_FILENAME=/status \
REQUEST_METHOD=GET \
cgi-fcgi -bind -connect /var/run/php/php7.0-fpm.sock

