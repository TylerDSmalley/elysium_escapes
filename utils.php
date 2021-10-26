<?php

require_once 'vendor/autoload.php';

require_once 'init.php';

function validatePhone($phone){
    $valid_number = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
    $valid_number = str_replace("-", "", $valid_number);
    if (strlen($valid_number) < 10 || strlen($valid_number) > 14) {
        return FALSE;
    }else{
        return TRUE;
    }
    }

function validatePassword($password1,$password2){
    if(!empty($password1) && ($password1 == $password2)) {
        
        if (strlen($password1) < 4 || strlen($password1) > 100) {
            return $errorList[] = "Your Password Must Contain At Least 8 Characters!";
        }
        elseif(!preg_match("#[0-9]+#",$password1)) {
           return $errorList[] = "Your Password Must Contain At Least 1 Number!";
        }
        elseif(!preg_match("#[A-Z]+#",$password1)) {
            return $errorList[] = "Your Password Must Contain At Least 1 Capital Letter!";
        }
        elseif(!preg_match("#[a-z]+#",$password1)) {
            return $errorList[] = "Your Password Must Contain At Least 1 Lowercase Letter!";
        }
        elseif(!empty($password1)) {
            return $errorList[] = "Please Check You've Entered Or Confirmed Your Password!";
        } 
    }else {
        return $errorList[] = "Please enter password";
        }
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

