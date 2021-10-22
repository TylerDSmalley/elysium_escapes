<?php

require_once 'vendor/autoload.php';

require_once 'init.php';

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

