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

if [ ! -e /opt/nicedocker/dkim.public ]; then
  gen_dkim
fi

service exim4 start
service nginx start

php dnspod.php

function gen_dkim() {
  SELECTOR=`cat /dev/urandom | tr -dc 'a-z' | fold -w 4 | head -n 1`
  cd /etc/exim4
  openssl genrsa -out dkim.private.$SELECTOR 1024
  openssl rsa -in dkim.private.$SELECTOR -out dkim.public.der -pubout -outform DER
  base64 < dkim.public.der > dkim.public.$SELECTOR 
  rm dkim.public.der
  chown root:Debian-exim dkim.private.$SELECTOR
  chmod 440 dkim.private.$SELECTOR
  echo $SELECTOR > /opt/nicedocker/dkim.selector
  cat dkim.public.$SELECTOR > /opt/nicedocker/dkim.public
}
