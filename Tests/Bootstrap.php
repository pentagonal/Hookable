<?php
/**
 * Load composer Autoload
 */

// file name
$file = __DIR__.'/../vendor/autoload.php';

// filecheck
if (!file_exists($file)) {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

// include
include $file;
