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

// CALENDAR HANDLERS
$app->get('/trcalendar', function ($request, $response, $args) {
    return $this->view->render($response, 'trcalendar.html.twig');
});
// CALENDAR HANDLERS END

// DESTINATIONS HANDLERS
$app->get('/destinations', function ($request, $response, $args) {
    $destinations = DB::query("SELECT * FROM destinations");
    $images = DB::query("SELECT * FROM images");
    return $this->view->render($response, 'destinations.html.twig', ['destinations' => $destinations, 'images' => $images]);
});
// DESTINATIONS HANDLERS END

// REGISTER HANDLERS
$app->get('/register', function ($request, $response, $args) {
    return $this->view->render($response, 'register.html.twig');
});

$app->post('/register', function ($request, $response, $args) {
    //extract values submitted
    $firstName = $request->getParam('firstName');
    $lastName = $request->getParam('lastName');
    $email = $request->getParam('email');
    $phoneNumber = $request->getParam('phone');
    $password1 = $request->getParam('password1');
    $password2 = $request->getParam('password2');

    //validate

    $errorList = array('firstName' => '', 'lastName' => '', 'email' => '', 'phone' => '', 'password1' => '');

    if (preg_match('/^[\.a-zA-Z0-9,!? ]*$/', $firstName) != 1 || strlen($firstName) < 2 || strlen($firstName) > 100) {
        $errorList['firstName'] = "Name must be between 2 and 100 characters and include only letters, numbers, space, dash, dot or comma";
        $firstName = "";
    }

    if (preg_match('/^[\.a-zA-Z0-9,!? ]*$/', $lastName) != 1 || strlen($lastName) < 2 || strlen($lastName) > 100) {
        $errorList['lastName'] = "Name must be between 2 and 100 characters and include only letters, numbers, space, dash, dot or comma";
        $lastName = "";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorList['email'] = "Invalid email format";
        $email = "";
    } else {

        // check DB for duplicates
        $userRecord = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);
        if ($userRecord) {
            $errorList['email'] = 'Email already taken';
            $email = ""; // reset invalid value to empty string
        }
    }

    if (!validatePhone($phoneNumber)) {
        $errorList['phone'] = "Invalid Phone Number format";
        $phoneNumber = "";
    }

    $valPass = validatePassword($password1, $password2);
    if (!$valPass) {
        $errorList['password1'] = $valPass;
    }

    if (array_filter($errorList)) { //STATE 2: Errors
        $valuesList = ['firstName' => $firstName, 'lastName' => $lastName, 'email' => $email, 'phone' => $phoneNumber];
        return $this->view->render($response, 'register.html.twig', ['errorList' => $errorList, 'v' => $valuesList]);
    } else {
        //$hash = password_hash($password1, PASSWORD_DEFAULT);
        DB::insert('users', ['first_name' => $firstName, 'last_name' => $lastName, 'email' => $email, 'phone_number' => $phoneNumber, 'password' => $password1]);
        return $this->view->render($response, 'login.html.twig');
    }
});

$app->get('/registered', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html.twig');
});
// REGISTER HANDLERS END

// LOGIN HANDLERS
$app->get('/login', function ($request, $response, $args) {
    return $this->view->render($response, 'login.html.twig');
});

$app->post('/login', function ($request, $response, $args) {

    $errors = array('email' => '', 'password1' => '');
    if (isset($_SESSION['user'])) {
        $errors['email'] = "already signed in";
    }
    // check email
    if (empty($request->getParam('email')) || empty($request->getParam('password1'))) {
        $errors['email'] = 'A email and password is required';
    }

    $email = $request->getParam('email');
    $password1 = $request->getParam('password1');
    // verify inputs
    $userCheck = DB::queryFirstRow("SELECT * FROM users WHERE email=%s", $email);

    if (!$userCheck) {
        $errors['email'] = 'Incorrect entry';
        $email = ""; // reset invalid value to empty string
    }

    $loginSuccessful = ($userCheck != null) && ($password1 == $userCheck['password']); 
    //(password_verify($password1, $userCheck['password']));
    if (!$loginSuccessful) { // STATE 2: login failed
        $errors['email'] = 'Invalid email or password';
    } 

    if (array_filter($errors)) {
        return $this->view->render($response, 'login.html.twig', ['errors' => $errors]);
    } else {
        // STATE 3: login successful
        unset($userCheck['password']); // for safety reasons remove the password
        $_SESSION['user'] = $userCheck;
        return $this->view->render($response, 'index.html.twig');
    }
    
    // end POST check   
});

// LOGIN HANDLERS END


// LOGOUT HANDLERS
$app->get('/logout', function ($request, $response, $args) {
    unset($_SESSION['user']);
    return $this->view->render($response, 'logout.html.twig');
});
// LOGOUT HANDLERS END

// $app->get('/profile', function .....);

//SESSION HANDLER 
$app->get('/session', function ($request, $response, $args) {
    $session = print_r($_SESSION);
    return $this->view->render($response, 'session.html.twig', ['session' => $session]);
});
