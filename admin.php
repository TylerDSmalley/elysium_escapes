<?php

require_once 'vendor/autoload.php';
require_once 'utils.php';
require_once 'init.php';

 $app->get('/admin/users/list', function($request,$response,$args){
    $userList = DB::query("SELECT * FROM users");
    return $this->view->render($response,'admin/users_list.html.twig',['usersList'=> $userList]);
 });

$app->get('/admin/users/{id:[0-9]+}/edit',function($request,$response,$args){
 $user = DB::queryFirstRow("SELECT * FROM users WHERE id=%i",$args['id']);
 if(!$user){
   $response = $response->withStatus(404);
   return $this->view->render($response,'admin/not_found.html.twig');
 }
   return $this->view->render($response,'admin/users_edit.html.twig',['user'=> $user]);
});

$app->get('/error_internal', function ($request, $response, $args) {
  return $this->view->render($response, 'error_internal.html.twig');
});

// ADD DESTINATION HANDLER
//State 1 - display form
$app->get('/admin/destinations', function($request,$response,$args){
  return $this->view->render($response,'admin/destinations_add.html.twig');
});

//STATE 2 & 3 = recieving submission
$app->post('/admin/destinations', function ($request, $response, $args) use ($log) {

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
  if ($photo == null) {
      $errors['photo'] = 'A photo is required';
  } else {
      $photoFilePath = "";
      $retval = verifyUploadedPhoto($photoFilePath, $destination_name);
      if ($retval !== TRUE) {
          $errors['photo'] = $retval; // string with error was returned, add it to error list
      }
  }


  if (array_filter($errors)) { //STATE 2 = errors
      $valuesList = ['destination_name' => $destination_name, 'destination_description' => $destination_description, 'photo' => $photoFilePath];
      return $this->view->render($response, 'admin/destinations_add.html.twig', ['errors' => $errors, 'v' => $valuesList]);
  } else {
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
