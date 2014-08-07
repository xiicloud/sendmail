#!/bin/bash

set -e 

envfile=${CONF_DIR-:/opt/nicedocker/conf}/env

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

  cat <<EOF > /etc/exim4/conf.d/main/00_exim4-config_localmacros
DKIM_CANON = relaxed
DKIM_DOMAIN = $MAIL_DOMAIN
DKIM_PRIVATE_KEY = /etc/exim4/dkim.private.$SELECTOR
DKIM_SELECTOR = $SELECTOR
EOF

}

function setup_exim() {
  eximconf=/etc/exim4/update-exim4.conf.conf
  sed -i -e "s/dc_eximconfig_configtype=.*/dc_eximconfig_configtype='internet'/" $eximconf
  sed -i -e "s/dc_other_hostnames=.*/dc_other_hostnames='$MAIL_DOMAIN'/" $eximconf
  sed -i -e "s/dc_use_split_config=.*/dc_use_split_config='true'/" $eximconf
}

[ -f $envfile ] &&
. $envfile &&
eval `cat $envfile|grep -v ^#|grep '='|cut -f1 -d'='|xargs echo export`

if [ -z "$MAIL_DOMAIN" -o -z "$DP_USER" -o -z "$DP_PASS" ]; then
  echo "you must supply env MAIL_DOMAIN|DP_USER|DP_PASS"
  exit 1
fi

if [ ! -e /opt/nicedocker/dkim.public -o ! -e /opt/nicedocker/dkim.selector ]; then
  gen_dkim
  setup_exim
  update-exim4.conf
fi

exim_pid=`ps ax|grep exi[m]`
if [ -n "$exim_pid" ]; then
  echo $exim_pid|cut -c1-5|xargs kill
fi

exec /opt/nicedocker/dnspod.php
