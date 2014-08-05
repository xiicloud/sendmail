from ubuntu:14.04
run apt-get update
run DEBIAN_FRONTEND=noninteractive apt-get -y install exim4-daemon-light nginx php5-cli php5-fpm php5-curl php5-json git python
run sed -i -e "s/dc_local_interfaces=.*/dc_local_interfaces=''/" /etc/exim4/update-exim4.conf.conf && update-exim4.conf
run git clone https://github.com/Synchro/PHPMailer.git /opt/nicedocker/phpmailer

add . /opt/nicedocker
workdir /opt/nicedocker

expose 25 80

cmd ["/opt/nicedocker/run.sh"]
