#!/usr/bin/env php

<?php

$from=$argv[1];
$sender=$argv[1];
$to=$argv[2];
$subject=$argv[3];
$msg = fgets(STDIN);

mail ( $to, $subject, $msg, "FROM: $sender\nReply-to: $sender" );
