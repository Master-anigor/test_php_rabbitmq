<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


function initDatabase()
{
    $mysqli = new mysqli("mysql", "user", "password");
    if($mysqli->connect_error){
        die("Ошибка: " . $mysqli->connect_error);
    }

    $mysqli->query("CREATE DATABASE IF NOT EXISTS dev;");
    $mysqli->query("USE dev;");
    $mysqli->query("CREATE TABLE `response` (
                        `id` int NOT NULL,
                        `url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
                        `code` int NOT NULL,
                        `header` json DEFAULT NULL,
                        `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    $mysqli->query("ALTER TABLE `response` ADD PRIMARY KEY (`id`);");
    $mysqli->query("ALTER TABLE `response` MODIFY `id` int NOT NULL AUTO_INCREMENT; COMMIT;");

    $mysqli->close();
}

function headersToArray( $str )
{
    $headers = array();
    $headersTmpArray = explode( "\r\n" , $str );
    for ( $i = 0 ; $i < count( $headersTmpArray ) ; ++$i )
    {
        if ( strlen( $headersTmpArray[$i] ) > 0 )
        {
            if ( strpos( $headersTmpArray[$i] , ":" ) )
            {
                $headerName = substr( $headersTmpArray[$i] , 0 , strpos( $headersTmpArray[$i] , ":" ) );
                $headerValue = substr( $headersTmpArray[$i] , strpos( $headersTmpArray[$i] , ":" )+1 );
                $headers[$headerName] = $headerValue;
            }
        }
    }
    return $headers;
}

function getMessageInRabbitmq()
{
    $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    $channel->queue_declare('test', false, false, false, false);


    $callback = function ($msg) {
        $message = json_decode($msg->body, TRUE);
        sleep($message['wait']);
        $response = responseUrl($message['url']);
        if(count($response)){
            inserResponse($response);
            if($response['code'] != 200 && $message['wait'] == 30){
                inserMessageInRabbitmq(json_encode(["url" => $response['url'], "wait" => 15]));
            }
        }
    };

    $channel->basic_consume('test', '', false, true, false, false, $callback);

    while ($channel->is_open()) {
        $channel->wait();
    }

    $channel->close();
    $connection->close();
}

function inserMessageInRabbitmq( $message = "" ){
    $connection_insert = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    $channel_insert = $connection_insert->channel();
    
    $channel_insert->queue_declare('test', false, false, false, false);

    $msg = new AMQPMessage($message);
    $channel_insert->basic_publish($msg, '', 'test');
    
    $channel_insert->close();
    $connection_insert->close();
}

function responseUrl($url = ""){
    $response = [];
    if ($url != ""){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        $response = curl_exec($ch);

        $response['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $response['header'] = json_encode(headersToArray(substr($response, 0, $header_size)));
        $response['content'] = substr($response, $header_size);
        $response['url'] = $url;

        curl_close($ch);
    }
    return $response;
}

function inserResponse($response)
{
    if(count($response) == 4){
        $mysqli = new mysqli("mysql", "user", "password");
        if($mysqli->connect_error){
            die("Ошибка: " . $mysqli->connect_error);
        }

        $mysqli->query("INSERT INTO `response`(`url`, `code`, `header`, `content`) 
                        VALUES ('".$response['url']."','".$response['code']."','".$response['header']."','".$response['content']."')");

        $mysqli->close();
    }
}

initDatabase();
getMessageInRabbitmq();