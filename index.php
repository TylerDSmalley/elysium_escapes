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


$app->post('/testbooking', function ($request, $response, $args) {
    /* if ($request->getParam('submit') !== null) {

        $bookingId = DB::insertId();

        $bookingData = DB::queryFirstRow("SELECT * FROM booking_history WHERE id=%i", $bookingId);
        $hotelData = DB::queryFirstRow("SELECT * FROM hotel WHERE confirmation=%i", $bookingData["booking_confirm"]);     
        $location2 = DB::queryFirstField("SELECT destination_name FROM destinations WHERE id=%i", $bookingData["destination_id"]);

        $values = [
            'location' => $location2,
            'adults' => $bookingData["number_adults"],
            'children' => $bookingData["number_children"],
            'arrival' => $bookingData["departure_date"],
            'departure' => $bookingData["return_date"],
            'hotel_name' => $hotelData["hotel_name"],
            'hotel_city' => $hotelData["hotel_city"],
            'hotel_address' => $hotelData["hotel_address"],
            'hotel_currency' => $hotelData["hotel_currency"],
            'price_hotel_currency' => $hotelData["price_hotel_currency"],
            'price_cad' => $hotelData["price_cad"],
            'confirmation' => $bookingData["booking_confirm"]
        ];
        
        return $this->view->render($response, 'testBookingConfirm.html.twig', ['v' => $values]);
        
    } else */ if ($request->getParam('hotel') !== null){
        $hotel = json_decode($request->getParam('hotel'));
        $options = json_decode($request->getParam('options'));

        $hotelName = $hotel->hotel_name;
        $hotelCity = $hotel->city;
        // $hotelCountry = $hotel->country_trans;
        $hotelAddress = $hotel->address;
        $hotelCurrencyCode = $hotel->currency_code;
        $hotelPrice = $hotel->price_breakdown->all_inclusive_price;
        $cadPrice = convertCurrencyToCAD($hotelCurrencyCode, $hotelPrice);

        if ($hotel->composite_price_breakdown->included_taxes_and_charges_amount->value != "0") {
            $hotelTaxChargesTotal = $hotel->composite_price_breakdown->included_taxes_and_charges_amount->value;
        } else {
            $hotelTaxChargesTotal = $hotel->price_breakdown->sum_excluded_raw;
        }

        if ($hotelTaxChargesTotal != 0) {
            $chargesList = [];
            foreach ($hotel->composite_price_breakdown->items as $item) {
                if (property_exists($item, "base") && $item->base->kind != "incalculable") {
                    $chargesList []= $item;
                }
            }
        } else {
            $chargesList = "N/A";
        }

        $chargesList = json_encode($chargesList); // Add charges column to hotel table

        $min = 100000000000000;
        $max = 999999999999999;
        $rand = random_int($min, $max);
        $destinationId = DB::queryFirstField("SELECT id FROM destinations WHERE destination_name=%s", $options->location);
        $dummyHotel = ['destination_id' => $destinationId, 'hotel_name' => $hotelName, 'hotel_city' => $hotelCity, 'hotel_address' => $hotelAddress, 'hotel_currency' => $hotelCurrencyCode, 'price_hotel_currency' => $hotelPrice, 'price_cad' => $cadPrice, 'confirmation' => $rand];
        
        DB::insert('hotel', $dummyHotel);
        $hotelId = DB::insertId();

        $dummyFlight = ['destination_id' => $destinationId, 'flight_name' => "test flight", 'price' => 1, 'confirmation' => $rand];
        DB::insert('flight', $dummyFlight);
        $flightId = DB::insertId();

        $valuesList = [
            'user_id' => $_SESSION['user']['id'],
            'destination_id' => $destinationId,
            'hotel_id' => $hotelId,
            'flight_id' => $flightId,
            'number_adults' => $options->adults,
            'number_children' => $options->children,
            'total_price' => $cadPrice,
            'departure_date' => $options->arrival,
            'return_date' => $options->departure,
            'booking_confirm' => $rand
        ];

        DB::insert('booking_history', $valuesList);
        
        return $this->view->render($response, 'checkout.html.twig', ['hotel' => $hotel, 'options' => $options, 'cad_price' => $cadPrice]);
    } else {
        $location = $request->getParam('location');
        $adults = $request->getParam('adults');
        $children = $request->getParam('children');
        $arrival = $request->getParam('arrival');
        $departure = $request->getParam('departure');
        $destType = "";
        $locationId = searchLocation($location, $destType);
        $hotelList = searchHotels($locationId, $destType, $adults, $children, $arrival, $departure);
        return $this->view->render($response, 'apitestbooking.html.twig', ['options' => ['location' => $location, 'adults' => $adults, 'children' => $children, 'arrival' => $arrival, 'departure' => $departure], 'h' => $hotelList->result]);
    }
    
});

$app->post('/create', function ($request, $response, $args) {
    \Stripe\Stripe::setApiKey('sk_test_51JuPDTKzuA9IpUUKot3YMvv0KCWLD5GXtkRASmhqQ96VrLzHufknH8XmZzTexDcaIiOcmcuGfQpHMQQ5jY6nd0da007T6z1Bi9');


    function calculateOrderAmount(array $items): int {
        // Replace this constant with a calculation of the order's amount
        // Calculate the order total on the server to prevent
        // people from directly manipulating the amount on the client
        // return 1400;
        return $items[0]["total"];
    }


    

    try {
        // retrieve JSON from POST body
        $jsonStr = file_get_contents('php://input');
        $jsonObj = json_decode($jsonStr);
        // print_r($jsonObj);
        // Create a PaymentIntent with amount and currency
         $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => calculateOrderAmount($jsonObj->items),
            'currency' => 'CAD',
            'payment_method_types' => ['card'],
        ]);

        $output = [
            'clientSecret' => $paymentIntent->client_secret,
        ];
        return $response->write(json_encode($output)); 
    } catch (Error $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
});

function callAPI($url, $bookingApi = false) {
	$curl = curl_init($url);

	curl_setopt_array($curl, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_SSL_VERIFYHOST => FALSE,
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: booking-com.p.rapidapi.com",
            "x-rapidapi-key: 68aae21a33msh252600cf6b8ca98p12dc49jsn6f7bf7be6ae4" // testkeyone1@gmail.com // ApiTestingKey#1
        ],
	]);

    if ($bookingApi === true) {
        curl_setopt($curl,CURLOPT_HTTPHEADER , ["x-rapidapi-host: booking-com.p.rapidapi.com",
        "x-rapidapi-key: 68aae21a33msh252600cf6b8ca98p12dc49jsn6f7bf7be6ae4"]);
    }
	
	$response = curl_exec($curl);
	$err = curl_error($curl);
	
	curl_close($curl);
	
	$data = json_decode ( $response);

	return $data;
}

function searchLocation($searchLocation, &$dest_type) {
    $apiUrl = "https://booking-com.p.rapidapi.com/v1/hotels/locations?locale=en-us&name=" . urlencode($searchLocation);

    $locationList = callAPI($apiUrl, true);

    foreach ($locationList as $location) {
        if ($location->name == $searchLocation) {
            $dest_type = $location->dest_type;
            return $location->dest_id; 
        }
    }
}

function searchHotels($location, $destType, $adults, $children, $arrival, $departure) {

    $apiUrl = "https://booking-com.p.rapidapi.com/v1/hotels/search?"
    ."dest_type=" . $destType
    ."&checkin_date=" . $arrival
    ."&room_number=1"
    ."&checkout_date=" . $departure
    ."&order_by=popularity"
    ."&dest_id=" . $location
    ."&adults_number=" . $adults
    ."&units=metric"
    ."&filter_by_currency=CAD"
    ."&locale=en-us"
    ."&include_adjacency=false";
    
    if ($children > 0) {
        $childrenAges = "";
        $apiUrl = $apiUrl . "&children_number=" . $children . "&children_ages=";
        for ($i = $children; $i >= 1; $i--) {
            $apiUrl = $apiUrl . "8";
            $childrenAges = $childrenAges . "8";
            if ($i > 1) {
                $apiUrl = $apiUrl . "%2C";
                $childrenAges = $childrenAges . ",";
            }
        }
    }

    return callAPI($apiUrl, true);
    
}

function convertCurrencyToCAD($sourceCurrencyCode, $convertAmount) {
    $apiUrl = "https://free.currconv.com/api/v7/convert?q=" . $sourceCurrencyCode . "_CAD&compact=ultra&apiKey=05d742f1f2b8ff8dd8c3";
    $result = callAPI($apiUrl);
    return $convertAmount * $result->{array_keys(get_object_vars($result))[0]};
}

//$app->post('/passreset_request', function (Request $request, Response $response) {
    $app->post('/passreset_request', function ( $request, $response) {
    global $log;
    //$view = Twig::fromRequest($request);
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
        $to = $email;
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
            //COULD CALL validatepassword function -> Check if it will work the same
            if ($pass1 != $pass2) {
                array_push($errorList, "Passwords don't match");
            } else {
                $passQuality = validatePasswordQuality($pass1);
                if ($passQuality !== TRUE) {
                    array_push($errorList, $passQuality);
                }
            }
            //
            if ($errorList) {
                return $this->view->render($response, 'password_reset_action.html.twig', ['errorList' => $errorList]);
            } else {
                DB::update('users', ['password' => $pass1], "id=%d", $resetRecord['user_id']);
                DB::delete('password_resets', 'secretCode=%s', $secret); // cleanup the record
                return $this->view->render($response, 'password_reset_action_success.html.twig');
            }
        } else {
            return $this->view->render($response, 'password_reset_action.html.twig');
        }
    });

$app->run();