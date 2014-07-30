sendmail
========

setup your own send mail service in one minute, support: mail command/mail function/mailgun http. 

## Requirement

You should own dnspod account and add one top-level domain in dnspod. http://dnspod.cn

## RUN
```
docker run -d --name mta -e MAIL_DOMAIN=mail.example -e DP_USER=xx@example.com -e DP_PASS=123456 nicescale/sendmail
```

MAIL_DOMAIN is your mail domain for sendmail service.

DP_USER is your login user of dnspod.cn

DP_PASS is your login password of dnspod.cn

This docker will setup a MTA service, and set TXT domain record in dnspod.cn.

## Usage

Configure smtp host and port(25) in your runtime, then you can sendmail through function mail. or:
```
docker run -d mta:mta nicescale/apache_php
```
then you can get MTA related environments in your php.

## Roadmap

- support dkim
- support http api of mailgun
