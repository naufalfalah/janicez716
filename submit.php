<?php

require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method',
    ]);
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS total, leads.ph_number as ph_number FROM leads WHERE ph_number = :phone");
$stmt->bindParam(":phone", $_POST['ph_number'], PDO::PARAM_STR);
$stmt->execute();

// Fetch result
$result = $stmt->fetch(PDO::FETCH_ASSOC);
// if ($result['total'] != 0) {
//     echo json_encode([
//         "success" => true,
//         "message" => "Data saved successfully!",
//         "lead_id" => $_POST['lead_id'],
//     ]);
//     exit();
// }

// if ($result['ph_number'] == $_POST['ph_number']) {
//     if (isset($_POST['lead_id']) && $_POST['lead_id'] != '') {
//         echo json_encode([
//             "success" => true,
//             "message" => "Data saved successfully!",
//             "lead_id" => $_POST['lead_id'],
//         ]);
//         exit();
//     }
// }

$data = $_POST;

$leadFields = ["form_type", "source_url", "name", "ph_number", "email"];
$leadData = array_intersect_key($data, array_flip($leadFields));

foreach ($leadFields as $field) {
    if (!isset($leadData[$field]) || empty($leadData[$field])) {
        die(json_encode([
            "success" => false,
            "message" => "❌ Missing required field: $field"
        ]));
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO leads (form_type, source_url, firstname, ph_number, email) 
                          VALUES (:form_type, :source_url, :name, :ph_number, :email)");
    $stmt->execute($leadData);
    $leadId = $pdo->lastInsertId(); // Get inserted lead ID

    $extraFields = array_diff_key($data, array_flip($leadFields));
    unset($extraFields['user_otp'], $extraFields['wp_otp'], $extraFields['lead_id']);

    if (!empty($extraFields)) {
        $stmt = $pdo->prepare("INSERT INTO lead_details (lead_id, lead_form_key, lead_form_value) 
                              VALUES (:lead_id, :lead_form_key, :lead_form_value)");
                    
        foreach ($extraFields as $key => $value) {
            $stmt->execute([
                ':lead_id' => $leadId,
                ':lead_form_key' => $key,
                ':lead_form_value' => is_array($value) ? implode('| ', $value) : $value
            ]);
        }
    }
    
    $formType = $_POST['form_type'] ?? null;

    $project = $_POST['project'] ?? '';
    $block = $_POST['block'] ?? '';
    $floor = $_POST['floor'] ?? '';
    $unitVal = $_POST['unit'] ?? '';
    $flatType = $_POST['flat_type'] ?? '';
    $town = $_POST['town'] ?? '';
    $street = $_POST['street'] ?? '';
    $sqft = $_POST['sqft'] ?? '';
    $plan = $_POST['plan'] ?? '';

    $fullAddress = '';
    $unit = '';

    if ($formType === 'condo') {
        $fullAddress = "$project, Blk $block, Floor $floor - Unit $unitVal";
        $unit = "Floor $floor - Unit $unitVal";
    } elseif ($formType === 'hdb') {
        $fullAddress = "$town, $street, Blk $block, Floor $floor - Unit $unitVal, HDB Flat Type: $flatType";
        $unit = "Floor $floor - Unit $unitVal";
    }

    if ($formType === 'condo') {
        header("Location: ./condo/report.php?lead_id=$leadId&unit=".urlencode($unit)."&full_address=".urlencode($fullAddress)."&project=".urlencode($project)."&block=".urlencode($block)."&unit=".urlencode($unit));
    } elseif ($formType === 'hdb') {
        header("Location: ./hdb/report.php?lead_id=$leadId&unit=".urlencode($unit)."&full_address=".urlencode($fullAddress)."&town=".urlencode($town)."&flat_type=".urlencode($flatType)."&block=".urlencode($block));
    } 
    exit();
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database Insert Failed",
        "errorInfo" => $e->getMessage()
    ]);
    exit();
}
