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

use Phalcon\CLI\Task;

use Phalcon\Events\Event;
use Panthea\Irc\Monitor;
use Phalcon\Events\Manager as EventsManager;
use Panthea\Irc\Client as IrcClient;

require 'library/Irc/Client.php';

/**
 * IrcTask
 *
 * Runs the IRc client and the monitor
 */
class IrcTask extends Task
{
    public function runAction()
    {
        $irc = $this->config->irc;
        $connection = $this->db;

        /**
         * Creates a IRC client
         */
        $client = new IrcClient(new EventsManager, $irc->server, $irc->port);

        /**
         * Opens a connection and joins every configured channel
         */
        $client->on('open', function(Event $event, $client) use ($irc) {
            foreach ($irc->channels as $channel) {
                $client->join($channel, $irc->nickname, $irc->password);
            }
        });

        /**
         * Queries the database connection when a ping is received from the IRC server
         */
        $client->on('ping', function() use ($connection) {
            $connection->query('SELECT 1');
        });

        /**
         * Receives a message from the channel and stores it in the log
         */
        $client->on('message', function(Event $event, $client, $message) use ($connection) {
            $connection->execute(
                'INSERT INTO irclog (who, content, datelog) VALUES (:who, :content, :datelog)',
                array(
                    'who'     => $message['from']['nick'],
                    'content' => $message['message'],
                    'datelog' => time()
                )
            );
        });

        $client->listen();
    }
}