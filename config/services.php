<?php

use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->set('db', function () use ($config) {
	return new DbAdapter($config->database->toArray());
});

/**
 * Sets the config itself as a service
 */
$di->set('config', $config);