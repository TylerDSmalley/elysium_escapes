<?php

require_once 'vendor/autoload.php';

require_once 'init.php';

//display home page
$app->get('/', function ($request, $response, $args) {
    return $this->view->render($response, 'index.html.twig');
});

$app->get('/blog', function ($request, $response, $args) {
    return $this->view->render($response, 'blog.html.twig');
});

$app->get('/booking', function ($request, $response, $args) {
    return $this->view->render($response, 'booking.html.twig');
});

$app->get('/bookingConfirm', function ($request, $response, $args) {
    return $this->view->render($response, 'bookingConfirm.html.twig');
});

// CONTACT US HANDLERS
$app->get('/contactus', function ($request, $response, $args) {
    return $this->view->render($response, 'contactus.html.twig');
});

$app->post('/contactus', function ($request, $response, $args) {
    $first_name = $last_name = $email = $message_body =  "";
    $errors = array('first_name' => '', 'last_name' => '', 'email' => '', 'message_body' => '');

    // check first name
    if (empty($request->getParam('first_name'))) {
        $errors['first_name'] = 'A First Name is required';
    } else {
        $first_name = $request->getParam('first_name');
        if (strlen($first_name) < 2 || strlen($first_name) > 50) {
            $errors['first_name'] = 'First name must be 2-50 characters long';
        } else {
            $finalfirst_name = htmlentities($first_name);
        }
    }

    // check last name
    if (empty($request->getParam('last_name'))) {
        $errors['last_name'] = 'A Last Name is required';
    } else {
        $last_name = $request->getParam('last_name');
        if (strlen($last_name) < 2 || strlen($last_name) > 50) {
            $errors['last_name'] = 'last name must be 2-50 characters long';
        } else {
            $finallast_name = htmlentities($last_name);
        }
    }

    // check email
    if (empty($request->getParam('email'))) {
        $errors['email'] = 'An email is required';
    } else {
        $email = $request->getParam('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email must be a valid email address';
            $email = ""; // reset invalid value to empty string
        } else {
            // escape sql chars
            $finalEmail = htmlentities($email);
        }
    }

    // check message_body
    if (empty($request->getParam('message_body'))) {
        $errors['message_body'] = 'An message is required';
    } else {
        $message_body = $request->getParam('message_body');
        if (strlen($message_body) < 2 || strlen($message_body) > 5000) {
            $errors['message_body'] = 'Message must be 2-5000 characters long';
            $message_body = "";
        } else {
            $finalmessage_body = strip_tags($message_body, "<p><ul><li><em><strong><i><b><ol><h3><h4><h5><span><pre>");
            $finalmessage_body = htmlentities($finalmessage_body);
        }
    }

    if (array_filter($errors)) { //STATE 2 = errors
        $valuesList = ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'message_body' => $finalmessage_body];
        return $this->view->render($response, 'contactus.html.twig', ['errors' => $errors, 'v' => $valuesList]);
    } else {
        // STATE 3: submission successful
        // insert the record and inform user

        //save to db and check
        DB::insert('contact_us', [
            'first_name' => $finalfirst_name,
            'last_name' => $finallast_name,
            'email' => $finalEmail,
            'message_body' => $finalmessage_body
        ]);
        return $this->view->render($response, 'contactus.html.twig');
    } // end POST check
});

// CONTACT US HANDLERS END
$app->get('/trcalendar', function ($request, $response, $args) {
    return $this->view->render($response, 'trcalendar.html.twig');
});

$app->get('/register', function ($request, $response, $args) {
    return $this->view->render($response, 'register.html.twig');
});

// DESTINATIONS HANDLERS
$app->get('/destinations', function ($request, $response, $args) {
    $destinations = DB::query("SELECT * FROM destinations");
    $images = DB::query("SELECT * FROM images");
    return $this->view->render($response, 'destinations.html.twig', ['destinations' => $destinations, 'images' => $images]);
});
// DESTINATIONS HANDLERS END

// $app->get('/register', function .....);

// $app->get('/login', function .....);

// $app->get('/logout', function .....);

// $app->get('/profile', function .....);

//
