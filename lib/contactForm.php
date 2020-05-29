<?php
if ((isset($_POST['name'])) && (strlen(trim($_POST['name'])) > 0)) {
	$name = stripslashes(strip_tags($_POST['name']));
}
else {
    return;
}

if ((isset($_POST['email'])) && (strlen(trim($_POST['email'])) > 0) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
	$emailFrom = stripslashes(strip_tags($_POST['email']));
}
else
    return;

if ((isset($_POST['message'])) && (strlen(trim($_POST['message'])) > 0)) {
	$message = stripslashes(strip_tags($_POST['message']));
}

if ((isset($_POST['phone'])) && (strlen(trim($_POST['phone'])) > 0)) {
    $phone = stripslashes(strip_tags($_POST['phone']));
}

$body = 'This e-mail is from: <strong>' . $name . '</strong><br />
		 with email: <strong>' . $emailFrom.'</strong><br />';

if (isset($phone)) {
    $body .= '
        with phone number: <strong>' . $phone . '</strong><br />';
}

$body .= $name . ' wrote: <br /><p>'.$message.'</p>';

$address = 'info@paramana.com';
$subject = 'People Contacting';

require_once('class/class.phpmailer.php');

$mail  = new PHPMailer(); // defaults to using php "mail()"

$name = "=?UTF-8?B?" . base64_encode($name) . "?=";
$mail->From     = $emailFrom;
$mail->FromName = $name;
$mail->AddReplyTo($emailFrom, $name);
$mail->SetFrom($emailFrom, $name);

$mail->AddAddress($address, "paramana.com");
$mail->Subject = $subject;
$mail->CharSet = 'UTF-8';
$mail->MsgHTML($body);



if(!$mail->Send()) {
	mail($address, $subject, $body, "From: $emailFrom\r\nReply-To: $emailFrom\r\nX-Mailer: DT_formmail");
	exit;
}
else
	echo "1";
?>
