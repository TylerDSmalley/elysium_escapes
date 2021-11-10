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

$app->get('/testbooking', function ($request, $response, $args) {
    $destinations = DB::query("SELECT destination_name, destination_imagepath FROM destinations");
    return $this->view->render($response, 'testbooking.html.twig', ['d' => $destinations]);
});


$app->post('/testbooking', function ($request, $response, $args) {
    if ($request->getParam('submit') !== null) {
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
            'user_id' => 1,
            'destination_id' => $destinationId,
            'hotel_id' => $hotelId,
            'flight_id' => $flightId,
            'number_adults' => $options->adults,
            'number_children' => $options->children,
            'total_price' => $hotelPrice,
            'departure_date' => $options->arrival,
            'return_date' => $options->departure,
            'booking_confirm' => $rand
        ];

        DB::insert('booking_history', $valuesList);

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
        
        return $this->view->render($response, 'testBookingConfirm2.html.twig', ['v' => $values]);
        
    } else if ($request->getParam('hotel') !== null){
        $hotel = json_decode($request->getParam('hotel'));
        $options = json_decode($request->getParam('options'));
        $cadPrice = convertCurrencyToCAD($hotel->currency_code, $hotel->price_breakdown->all_inclusive_price);
        
        return $this->view->render($response, 'testpayment.html.twig', ['hotel' => $hotel, 'options' => $options, 'cad_price' => $cadPrice]);
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

function callAPI($url) {
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
	
	$response = curl_exec($curl);
	$err = curl_error($curl);
	
	curl_close($curl);
	
	$data = json_decode ( $response);

	return $data;
}

function searchLocation($searchLocation, &$dest_type) {
    $apiUrl = "https://booking-com.p.rapidapi.com/v1/hotels/locations?locale=en-us&name=" . urlencode($searchLocation);

    $locationList = callAPI($apiUrl);

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

    return callAPI($apiUrl);
    
}

function convertCurrencyToCAD($sourceCurrencyCode, $convertAmount) {
    function callAPI2($url) {
        $curl = curl_init($url);
    
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_SSL_VERIFYHOST => FALSE
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        $data = json_decode ( $response);
    
        return $data;
    }
    $apiUrl = "https://free.currconv.com/api/v7/convert?q=" . $sourceCurrencyCode . "_CAD&compact=ultra&apiKey=05d742f1f2b8ff8dd8c3";
    $result = callAPI2($apiUrl);
    return $convertAmount * $result->{array_keys(get_object_vars($result))[0]};
}

$app->run();
