<?php

require_once 'config.php';

loadEnv(__DIR__ . '/.env');

function sendLeadToDiscord($lead)
{
    $Project = "";
    $ProjectData = "";
    $ZapierURL = "";
    $commonData = array();
    $additional_data = array();

    if (isset($lead)) {
        $commonData = array(
            "name" => $lead['firstname'],
            "mobile_number" => $lead['ph_number'],
            "email" => $lead['email'],
            "source_url" => getenv('DISCORD_SOURCE_URL') ?? 'https://launchgovtest.homes/',
        );

        if ($lead['form_type'] == 'condo') {
            $additional_data = array(
                array(
                    "key" => "Project",
                    "value" => "Condo " . $lead['project']
                ),
                array(
                    "key" => "Blk",
                    "value" => $lead['block']
                ),
                array(
                    "key" => "Looking to sell your property",
                    "value" => $lead['sell']
                ),
                array(
                    "key" => "Floor - Unit number",
                    "value" => $lead['floor'] ." - ". $lead['number']
                )
            );
        } elseif ($lead['form_type'] == 'landed') {
            $additional_data = array(
                array(
                    "key" => "Project",
                    "value" => "Landed"
                ),
                array(
                    "key" => "Landed Street",
                    "value" => $lead['street']
                ),
                array(
                    "key" => "SQFT",
                    "value" => $lead['sqft']
                ),
                array(
                    "key" => "Like to Know",
                    "value" => $lead['like_to_know']
                ),
                array(
                    "key" => "Plans",
                    "value" => $lead['plan']
                )
            );
        } elseif ($lead['form_type'] == 'hdb') {
            $additional_data = array(
                array(
                    "key" => "Project",
                    "value" => "HDB"
                ),
                array(
                    "key" => "Town",
                    "value" => $lead['town']
                ),
                array(
                    "key" => "Street Name",
                    "value" => $lead['street']
                ),
                array(
                    "key" => "Blk",
                    "value" => $lead['block']
                ),
                array(
                    "key" => "HDB Flat Type",
                    "value" => $lead['flat_type']
                ),
                array(
                    "key" => "Looking to sell your property",
                    "value" => $lead['sell']
                ),
                array(
                    "key" => "Floor - Unit number",
                    "value" => $lead['floor'] ." - ".$lead['unit']
                )
            );
        }

        $commonData['additional_data'] = $additional_data;
        $LeadManagement = $commonData;

        // JSON encode the lead data
        $jsonData = json_encode($LeadManagement);

        // Check for potential junk content
        $check_junk = checkJunk($jsonData);

        //check dnc via phone or email
        $check_dnc = check_dnc($lead['email'],$lead['ph_number']);
        
        // Fetch the user's IP address
        $ip_address = fetchIp();

        // Prepare webhook data
        $webhook_data = array(
            'client_id' => null,
            'project_id' => null,
            'ip_address' => $ip_address,
            'is_verified' => 0
        );
        if (isset($lead['wp_otp']) && $lead['wp_otp'] != '' && $lead['wp_otp'] == $lead['user_otp']){
            $LeadManagement["additional_data"][] = [
                "key" => "Whatsapp Verified",
                "value" => $response["lead"]["is_whatsapp_verified"] = "Yes"
            ];
        } else {
            $LeadManagement["additional_data"][] = [
                "key" => "Whatsapp Verified",
                "value" => $response["lead"]["is_whatsapp_verified"] = "No"
            ];
        }
        
        // Determine status based on junk content
        if (isset($check_junk['Terms']) && !empty($check_junk['Terms']) && count($check_junk['Terms']) > 0) {
            $webhook_data['status'] = 'junk';
            $webhook_data['is_send_discord'] = 0;
        } if ($check_dnc['status']){
            $webhook_data['status'] = 'DNC Registry';
            $webhook_data['is_send_discord'] = 0;
        } else {
            $webhook_data['status'] = 'clear';
            $webhook_data['is_send_discord'] = 1;
            // Assuming sendFrequencyLead() is defined elsewhere
            sendFrequencyLead($LeadManagement);
            $_SESSION['lead_sent'] = true;
        }

        // Merge $lead data with webhook data
        $webhook_data = array_merge($webhook_data, $lead);
        
        // Send data to the endpoint
        sendData($webhook_data);
        return true;
    }
}

// Function to send data via cURL
function sendData($data)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://janicez87.sg-host.com/endpoint.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic am9tZWpvdXJuZXl3ZWJzaXRlQGdtYWlsLmNvbTpQQCQkd29yZDA5MDIxOGxlYWRzISM='
        ),
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    // return $response;
}

// Function to fetch user's IP address
function fetchIp()
{
    $url = "https://api.ipify.org/?format=json";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return false;
    }

    curl_close($ch);

    $data = json_decode($response, true);

    if ($data !== null) {
        return $data['ip'];
    } else {
        return false;
    }
}

// Function to check for junk content
function checkJunk($data)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://jomejourney.cognitiveservices.azure.com/contentmoderator/moderate/v1.0/ProcessText/Screen',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: text/plain',
            'Ocp-Apim-Subscription-Key: 453fe3c404554800bc2c22d7ef681542'
        ),
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

// Function to send frequency lead
function sendFrequencyLead($data)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://roundrobin.datapoco.ai/api/lead_frequency/add_lead',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode('Client Management Portal:123456')
        ),
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function check_dnc($email,$phone)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://janicez87.sg-host.com/check_dnc.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "email": "'.$email.'",
            "ph_number": "'.$phone.'"
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

?>
