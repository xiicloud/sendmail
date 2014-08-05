#!/usr/bin/env php

<?php

require "phpmailer/PHPMailerAutoload.php";

$mail = new PHPMailer;
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = '127.0.0.1';  // Specify main and backup SMTP servers
$mail->SMTPAuth = false;                               // Enable SMTP authentication

$mail_domain = getenv("MAIL_DOMAIN");
$dp_user = getenv("DP_USER");

function mailsend($from, $fromname, $subject, $msg, $to, $cc="", $bcc="") {
  $mail->From = $from;
  $mail->FromName = $fromname;
  $mail->addAddress($to);
  $mail->addReplyTo($from, $fromname);
  if ($cc != "") $mail->addCC($cc);
  if ($bcc != "") $mail->addBCC($bcc);

  $mail->WordWrap = 50;                                 // Set word wrap to 50 characters

  $mail->Subject = $subject;
  $mail->Body    = $msg;
  $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

  if(!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
  } else {
    echo 'Message has been sent';
  }
}

$msp=array("qq.com", "sina.com", "sohu.com", "126.com", "163.com", "263.com", "gmail.com","21cn.com","yahoo.com", "hotmail.com");
$muser=array("jack", "jhon", "will", "hello", "shenma", "mayun", "huateng", "yanhong", "guowei", "chaoyang", "stevens", "jobs", "richard", "dingning", "mofan", "charles", "david", "sophia", "susan", "mary", "hill", "water", "xiaoli", "xiaowang", "xiaozhang", "xiaohan", "xiaolong", "tianxia", "feng", "yunwu", "world", "tianshen", "yadianna", "shendoushi", "dura", "boots", "power", "trade");

foreach ($msp as $m) {
  foreach ($muser as $u) {
    mailsend("admin@$mail_domain", "admin", "你好，$u ，回到北京了吗？", "hello, $u 听说你在海湾地区搞到了不少油田，是土豪了。恭喜恭喜，什么时候一定要请客呀！ Best Regards! ", "$u@$m");
    sleep(120);
  }
}
