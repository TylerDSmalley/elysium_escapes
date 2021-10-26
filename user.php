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

$app->get('/contactus', function ($request, $response, $args) {
    return $this->view->render($response, 'contactus.html.twig');
});

$app->get('/destinations', function ($request, $response, $args) {
    return $this->view->render($response, 'destinations.html.twig');
});

$app->get('/pricing', function ($request, $response, $args) {
    return $this->view->render($response, 'pricing.html.twig');
});

$app->get('/trcalendar', function ($request, $response, $args) {
    return $this->view->render($response, 'trcalendar.html.twig');
});

$app->get('/register',function($request,$response,$args){
    return $this->view->render($response,'register.html.twig');
 });


// $app->get('/register', function .....);

// $app->get('/login', function .....);

// $app->get('/logout', function .....);

// $app->get('/profile', function .....);

//

