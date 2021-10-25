<?php

require_once 'vendor/autoload.php';

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