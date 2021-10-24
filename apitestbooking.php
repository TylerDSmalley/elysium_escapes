<?php

/*
    Nassau, Bahamas
    Suva, Fiji
    Honolulu, Hawaii
*/

echo getHotelData("Nassau");

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

function callLocationAPI($url) {
	$curl = curl_init($url);

	curl_setopt_array($curl, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_SSL_VERIFYHOST => FALSE,
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: forward-reverse-geocoding.p.rapidapi.com",
            "x-rapidapi-key: ddadb2b982msh2f5bdc8948a96e5p1d52b9jsn9d517fe644de"
        ],
	]);
	
	$response = curl_exec($curl);
	$err = curl_error($curl);
	
	curl_close($curl);
	
	$data = json_decode ( $response );

	return $data;
}

function searchLocations($location) {
    $baseUrl = "https://booking-com.p.rapidapi.com/v1/hotels/locations?locale=en-gb&name=";
    $locationList = callAPI($baseUrl . $location);

    foreach($locationList as $locationResult) {
        if ($locationResult->name == $location) {
            return $locationResult->dest_id;
        }
    }
}

function searchHotels($locationId) {
    $apiUrl = "https://booking-com.p.rapidapi.com/v1/hotels/search?dest_type=city&checkin_date=2022-07-24&room_number=1&checkout_date=2022-07-25&order_by=popularity&dest_id=" . $locationId . "&adults_number=2&units=metric&filter_by_currency=AED&locale=en-gb&children_ages=5%2C0&include_adjacency=true&page_number=0&categories_filter_ids=class%3A%3A2%2Cclass%3A%3A4%2Cfree_cancellation%3A%3A1&children_number=2";
    
    return callAPI($apiUrl);
}

function getHotelData($location) {
    $hotelData = searchHotels(searchLocations($location));

    echo "Hotel Data" . "<br><br>";

    echo "Search results for \"" . $location . "\"" . "<br><br><br>";

    foreach($hotelData->result as $hotel) {
        $locationData = callLocationAPI("https://forward-reverse-geocoding.p.rapidapi.com/v1/reverse?lat=" . $hotel->latitude . "&lon=" . $hotel->longitude . "&accept-language=en&polygon_threshold=0.0");
    
        $isAvailable = null;
    
        if ($hotel->soldout == 0) {
            $isAvailable = "yes";
        }
        else if ($hotel->soldout == 1) {
            $isAvailable = "no";
        }
    
        echo "Hotel Name: " . $hotel->hotel_name . "<br>";
        echo "Road: " . $locationData->address->road . "<br>";
        echo "City: " . $locationData->address->city . "<br>";
        echo "Country: " . $locationData->address->country . "<br>";
        echo "Price: " . $hotel->price_breakdown->all_inclusive_price . "<br>";
        echo "Available?: " . $isAvailable . "<br><br><br>";
    }
}




