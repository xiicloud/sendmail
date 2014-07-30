#!/usr/bin/env php

<?php
$mail_domain = getenv("MAIL_DOMAIN");
$dp_user = getenv("DP_USER");
$dp_pass = getenv("DP_USER");
$interval = getenv("CHECK_INTERVAL");
if($interval == null) $interval = 550;

if (! $mail_domain or ! $dp_user or ! $dp_pass)
  die ("you must supply enviroments: MAIL_DOMAIN, DP_USER, DP_PASS");

$base_domain = get_basedomain($mail_domain);
if ($base_domain == $mail_domain) {
  $sub_domain = '@';
} else {
  $sub_domain = str_ireplace($base_domain,"", $mail_domain);
  $sub_domain = trim($sub_domain, '.');
}

function get_basedomain($domain) {
  if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
    return $regs['domain'];
  } 
  return false;
}

function get_ip() {
  $fp = fsockopen("ns1.dnspod.net", 6666, $errno, $errstr, 30);
  $ip = null;
  if (!$fp) {
    echo "$errstr ($errno)";
    return null;
  } else {
    $out = "\n";
    fwrite($fp, $out);
    $ip = fgets($fp, 128);
    fclose($fp);
  }
  return $ip;
}

function curl_get($url, $data) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}

function pdie($msg) {
  echo $msg."\n";
  sleep($interval);
}

echo "check domain list ...";
$domain_list_url = 'https://dnsapi.cn/Domain.List';
$data_domlist = array (
'login_email'=>$dp_user,
'login_password'=>$dp_pass,
"format"=>"json" );
$domlist = curl_get($domain_list_url, $data_domlist);
$domlist || die ("failed to get domain list.");
$domlist_arr = json_decode($domlist, true);
if (!$domlist_arr or $domlist_arr['status']['code'] != 1)
  die("error when parse the domain list return value.");
$domain_id = null;
foreach ($domlist_arr["domains"] as $d) {
  if($d['name'] == $base_domain) {
    $domain_id = $d['id'];
    break;
  }
}
if ($domain_id) {
  echo " OK.\n";
} else {
  echo " NO.\n";
  die("domain $base_domain not create in your dnspod.");
}

echo "check record list ... ";
$record_list_url = 'https://dnsapi.cn/Record.List';
$data_reclist = array (
'login_email'=>$dp_user,
'login_password'=>$dp_pass,
"format"=>"json",
'domain_id'=>$domain_id );
$reclist = curl_get($record_list_url, $data_reclist);
$reclist || die ("failed to get record list for domain $base_domain");
$reclist_arr = json_decode($reclist, true);
if (!$reclist_arr or $reclist_arr['status']['code'] != 1)
  die("error when parse the record list return value.");

echo "OK\n";

$record_id = null;
$record_value = null;
foreach ($reclist_arr['records'] as $r) {
  if ($r['name'] == $sub_domain && $r['type'] == 'TXT') {
    $record_id = $r['id']; 
    $record_value = $r['value'];
    break;
  }
}

echo "get public ip...";
$localip = get_ip();
$localip ||  die("failed to get your public ip.");
echo $localip . "\n";
$txtvalue = "v=spf1 ip4:$localip -all";

if (!$record_id) {
  echo "record $sub_domain type txt not existed yet.\n";
  echo "create record $sub_domain type txt ... ";
  $record_create_url = 'https://dnsapi.cn/Record.Create';
  $data_record_create = array (
  'login_email'=>$dp_user,
  'login_password'=>$dp_pass,
  "format"=>"json",
  'domain_id'=>$domain_id,
  'sub_domain'=>$sub_domain,
  'record_type'=>'TXT',
  'record_line'=>"默认",
  'value'=>$txtvalue );
  $rc_result= curl_get($record_create_url, $data_record_create);
  $rc_result || die ("failed to create txt record for domain $mail_domain.");
  $rcr_arr = json_decode($rc_result, true);
  if (!$rcr_arr or $rcr_arr['status']['code'] != 1)
    die ("error when parse the record create return value");
  echo "OK\n";
  $record_id = $rcr_arr['record']['id'];
  echo "you can run 'dig txt $mail_domain' to verify in linux shell.";
} 


echo "begin while loop ...\n";
while ( true ) {
  sleep($interval);
  echo "get public ip ... ";
  $localip = get_ip();
  $localip || pdie("failed to get your public ip.");
  $txtvalue = "v=spf1 ip4:$localip -all";
  echo $localip . "\n";
  echo "check record info for domain $mail_domain ... "; 
  $record_info_url = "https://dnsapi.cn/Record.Info";
  $data_record_info = array (
  'login_email'=>$dp_user,
  'login_password'=>$dp_pass,
  "format"=>"json",
  'domain_id'=>$domain_id,
  'record_id'=>$record_id
  );
  $ri_result=curl_get($record_info_url, $data_record_info);
  $ri_result || pdie("failed to get record info for id $record_id of sub_domain $sub_domain.");
  $rir_arr = json_decode($ri_result, true);
  if (!$rir_arr or $rir_arr['status']['code'] != 1)
    pdie ("error when parse the record info return value");
  $record_value = $rir_arr['record']['value'];

  // if record value contains local ip , then continue,not modify
  if(preg_match("/$localip/", $record_value)) {
    echo "record value:'$record_value', contains ip:$localip.\n";
    continue;
  }

  echo "public ip changed, should change dns to $localip ... ";
  $record_modify_url = "https://dnsapi.cn/Record.Modify";
  $data_record_modify = array (
  'login_email'=>$dp_user,
  'login_password'=>$dp_pass,
  "format"=>"json",
  'domain_id'=>$domain_id,
  'record_id'=>$record_id,
  'sub_domain'=>$sub_domain,
  'value'=>$txtvalue,
  'record_type'=>'TXT',
  'record_line'=>"默认"
  );
  $rm_result = curl_get($record_modify_url, $data_record_modify);
  $rm_result || pdie("failed to modify txt record for domain $mail_domain.");
  $rmr_arr = json_decode($rm_result, true);
  if (!$rmr_arr or $rmr_arr['status']['code'] != 1)
    pdie ("error when parse the record modify return value");
  echo "OK\n";
}
