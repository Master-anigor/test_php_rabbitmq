<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('test', false, false, false, false);

if (file_exists('urls.txt')){
    $file = fopen(__DIR__ . '/urls.txt', 'r');
    while (!feof($file)) {
        $msg = new AMQPMessage(json_encode(["url" => fgets($file), "wait" => 30]));
        $channel->basic_publish($msg, '', 'test');
    }
    fclose($file);
}

$channel->close();
$connection->close();