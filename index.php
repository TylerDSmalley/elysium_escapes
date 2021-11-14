<?php
date_default_timezone_set('America/Montreal');

require_once 'vendor/autoload.php';
require_once 'init.php';
require_once 'utils.php';

// Define app routes below
require_once 'user.php';
require_once 'admin.php';

// Run app - must be the last operation
// if you forget it all you'll see is a blank page






$app->post('/webhook', function ($request, $response, $args) {
    $endpoint_secret = 'whsec_kyc8SMQpVojIE3mFk6xAkcvSMxwM8sUY';
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $event = null;

    try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
    } catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
    }

    // Handle the event
    switch ($event->type) {
    case 'payment_intent.succeeded':
        $paymentIntent = $event->data->object;
        DB::update('booking_history', ['payment_status' => "paid"], "id=%i", $paymentIntent->description);
        echo 'Received payment of ' . $paymentIntent->amount . ' Booking Id: ' . $paymentIntent->description;
        break;
        
    case 'payment_intent.payment_failed':
        $paymentIntent = $event->data->object;
        DB::update('booking_history', ['payment_status' => "failed"], "id=%i", $paymentIntent->metadata->testId);
        echo 'Failed payment' . ' Booking Id: ' . $paymentIntent->metadata->testId;
        break;
    // ... handle other event types
    default:
        echo 'Received unknown event type ' . $event->type;
    }

    http_response_code(200);
});



    $app->post('/passreset_request', function ( $request, $response) {
    global $log;
    $post = $request->getParsedBody();
    $email = filter_var($post['email'], FILTER_VALIDATE_EMAIL); // 'FALSE' will never be found anyway
    $user = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
    if ($user) { // send email
        $secret = generateRandomString(60);
        $dateTime = gmdate("Y-m-d H:i:s"); // GMT time zone
        DB::insertUpdate('password_resets', ['user_id' => $user['id'],'secretCode' => $secret,'createdTS' => $dateTime], 
        ['secretCode' => $secret, 'createdTS' => $dateTime]);
        //
        // primitive template with string replacement
        $emailBody = file_get_contents('templates/password_reset_email.html.strsub');
        $emailBody = str_replace('EMAIL', $email, $emailBody);
        $emailBody = str_replace('SECRET', $secret, $emailBody);
        /* // OPTION 1: PURE PHP EMAIL SENDING - most likely will end up in Spam / Junk folder */
        $to = $email['email'];
        $subject = "Password reset";
        // Always set content-type when sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // More headers
        $headers .= 'From: No Reply <noreply@travel.fsd01.ca>' . "\r\n";
        // finally send the email
        $result = mail($to, $subject, $emailBody, $headers);
        if ($result) {
            $log->debug(sprintf("Password reset sent to %s", $email));
        } else {
            $log->error(sprintf("Error sending password reset email to %s\n:%s", $email));
        } 
    }
        return $this->view->render($response, 'password_reset_sent.html.twig');
    });

    $app->map(['GET', 'POST'], '/passresetaction/{secret}', function ( $request, $response, array $args) {
        global $log;
        //$view = Twig::fromRequest($request);
        // this needs to be done both for get and post
        $secret = $args['secret'];
        $resetRecord = DB::queryFirstRow("SELECT * FROM password_resets WHERE secretCode=%s", $secret);
        if (!$resetRecord) {
            $log->debug(sprintf('password reset token not found, token=%s', $secret));
            return $this->view->render($response, 'password_reset_action_notfound.html.twig');
        }
        // check if password reset has not expired
        $creationDT = strtotime($resetRecord['createdTS']); // convert to seconds since Jan 1, 1970 (UNIX time)
        $nowDT = strtotime(gmdate("Y-m-d H:i:s")); // current time GMT
        if ($nowDT - $creationDT > 60*60) { // expired
            DB::delete('password_resets', 'secretCode=%s', $secret);
            $log->debug(sprintf('password reset token expired user_id=%s, token=%s', $resetRecord['user_id'], $secret));
            return $this->view->render($response, 'password_reset_action_notfound.html.twig');
        }
        // 
        if ($request->getMethod() == 'POST') {
            $post = $request->getParsedBody();
            $pass1 = $post['pass1'];
            $pass2 = $post['pass2'];
            $errorList = array();

            $result = validatePassword($pass1,$pass2);
            if($result !== TRUE){
                $errorList = $result;
            }
            //COULD CALL validatepassword function -> Check if it will work the same
           // if ($pass1 != $pass2) {
            //    array_push($errorList, "Passwords don't match");
            //} else {
            //    $passQuality = validatePasswordQuality($pass1);
             //   if ($passQuality !== TRUE) {
             //       array_push($errorList, $passQuality);
              //  }
           // }
            //
            if ($errorList) {
                return $this->view->render($response, 'password_reset_action.html.twig', ['errorList' => $errorList]);
            } else {
                $hash = password_hash($pass1, PASSWORD_DEFAULT);
                DB::update('users', ['password' => $hash], "id=%d", $resetRecord['user_id']);
                DB::delete('password_resets', 'secretCode=%s', $secret); // cleanup the record
                return $this->view->render($response, 'password_reset_action_success.html.twig');
            }
        } else {
            return $this->view->render($response, 'password_reset_action.html.twig');
        }
    });

$app->run();