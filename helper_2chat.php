<?php

require_once 'config.php';

loadEnv(__DIR__ . '/.env');

function sendWpMessage($client_number, $message)
{
    if (empty($client_number) || empty($message)) {
        return "Error: client_number or message is empty.";
    }

    $curl = curl_init();
    $api_key = getenv('2CHAT_API_KEY');
    $from_number = getenv('2CHAT_FROM_NUMBER');

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.p.2chat.io/open/whatsapp/send-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(array(
            "to_number" => '+65'.$client_number,
            "from_number" => $from_number,
            "text" => $message
        )),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'X-User-API-Key: ' . $api_key
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error = 'Curl error: ' . curl_error($curl);
        curl_close($curl);
        return $error;
    }

    curl_close($curl);
    return $response;
}