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
  if ($request->getParam('hotel') === null) {
    $location = $request->getParam('location');
    $adults = $request->getParam('adults');
    $children = $request->getParam('children');
    $arrival = $request->getParam('arrival');
    $departure = $request->getParam('departure');
    $childrenAges = "";
    $hotelList = searchHotels($location, $adults, $children, $childrenAges, $arrival, $departure);
    return $this->view->render($response, 'apitestbooking.html.twig', ['options' => ['location' => $location, 'adults' => $adults, 'children' => $children, 'childrenAges' => $childrenAges, 'arrival' => $arrival, 'departure' => $departure], 'h' => $hotelList->result]);
} else {
    $hotel = json_decode($request->getParam('hotel'));
    $options = json_decode($request->getParam('options'));
    // print_r($hotel);
    return $this->view->render($response, 'testBookingConfirm.html.twig', ['options' => $options, 'hotel' => $hotel]);
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
          "x-rapidapi-key: ddadb2b982msh2f5bdc8948a96e5p1d52b9jsn9d517fe644de"
      ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

$data = json_decode ( $response);

return $data;
}

function searchHotels($location, $adults, $children, &$childrenAges, $arrival, $departure) {
    $destinationType = "country";
    switch ($location) {
        case "Bahamas":
            $locationId = 16;
            break;
        case "Bora Bora":
            $destinationType = "city";
            $locationId = 900054086;
            break;
        case "Phuket":
            $destinationType = "city";
            $locationId = -3253342;
            break;
            
        case "Bermuda":
            $locationId = 24;
            break;
        case "Fiji":
            $locationId = 71;
            break;
        case "Mauritius":
            $locationId = 135;
            break;
    }

    // $destinationType = "country"; // get list
    // $orderBy = "popularity"; // get list
    // $numberOfRooms = 1;

    $apiUrl = "https://booking-com.p.rapidapi.com/v1/hotels/search?"
    ."dest_type=" . $destinationType
    ."&checkin_date=" . $arrival
    ."&room_number=1"
    ."&checkout_date=" . $departure
    ."&order_by=popularity"
    ."&dest_id=" . $locationId
    ."&adults_number=" . $adults
    ."&units=metric"
    ."&filter_by_currency=CAD"
    ."&locale=en-us"
    ."&include_adjacency=false";
    
    if ($children > 0) {
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

$app->run();
