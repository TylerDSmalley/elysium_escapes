<?php

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

      if (!validatePassword($password1,$password2)) {
        $errorList[] = "Invalid Password";
        $phoneNumber ="";
      }
       
    
   if($errorList){ //STATE 2: Errors
       $valuesList = ['firstName' => $firstName, 'lastName' => $lastName,'email'=>$email,'phone'=>$phoneNumber];
       return $this->view->render($response,'register.html.twig',['errorList' => $errorList, 'v' => $valuesList]);
   }else{
        $hash = password_hash($password1,PASSWORD_DEFAULT);
       DB::insert('users',['first_name'=> $firstName,'last_name'=>$lastName,'email'=>$email,'phone_number'=>$phoneNumber,'password'=>$hash]);
       return $this->view->render($response,'register_success.html.twig');
   }
});

$app->run();
