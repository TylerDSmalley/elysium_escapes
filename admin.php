<?php

require_once 'vendor/autoload.php';
require_once 'utils.php';
require_once 'init.php';

// LIST USERS/DESTINATION/CONTACTUS/BOOKINGS/TESTIMONIALS HANDLER
 $app->get('/admin/{op:users|destinations|contactus|bookings|testimonials}/list', function($request,$response,$args){
    if($args['op']=='users'){
      $userList = DB::query("SELECT * FROM users WHERE status='active'");
      return $this->view->render($response,'admin/users_list.html.twig',['usersList'=> $userList]);
    }

    if($args['op']=='destinations'){
      $destinationsList = DB::query("SELECT * FROM  destinations");
      return $this->view->render($response,'admin/destinations_list.html.twig',['destinationsList'=> $destinationsList]);
    }

    if($args['op']=='contactus'){
      $contactsList = DB::query("SELECT * FROM  contact_us ORDER BY contactTS DESC");
      return $this->view->render($response,'admin/contactus_list.html.twig',['contactsList'=> $contactsList]);
    }

    if($args['op']=='bookings'){
      $bookingsList = DB::query("SELECT * FROM  booking_history");
      return $this->view->render($response,'admin/bookings_list.html.twig',['bookingsList'=> $bookingsList]);
    }

    if($args['op']=='testimonials'){
      $testimonialsList = DB::query("SELECT * FROM  testimonials");
      return $this->view->render($response,'admin/testimonials_list.html.twig',['testimonialsList'=> $testimonialsList]);
    }
 });


 // INACTIVE USERS HANDLER
 $app->get('/admin/users/inactive', function($request,$response,$args){
   $userList = DB::query("SELECT * FROM users WHERE status='inactive'");
   return $this->view->render($response,'admin/inactive_users.html.twig',['usersList'=> $userList]);
});



// ADD AND EDIT USERS HANDLER

/*
if (!isset($_SESSION['user']) || $_SESSION['user']['account_type'] != 'admin') {
        $app->redirect('/forbidden');
        return;
    }
*/

 $app->get('/admin/users/{op:edit|add}[/{id:[0-9]+}]',function($request,$response,$args){
   if(($args['op'] == 'add' && !empty($args['id']) || $args['op'] == 'edit' && empty($args['id']))){
      $response = $response->withStatus(404);
      return $this->view->render($response,'admin/not_found.html.twig');
   }

   if($args['op'] == 'edit'){
      $user = DB::queryFirstRow("SELECT * FROM users WHERE id=%i",$args['id']);
      if(!$user){
         $response = $response->withStatus(404);
         return $this->view->render($response,'admin/not_found.html.twig');
 }
}else{
   $user=[];
}
   return $this->view->render($response,'admin/users_addedit.html.twig',['user'=> $user,'op'=>$args['op']]);
});

$app->get('/flash',function($request,$response,$args){
   print_r($_SESSION);
   return $this->view->render($response,'flash.html.twig');
});

$app->post('/admin/users/{op:edit|add}[/{id:[0-9]+}]',function($request,$response,$args){
   if(($args['op'] == 'add' && !empty($args['id']) || $args['op'] == 'edit' && empty($args['id']))){
      $response = $response->withStatus(404);
      return $this->view->render($response,'admin/not_found.html.twig');
   }

  $firstName = $request->getParam('firstName');
  $lastName = $request->getParam('lastName');
  $email = $request->getParam('email');
  $phoneNumber = $request->getParam('phone');
  $type = $request->getParam('type');
  $password1 = $request->getParam('password1');
  $password2 = $request->getParam('password2');

  $errorList = [];

  $result = validateName($firstName);
  if(!$result){
     $errorList[] = $result;
  }

  $result = validateName($lastName);
  if(!$result){
     $errorList[] = $result;
  }

  //First check if you are need to validate password. Ex. Updating user info but not their password
  if($password1 != "" || $args['op']=='add'){
      $result = validatePassword($password1,$password2);
   if(!$result){
     $errorList[] = $result;
  }
  }
  
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
     $errorList[] = "Email not valid";
     $email="";
  }else{
     //is email already in use?
     if($args['op']== 'edit'){
      $record = DB::queryFirstRow("SELECT * FROM users where email=%s AND id != %d",$email,$args['id']);
     }else{
      $record = DB::queryFirstRow("SELECT * FROM users where email=%s",$email);
     }
      if($record){
         $errorList[]= "This email is already registered";
         $email="";
      }
   }
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
   if($errorList){
      return $this->view->render($response,'admin/users_addedit.html.twig',
      ['errorList'=> $errorList, 'val'=> ['firstName'=> $firstName,'lastName'=>$lastName,'email'=>$email,'phone'=>$phoneNumber]]);
   }else{
      $hash = password_hash($password1,PASSWORD_DEFAULT);
      if($args['op']=='add'){
         DB::insert('users',['first_name'=>$firstName,'last_name'=>$lastName,'email'=>$email,'phone_number'=>$phoneNumber,'password'=>$hash,'account_type'=>$type]);
         setFlashMessage("User successfully added!"); 
         //return $this->view->render($response,'/admin/users_addeit_sucess.html.twig',['op'=>$args['op']]);
         return $response->withRedirect("/admin/users/list");
   }else{
      $data = ['first_name'=>$firstName,'last_name'=>$lastName,'email'=>$email,'phone_number'=>$phoneNumber,'account_type'=>$type];
         if($password1 != ""){
               $data['password'] = $hash;
         }
         DB::update('users',$data,"id=%d",$args['id']);
         return $this->view->render($response,'/admin/users_addedit_success.html.twig',['op'=>$args['op']]);
   }
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
});
//DELETE USERS HANDLER
$app->get('/admin/users/delete[/{id:[0-9]+}]',function($request,$response,$args){
   $user = DB::queryFirstRow("SELECT * FROM users WHERE id=%d",$args['id']);
      if(!$user){
         $response = $response->withStatus(404);
         return $this->view->render($response,'admin/not_found.html.twig');
      }
   return $this->view->render($response,'admin/users_delete.html.twig',['user'=> $user]);
});

$app->post('/admin/users/delete[/{id:[0-9]+}]',function($request,$response,$args){
   DB::query("UPDATE users SET status='inactive' WHERE id=%i",$args['id']);
   return $this->view->render($response,'admin/users_delete.html_success.twig');
});

$app->get('/error_internal', function ($request, $response, $args) {
  return $this->view->render($response, 'error_internal.html.twig');
});


//DELETE DESTINATION HANDLER
$app->get('/admin/destinations/delete[/{id:[0-9]+}]',function($request,$response,$args){
   $destination = DB::queryFirstRow("SELECT * FROM destinations WHERE id=%d",$args['id']);
      if(!$destination){
         $response = $response->withStatus(404);
         return $this->view->render($response,'admin/not_found.html.twig');
      }
   return $this->view->render($response,'admin/destinations_delete.html.twig',['destination'=> $destination]);
});

$app->post('/admin/destinations/delete[/{id:[0-9]+}]',function($request,$response,$args){
   DB::query("UPDATE destinations SET status = 'inactive' WHERE id=%i",$args['id']);
   return $this->view->render($response,'admin/destinations_delete_success.html.twig');
});

//DELETE TESTIMONIAL HANDLER
$app->get('/admin/testimonials/delete[/{id:[0-9]+}]',function($request,$response,$args){
   $testimonial = DB::queryFirstRow("SELECT * FROM testimonials WHERE id=%d",$args['id']);
      if(!$testimonial){
         $response = $response->withStatus(404);
         return $this->view->render($response,'admin/not_found.html.twig');
      }
   return $this->view->render($response,'admin/testimonials_delete.html.twig',['testimonial'=> $testimonial]);
});

$app->post('/admin/testimonials/delete[/{id:[0-9]+}]',function($request,$response,$args){
   DB::query("DELETE FROM testimonials WHERE id=%i",$args['id']);
   return $this->view->render($response,'admin/testimonials_delete_success.html.twig');
});




// ADD AND EDIT DESTINATION HANDLER
$app->get('/admin/destinations/{op:edit|add}[/{id:[0-9]+}]',function($request,$response,$args){
   if(($args['op'] == 'add' && !empty($args['id']) || $args['op'] == 'edit' && empty($args['id']))){
      $response = $response->withStatus(404);
      return $this->view->render($response,'admin/not_found.html.twig');
   }

   if($args['op'] == 'edit'){
      $destination = DB::queryFirstRow("SELECT * FROM destinations WHERE id=%i",$args['id']);
      if(!$destination){
         $response = $response->withStatus(404);
         return $this->view->render($response,'admin/not_found.html.twig');
 }
}else{
   $destination=[];
}
   return $this->view->render($response,'admin/destinations_addedit.html.twig',['destination'=> $destination,'op'=>$args['op']]);
});

//Need to fix up POST edit / add on destination

//STATE 2 & 3 = recieving submission
$app->post('/admin/destinations/{op:edit|add}[/{id:[0-9]+}]', function ($request, $response, $args) use ($log) {

   $op = $args['op'];

  $destination_description = $destination_name = $photo =  "";
  $errors = array('destination_name' => '', 'destination_description' => '', 'photo' => '');

  // check destination_name
  if (empty($request->getParam('destination_name'))) {
      $errors['destination_name'] = 'A Seller Name is required';
  } else {
      $destination_name = $request->getParam('destination_name');
      if (strlen($destination_name) < 2 || strlen($destination_name) > 50) {
          $errors['destination_name'] = 'destination_name must be 2-50 characters long';
      } else {
          $finaldestination_name = htmlentities($destination_name);
      }
  }

  // check destination_description
  if (empty($request->getParam('destination_description'))) {
      $errors['destination_description'] = 'An Item Description is required';
  } else {
      $destination_description = $request->getParam('destination_description');
      if (strlen($destination_description) < 2 || strlen($destination_description) > 5000) {
          $errors['itemDescription'] = 'Body must be 2-10000 characters long';
      } else {
          $final_destination_description = strip_tags($destination_description, "<p><ul><li><em><strong><i><b><ol><h3><h4><h5><span><pre>");
          $final_destination_description = htmlentities($final_destination_description);
      }
  }

  // check photo

  $photo = $_FILES['photo'];
  $isPhoto = TRUE;
  if($op == 'add'){
   $photoFilePath = "";
   $retval = verifyUploadedPhoto($photoFilePath, $photo);
   if ($retval !== TRUE) {
       $errors['photo'] = $retval; // string with error was returned, add it to error list
   }
   //adding a new destination sent to validatePhoto

  }elseif($op == 'edit' && $photo->getError()!= UPLOAD_ERR_NO_FILE){
   $photoFilePath = "";
   $retval = verifyUploadedPhoto($photoFilePath, $photo);
   if ($retval !== TRUE) {
       $errors['photo'] = $retval; // string with error was returned, add it to error list
   }
   //there is a photo for edit so send to validatePhoto
  }else{
     $isPhoto = FALSE;
  }
 

  if (array_filter($errors)) { //STATE 2 = errors
      $valuesList = ['destination_name' => $destination_name, 'destination_description' => $destination_description, 'photo' => $photoFilePath];
      return $this->view->render($response, 'admin/destinations_add.html.twig', ['errors' => $errors, 'v' => $valuesList]);
  } else { //This is an add operation
         if($op == 'add'){

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoFilePath)) {
               die("Error moving the uploaded file. Action aborted.");
           }
           // 2. insert a new record with file path
           $finalFilePath = htmlentities($photoFilePath);

            DB::insert('destinations', [
               'destination_name' => $finaldestination_name,
               'destination_description' => $final_destination_description,
               'destination_imagepath' => $finalFilePath,
           ]);
         }else{ //This is an edit operation
            if($isPhoto == TRUE){
               if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoFilePath)) {
                  die("Error moving the uploaded file. Action aborted.");
              }
              // 2. insert a new record with file path
              $finalFilePath = htmlentities($photoFilePath);
               $data = ['destination_name'=>$finaldestination_name,'destination_description'=>$final_destination_description,'destination_imagepath' => $finalFilePath];
               DB::update('destinations',$data,"id=%d",$args['id']);
            }else{
               $data = ['destination_name'=>$finaldestination_name,'destination_description'=>$final_destination_description];
               DB::update('destinations',$data,"id=%d",$args['id']);
            }
            return $this->view->render($response,'/admin/users_addedit_success.html.twig',['op'=>$args['op']]);

         }
      // STATE 3: submission successful
      // insert the record and inform user

      // 1. move uploaded file to its desired location
      if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoFilePath)) {
          die("Error moving the uploaded file. Action aborted.");
      }
      // 2. insert a new record with file path
      $finalFilePath = htmlentities($photoFilePath);

      //save to db and check
      DB::insert('destinations', [
          'destination_name' => $finaldestination_name,
          'destination_description' => $final_destination_description,
          'destination_imagepath' => $finalFilePath,
      ]);

      $log->debug(sprintf("new auction created with id=%s", $_SERVER['REMOTE_ADDR'], DB::insertId())); //needs test
      return $this->view->render($response, 'admin/destinations_add.html.twig'); //needs confirmation signal
  } // end POST check
  
});

$app->patch('/isMessageRead/{id:[0-9]+}/{checkVal}', function($request, $response, $args) {

   if($args['checkVal']=='No'){ 
      DB::query("UPDATE contact_us SET replied = 'No' WHERE id=%i",$args['id']);
   }else{
      DB::query("UPDATE contact_us SET replied = 'Yes' WHERE id=%i",$args['id']);
   }
});