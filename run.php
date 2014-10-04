<?php

/*
 +------------------------------------------------------------------------+
 | Panthea                                                                |
 +------------------------------------------------------------------------+
 | Copyright (c) 2013-2014 Phalcon Team and contributors                  |
 +------------------------------------------------------------------------+
 | This source file is subject to the MIT License that is bundled         |
 | with this package in the file docs/LICENSE.txt.                        |
 |                                                                        |
 | If you did not receive a copy of the license and are unable to         |
 | obtain it through the world-wide-web, please send an email             |
 | to license@phalconphp.com so we can send you a copy immediately.       |
 +------------------------------------------------------------------------+
*/

use Phalcon\Logger\Adapter\File as Logger,
    Phalcon\DI\FactoryDefault\CLI as CliDi,
    Phalcon\CLI\Console as ConsoleApp;

// Define path to application directory
define('APP_PATH', realpath('.'));

//Create a service container
$di = new CliDi();

// Load the configuration file (if any)
$config = require APP_PATH . '/config/config.php';

// Load services
require APP_PATH . '/config/services.php';

// Define auto-loader
require APP_PATH . '/config/loader.php';

try {

    // Create a console application
    $console = new ConsoleApp();
    $console->setDI($di);

    // handle incoming arguments
    $console->handle(array('task' => 'irc', 'action' => 'run'));

} catch (Exception $e) {

    /**
     * Log the exception
     */
    $logger = new Logger(APP_PATH . '/logs/error.log');
    $logger->error($e->getMessage());
    $logger->error($e->getTraceAsString());

    echo $e->getMessage(), PHP_EOL;
    exit(255);
}
