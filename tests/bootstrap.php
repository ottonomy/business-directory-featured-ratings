<?php

// Load the test environment
// https://github.com/nb/wordpress-tests

$path = '/Users/nate/Documents/www/wordpress-tests/bootstrap.php';

if (file_exists($path)) {
        $GLOBALS['wp_tests_options'] = array(
                'active_plugins' => array(
                	'business-directory-featured-ratings/business-directory-featured-ratings.php',
                	'business-diretory-plugin/wpbusdirman.php',
                	'business-directory-ratings/business-directory-ratings.php'
                )
        );
        require_once $path;
} else {
        exit("Couldn't find wordpress-tests/bootstrap.php");
}

?>