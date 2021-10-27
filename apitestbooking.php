<?php

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
	
	$data = json_decode ( $response );

	return $data;
}

/*
    include_adjacency
    units
    filter_by_currency
    locale
    page_number
    categories_filter_ids
*/

function searchHotels($locationId) {
    // $destinationType = "country"; // get list
    $checkInDate = "2021-11-10";
    $checkOutDate = "2021-11-20";
    // $orderBy = "popularity"; // get list
    // $numberOfRooms = 1;
    $numberOfAdults = 2;
    $numberOfChildren = 1;
    $childrenAges = urlencode("5,0"); // %2C == , // "5%2C0" // urlencode("5,0")

    $apiUrl = "https://booking-com.p.rapidapi.com/v1/hotels/search?"
    ."dest_type=country"
    ."&checkin_date=" . $checkInDate
    ."&room_number=1"
    ."&checkout_date=" . $checkOutDate
    ."&order_by=popularity"
    ."&dest_id=" . $locationId
    ."&adults_number=" . $numberOfAdults
    ."&units=metric"
    ."&filter_by_currency=CAD"
    ."&locale=en-us"
    ."&children_ages=" . $childrenAges
    ."&include_adjacency=false"
    ."&children_number=" . $numberOfChildren;

    $hotelData = callAPI($apiUrl);

    echo "Hotel Data" . "<br><br>";

    // echo "Search results for \"" . $location . "\"" . "<br><br><br>";

    foreach($hotelData->result as $hotel) {
        
        $isAvailable = null;
        
        if ($hotel->accommodation_type_name == "Resort") {
            if ($hotel->soldout == 0) {
                $isAvailable = "yes";
            }
            else if ($hotel->soldout == 1) {
                $isAvailable = "no";
            }
            echo "<img src=" . $hotel->max_photo_url . " width='300px'>" . "<br>";
            echo "Hotel Name: " . $hotel->hotel_name . "<br>";
            echo "Road: " . $hotel->address . "<br>";
            echo "City: " . $hotel->city . "<br>";
            // echo "Country: Country name" . "<br>";
            echo "Price: " . $hotel->price_breakdown->all_inclusive_price . "<br>";
            echo "Available?: " . $isAvailable . "<br><br><br>";
        }
    }
    
    return ;
}
$mauritius = 135;
$fiji = 71;
$bahamas = 16;

echo searchHotels($bahamas);


