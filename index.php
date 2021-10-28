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

$app->post('/register',function($request,$response,$args){
    //extract values submitted
  $firstName = $request->getParam('firstName');
  $lastName = $request->getParam('lastName');
  $email = $request->getParam('email');
  $phoneNumber = $request->getParam('phone');
  $password1 = $request->getParam('password1');
  $password2 = $request->getParam('password2');
  
  //validate
  
   $errorList =[];
 
if(preg_match('/^[\.a-zA-Z0-9,!? ]*$/',$firstName) != 1 || strlen($firstName) < 2 || strlen($firstName)> 100)
   {
       $errorList[]="Name must be between 2 and 100 characters and include only letters, numbers, space, dash, dot or comma";
       $firstName = "";
   }

   if(preg_match('/^[\.a-zA-Z0-9,!? ]*$/',$lastName) != 1 || strlen($lastName) < 2 || strlen($lastName)> 100)
   {
       $errorList[]="Name must be between 2 and 100 characters and include only letters, numbers, space, dash, dot or comma";
       $lastName = "";
   }

   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       $errorList[] = "Invalid email format";
       $email ="";
     }

     if (!validatePhone($phoneNumber)) {
        $errorList[] = "Invalid Phone Number format";
        $phoneNumber ="";
      }

      $valPass = validatePassword($password1,$password2);
      if (!$valPass) {
        $errorList[] = $valPass;
      }
       
    
   if($errorList){ //STATE 2: Errors
       $valuesList = ['firstName' => $firstName, 'lastName' => $lastName,'email'=>$email,'phone'=>$phoneNumber];
       return $this->view->render($response,'register.html.twig',['errorList' => $errorList, 'v' => $valuesList]);
   }else{
       $hash = password_hash($password1,PASSWORD_DEFAULT);
       DB::insert('users',['first_name'=> $firstName,'last_name'=>$lastName,'email'=>$email,'phone_number'=>$phoneNumber,'password'=>$hash]);
       return $this->view->render($response,'/register_success.html.twig');
   }
});

$app->get('/testbooking', function ($request, $response, $args) {
  return $this->view->render($response, 'testbooking.html.twig');
});


$app->post('/testbooking', function ($request, $response, $args) {
  $location = $request->getParam('location');
  $adults = $request->getParam('adults');
  $children = $request->getParam('children');
  $arrival = $request->getParam('arrival');
  $departure = $request->getParam('departure');
  $hotelList = searchHotels($location, $adults, $children, $arrival, $departure);
  return $this->view->render($response, 'apitestbooking.html.twig', ['location' => $location, 'adults' => $adults, 'children' => $children, 'arrival' => $arrival, 'departure' => $departure, 'h' => $hotelList->result]);
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

function searchHotels($location, $adults, $children, $arrival, $departure) {
  switch ($location) {
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
  $numberOfChildren = 1;
  $childrenAges = urlencode("5,0"); // %2C == , // "5%2C0" // urlencode("5,0")

  $apiUrl = "https://booking-com.p.rapidapi.com/v1/hotels/search?"
  ."dest_type=country"
  ."&checkin_date=" . $arrival
  ."&room_number=1"
  ."&checkout_date=" . $departure
  ."&order_by=popularity"
  ."&dest_id=" . $locationId
  ."&adults_number=" . $adults
  ."&units=metric"
  ."&filter_by_currency=CAD"
  ."&locale=en-us"
  ."&children_ages=" . $childrenAges
  ."&include_adjacency=false"
  ."&children_number=" . $numberOfChildren;

  return callAPI($apiUrl);   
  
}

$app->run();
