<?php

function check_email($email, $ph_number)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://janicez87.sg-host.com/check_time_email_round.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{
        "email":"' . $email . '",
        "ph_number":"' . $ph_number . '"
    }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response, true);
}
