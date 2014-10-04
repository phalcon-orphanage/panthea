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

namespace Panthea\Irc;

use Phalcon\Events\ManagerInterface as EventsManagerInterface;

/**
 * Panthea\Irc\Client
 *
 * A simple IRC client
 */
class Client
{

    /**
     * Events Manager
     */
    protected $eventsManager;

    /**
     * IRC server
     */
    protected $server;

    /**
     * IRC port
     */
    protected $port;

    /**
     * Current channel
     */
    protected $channel;

    /**
     * Current nickname
     */
    protected $nick;

    /**
     * Socket
     */
    protected $socket;
    protected $errno;
    protected $errstr;
    protected $timeout = 2;

    /**
     * Last data read from the socket
     */
    protected $buffer;

    /**
     * Client Constructor
     *
     * @param EventsManagerInterface $eventsManager
     * @param string $server
     * @param int $port
     */
    public function __construct(EventsManagerInterface $eventsManager, $server, $port)
    {
        $this->eventsManager = $eventsManager;
        $this->server = $server;
        $this->port = $port;
    }

    /**
     * Opens a connection to the IRC server
     *
     * @return resource
     */
    public function open()
    {
        $this->socket = fsockopen($this->server, $this->port, $this->errno, $this->errstr, $this->timeout);
        return $this->socket;
    }

    /**
     * Changes the current IRC nick
     *
     * @param string $nick
     * @param string $password
     */
    public function nick($nick, $password)
    {
        $this->nick = $nick;
        if ($password !== null) {
            $this->send("PASS {$password}\n\r");
        }
        $this->send('NICK ' . $this->nick . "\n\r");
        $this->send('USER ' . $this->nick . ' 0 * :' . $this->nick . "\n\r");
    }

    /**
     * Sends a message to the chat
     *
     * @param string $message
     * @param string $who
     */
    public function say($message, $who)
    {
        $this->send("PRIVMSG " . $who . " :" . $message . "\n\r");
    }

    /**
     * Check whether the connection does exist
     *
     * @return boolean
     */
    public function connected()
    {
        return !feof($this->socket);
    }

    /**
     * Sends a command
     *
     * @param string $command
     */
    public function send($command)
    {
        fputs($this->socket, $command, strlen($command));
    }

    /**
     * Joins a IRC channel
     *
     * @param string $channel
     * @param string $nick
     * @param string $password
     */
    public function join($channel, $nick = null, $password = null)
    {
        if ($nick !== null) {
            $this->nick($nick, $password);
        }
        $this->channel = $channel;
        $this->send("JOIN {$channel}\n\r");
    }

    // Part channel
    public function partChannel($channel)
    {
        $this->send("PART {$channel}\n\r");
    }

    /**
     * Returns an array with the last message
     *
     * @return array
     */
    public function readBuffer()
    {
        $this->buffer = fgets($this->socket, 1024);

        preg_match(
            '#^(?::(?<prefix>[^\s]+)\s+)?(?<command>[^\s]+)\s+(?<middle>[^:]+)?(:\s*(?<trailing>.+))?$#',
            $this->buffer,
            $message
        );

        return $message;
    }

    /**
     * Returns a valid nick
     *
     * @param  string  $nick
     * @return array
     */
    public function parseNick($nick)
    {
        preg_match('#^(?<nick>[^!]+)!(?<user>[^@]+)@(?<host>.+)$#', $nick, $matches);
        return $matches;
    }

    /**
     * Attaches an event to the client
     *
     * @param string $eventName
     * @param callable $callback
     */
    public function on($eventName, $callback)
    {
        $this->eventsManager->attach('irc:' . $eventName, $callback);
    }

    /**
     * Reply to a ping
     *
     * @param string $daemon
     */
    public function pong($daemon)
    {
        $this->send('PONG ' . $daemon);
    }

    /**
     * Listens for events and triggers them to the listeners
     */
    public function listen()
    {
        set_time_limit(0);

        if ($this->open()) {

            $this->eventsManager->fire('irc:open', $this);

            while ($this->connected()) {

                $message = $this->readBuffer();
                if (count($message)) {

                    if (isset($message['command'])) {

                        switch ($message['command']) {

                            case 366:
                                list($nickname, $channel) = explode(' ', $message['middle'], 2);
                                $this->eventsManager->fire('irc:join', $this, array(
                                    'nickname' => $nickname,
                                    'channel'  => trim($channel)
                                ));
                                break;

                            case 'PRIVMSG':
                                $middle       = trim($message['middle']);
                                $messageText  = $message['trailing'];

                                $this->eventsManager->fire('irc:message', $this, array(
                                    'from'    => $this->parseNick($message['prefix']),
                                    'message' => $messageText
                                ));
                                break;

                            case 'PING':
                                $servers = explode(' ', $message['trailing']);
                                foreach ($servers as $server) {
                                    $this->pong($server);
                                }
                                $this->eventsManager->fire('irc:ping', $this);
                                break;

                            case 'NOTICE':
                            case 'MODE':
                            case 'ERROR':
                            case 'QUIT':
                            case 'KICK':
                                $this->eventsManager->fire('irc:' . strtolower($message['command']), $this, $message);
                                break;

                            default:
                                $this->eventsManager->fire('irc:other', $this, $message);
                                break;
                        }
                    }

                }
            }
        }
    }

    /**
     * Close the connection to the IRC server
     *
     * @return boolean
     */
    public function close()
    {
        $this->eventsManager->fire('irc:close', $this);
        return fclose($this->socket);
    }
}
