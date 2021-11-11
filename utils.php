<?php

require_once 'vendor/autoload.php';

require_once 'init.php';

function validateName($name)
{
    if (preg_match('/^[\.a-zA-Z0-9,!? ]*$/', $name) != 1 || strlen($name) < 2 || strlen($name) > 100) {
        return "Name must be between 2 and 100 characters and include only letters, numbers, space, dash, dot or comma";
    }
    return TRUE;
}

function validatePhone($phone)
{
    $valid_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
    $valid_number = str_replace("-", "", $valid_number);
    if (strlen($valid_number) < 10 || strlen($valid_number) > 14) {
        return FALSE;
    } else {
        return TRUE;
    }
}
//Need to probably test and fix
function validatePassword($password1, $password2)
{
    if (empty($password1) || ($password1 !== $password2)) {
        return "Passwords do not match";
    } elseif (strlen($password1) < 9 || strlen($password1) > 100) {
        return  "Your Password Must Contain At Least 8 Characters!";
    } elseif (!preg_match("#[0-9]+#", $password1)) {
        return  "Your Password Must Contain At Least 1 Number!";
    } elseif (!preg_match("#[A-Z]+#", $password1)) {
        return "Your Password Must Contain At Least 1 Capital Letter!";
    } elseif (!preg_match("#[a-z]+#", $password1)) {
        return "Your Password Must Contain At Least 1 Lowercase Letter!";
    } elseif (!empty($password1)) {
        return "Please Check You've Entered Or Confirmed Your Password!";
    } else
        return TRUE;
}


function validatePasswordQuality($password1)
{
    if (strlen($password1) < 9 || strlen($password1) > 100) {
        return  "Your Password Must Contain At Least 8 Characters!";
    } elseif (!preg_match("#[0-9]+#", $password1)) {
        return  "Your Password Must Contain At Least 1 Number!";
    } elseif (!preg_match("#[A-Z]+#", $password1)) {
        return "Your Password Must Contain At Least 1 Capital Letter!";
    } elseif (!preg_match("#[a-z]+#", $password1)) {
        return "Your Password Must Contain At Least 1 Lowercase Letter!";
    } else
        return TRUE;
}


// $app->get('/admin/user/list', function .....);
function verifyUploadedPhoto(&$newFilePath, $photo)
{
    $photo = $_FILES['photo'];
    // is there a photo being uploaded and is it okay?
    if ($photo['error'] != UPLOAD_ERR_OK) {
        return "Error uploading photo " . $photo['error'];
    }
    if ($photo['size'] > 2 * 1024 * 1024) { // 2MB
        return "File too big. 2MB max is allowed.";
    }
    $info = getimagesize($photo['tmp_name']);

    if ($info[0] < 200 || $info[0] > 1000 || $info[1] < 200 || $info[1] > 1000) {
        return "Width and height must be within 200-1000 pixels range";
    }
    switch ($info['mime']) {
        case 'image/jpeg':
            $ext = "jpg";
            break;
        case 'image/gif':
            $ext = "gif";
            break;
        case 'image/png':
            $ext = "png";
            break;
        case 'image/bmp':
            $ext = "bmp";
            break;
        default:
            return "Only JPG, GIF, BMP, and PNG file types are accepted";
    }
    $filename = pathinfo($_FILES['photo']['name'], PATHINFO_FILENAME);
    $santitizedPhoto = str_replace(array_merge(
        array_map('chr', range(0, 31)),
        array('<', '>', ':', '"', '/', '\\', '|', '?', '*')
    ), '', $filename);
    $newFilePath = "uploads/" . $santitizedPhoto . "." . $ext;
    return TRUE;
}

function setFlashMessage($message){
    $_SESSION['flashMessage'] = $message;
}

function getAndClearFlashMessage(){
    if(isset($_SESSION['flashMessage'])){
        $message = $_SESSION['flashMessage'];
        unset($_SESSION['flashMessage']);
        return $message;
    }
    return "";
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}