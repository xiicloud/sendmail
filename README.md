sendmail
========

Setup your mail service like mailgun in one minute at anywhere(home/office/datacenter/cloud/vps).

Programming API: smtp/mailgun-http. 

Support SPF/DKIM

## Requirement

You should own dnspod account and add one top-level domain in dnspod. http://dnspod.cn

## Build
```
docker build -t nicescale/sendmail .
```

## RUN
```
docker run -d --name mta -e MAIL_DOMAIN=mail.example -e DP_USER=xx@example.com -e DP_PASS=123456 nicescale/sendmail
```

- MAIL_DOMAIN

your mail domain for sendmail service. if sender is noreply@mail.nicescale.com, then you should set MAIL_DOMAIN=mail.nicescale.com

- DP_USER

your login user of dnspod.cn

- DP_PASS

your login password of dnspod.cn

- CHECK_INTERVAL

interval time to check if public ip is changed and update it automatically, just ddns function. if set to 0, then no check.


The dnspod api is called over https, dont worry about your password leak.

This docker will setup a MTA service, and set TXT domain record in dnspod.cn automatically.

Further more, we will support dkim/tls/http.

## Usage

Configure smtp host and port(25) in your runtime, then you can sendmail through function mail. or:

```
docker run -d mta:mta nicescale/apache_php
```

then you can get smtp host/port from mta environments in your php.

## Roadmap

- support http api of mailgun
- support tls smtp (587 port)

