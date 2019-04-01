<?php

namespace Net\Ematos\Mesh\Infra;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Logger extends MonologLogger
{
    private $channelName;

    public function __construct($channelName)
    {
        parent::__construct($channelName);
        $this->channelName = $channelName;
    }

    public function streamHandler($fileName = '') {
        if ($fileName == '') {
            $fileName = sys_get_temp_dir() . $this->channelName . '.log';
        }
        $handler = new StreamHandler($fileName, Logger::DEBUG);
        $this->pushHandler($handler);
        $output = "%level_name% > %message%\n";
        $formatter = new LineFormatter($output, '');
        $handler->setFormatter($formatter);
    }

    public function socketHandler($connectionString = '') {
        if ($connectionString != '') {
            $handler = new SocketHandler($connectionString);
            $handler->setPersistent(true);
            $this->pushHandler($handler, Logger::DEBUG);
            $output = "%level_name% > %message%\n";
            $formatter = new LineFormatter($output, '');
            $handler->setFormatter($formatter);
        }
    }

}