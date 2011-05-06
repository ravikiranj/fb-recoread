<?php

    //Set include paths
    //$clientLibraryPath = '/home/ravikira/public_html/drupal/sites/all/hacks/recoread/';
    $clientLibraryPath = '/home/ravikiran/ravikiranj.net/drupal/sites/all/hacks/fb-recoread/';
    $oldPath = set_include_path(get_include_path() . PATH_SEPARATOR . $clientLibraryPath);

    //include_once('/home/ravikira/public_html/drupal/sites/all/hacks/recoread/recoread.php');
    include_once('/home/ravikiran/ravikiranj.net/drupal/sites/all/hacks/fb-recoread/fbrecoread.php');

    // Instantiate Object
    $readLike = new fbrecoread();
?>
