<?php
    echo "<pre>\n";
    //session_start();


   //  $_SESSION['blogUser'] = array("id"  => 5, 'name' => 'Jerry B.', 'email' => 'jerry@jerry.com');

    print_r($_SESSION);
    echo "\n";

    // is someone logged in?
    if (isset($_SESSION['user'])) {
        echo "you're logged in as " . $_SESSION['user']['account_type'];
    } else {
        echo 'not logged in';
    }