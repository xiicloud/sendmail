#!/bin/bash

set -e 

envfile=${CONF_DIR-:/opt/nicedocker/conf}/env

[ -f $envfile ] &&
. $envfile &&
eval `cat $envfile|grep -v ^#|grep '='|cut -f1 -d'='|xargs echo export`

if [ -z "$MAIL_DOMAIN" -o -z "$DP_USER" -o -z "$DP_PASS" ]; then
  echo "you must supply env MAIL_DOMAIN|DP_USER|DP_PASS"
  exit 1
fi

service exim4 start
service nginx start

php dnspod.php

