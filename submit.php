<?php

require_once 'database.php';
require_once 'helper_discord.php';
require_once 'helper_round_robin.php';
require_once 'helper_2chat.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ./");
    exit();
}
$phone = $_POST['ph_number'] ?? null;
if (!$phone) {
    // Missing phone number
    header("Location: ./");
    exit();
}
$stmt = $pdo->prepare("SELECT COUNT(*) AS total, leads.ph_number as ph_number FROM leads WHERE ph_number = :phone");
$stmt->bindParam(":phone", $_POST['ph_number'], PDO::PARAM_STR);
$stmt->execute();

$duplicate = false;

// Fetch result
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result['total'] != 0) {
    $duplicate = true;
}

$data = $_POST;
// die($data);

$leadFields = ["form_type", "source_url", "ip", "name", "ph_number", "email"];
$leadData = array_intersect_key($data, array_flip($leadFields));

foreach ($leadFields as $field) {
    if (!isset($leadData[$field]) || empty($leadData[$field])) {
        // Missing required field
        header("Location: ./");
        exit();
    }
}

$pdo->beginTransaction();
try {
    if (!$duplicate) {
        $stmt = $pdo->prepare("INSERT INTO leads (form_type, source_url, ip, firstname, ph_number, email) 
                              VALUES (:form_type, :source_url, :ip, :name, :ph_number, :email)");
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
    
        $pdo->commit();
        
        $fetchStmt = $pdo->prepare("SELECT * FROM leads WHERE id = :id");
        $fetchStmt->execute([':id' => $leadId]);
        $lead = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        $detailStmt = $pdo->prepare("SELECT lead_form_key, lead_form_value FROM lead_details WHERE lead_id = :lead_id");
        $detailStmt->execute([':lead_id' => $leadId]);

        while ($row = $detailStmt->fetch(PDO::FETCH_ASSOC)) {
            $lead[$row['lead_form_key']] = $row['lead_form_value'];
        }
        
        sendLeadToDiscord($lead);
    }
    if ($duplicate) {
        $stmt = $pdo->prepare("SELECT id FROM leads WHERE ph_number = :phone ORDER BY id DESC LIMIT 1");
        $stmt->execute([':phone' => $phone]);
        $leadId = $stmt->fetchColumn();
    }
        
    $formType = $_POST['form_type'] ?? null;

    $project = $_POST['project'] ?? '';
    $block = $_POST['block'] ?? '';
    $floor = $_POST['floor'] ?? '';
    $unitVal = $_POST['unit'] ?? '';
    $flatType = $_POST['flat_type'] ?? '';
    $town = $_POST['town'] ?? '';
    $street = $_POST['street'] ?? '';

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
        header("Location: ./result/condo.php?lead_id=$leadId&unit=".urlencode($unit)."&full_address=".urlencode($fullAddress)."&project=".urlencode($project)."&block=".urlencode($block)."&floor=".urlencode($floor)."&unit_val=".urlencode($unitVal));
    } elseif ($formType === 'hdb') {
        header("Location: ./result/hdb.php?lead_id=$leadId&unit=".urlencode($unit)."&full_address=".urlencode($fullAddress)."&town=".urlencode($town)."&block=".urlencode($block)."&flat_type=".urlencode($flatType)."&street=".urlencode($street)."&floor=".urlencode($floor)."&unit_val=".urlencode($unitVal));
    }
    exit();
} catch (PDOException $e) {
    header("Location: ./");
    exit();
}
