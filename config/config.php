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

return new \Phalcon\Config(array(

    'database' => array(
        'adapter'  => 'Mysql',
        'host'     => 'localhost',
        'username' => 'root',
        'password' => '',
        'dbname'   => 'forum',
        'charset'  => 'utf8'
    ),

    'application' => array(
        'tasksDir'       => APP_PATH . '/tasks/',
        'debug'          => true
    ),

    'irc' => array(
        'server'        => 'chat.freenode.org',
        'port'          => 6667,
        'nickname'      => 'panthea',
        'password'      => '',
        'channels'      => array('#phalconphp')
    ),

    'monitor' => array(
        'server'        => '127.0.0.1',
        'port'          => 10001
    )

));
