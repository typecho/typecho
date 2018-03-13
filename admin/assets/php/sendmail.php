<?php

// Load PHP Mailer
//
require 'PHPMailer/PHPMailerAutoload.php';
$mail = new PHPMailer;

/*
|--------------------------------------------------------------------------
| Configure your contact form
|--------------------------------------------------------------------------
|
| Set value of '$reciever' to email address that want to receive inquiries.
| Also, '$default_subject' is the subject that you'll see in your inbox.
|
| It's better to set `$sender_email` and `$sender_name` values, so there's
| more chance to receive the email at gmail, yahoo, hotmail, etc.
|
*/
$reciever        = "info@yourdomain.com";
$default_subject = "Email from yoursite.com";

$sender_email    = "noreply@yourdomain.com";
$sender_name     = "YourDomain.com";
$error_message   = "An error occured. Please try again later.";


/*
|--------------------------------------------------------------------------
| Configure PHP Mailer
|--------------------------------------------------------------------------
|
| By default, we're using the default configuration. If you need to change
| default settings or use a custion SMTP server, do it here.
|
| More info: https://github.com/PHPMailer/PHPMailer
|
*/
$mail->isHTML(true);


/*
|--------------------------------------------------------------------------
| Sending email
|--------------------------------------------------------------------------
|
| This part of code is responsible to send the email. So you don't need to
| change anything here.
|
*/

$email = $_POST['email'];
if ( ! empty( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) )
{

  // detect & prevent header injections
  //
  $test = "/(content-type|bcc:|cc:|to:)/i";
  foreach ( $_POST as $key => $val ) {
    if ( preg_match( $test, $val ) ) {
      exit;
    }
  }


  // Sender name
  //
  $name = '';
  if ( isset( $_POST['name'] ) ) {
    $name = $_POST['name'];
  }

  if ( isset( $_POST['firstname'] ) && isset( $_POST['lastname'] ) ) {
    $name = $_POST['firstname'] .' '. $_POST['lastname'];
  }


  // Email subject
  //
  $subject = '';
  if ( isset( $_POST['subject'] ) ) {
    $subject = $_POST['subject'];
  }

  if ($subject == "") {
    $subject = $default_subject;
  }

  if ( ! empty( $name ) ) {
    $subject .= ' - By '. $name;
  }


  // Message content
  //
  $message = '';
  if ( isset( $_POST['message'] ) ) {
    $message = nl2br( $_POST['message'] );
  }


  // Attach other input values to the end of message
  //
  unset( $_POST['subject'], $_POST['message'] );
  $message .= '<br><br><br>';
  foreach ($_POST as $key => $value) {
    $key = str_replace( array('-', '_'), ' ', $key);
    $message .= '<p><b>'. ucfirst($key) .'</b><br>'. nl2br( $value ) .'<p><br><br>';
  }


  // Prepare PHP Mailer
  //
  $mail->setFrom($sender_email, $sender_name);
  $mail->addAddress($reciever);
  $mail->addReplyTo($email, $name);

  $mail->Subject = $subject;
  $mail->Body    = $message;
  $mail->AltBody = strip_tags($message);

  if( ! $mail->send() ) {
    echo json_encode( array(
      'status'  => 'error',
      'message' => $error_message,
      'reason'  => $mail->ErrorInfo,
    ));
  } else {
    echo json_encode( array( 'status' => 'success' ) );
  }

}


?>
