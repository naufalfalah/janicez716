<?php

require_once 'database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'getProjects') {
    $stmt = $pdo->query("SELECT id, project, street FROM projects ORDER BY project");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($action === 'getTowns') {
    $stmt = $pdo->query("SELECT id, town, json_data FROM towns ORDER BY town");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($action === 'getStreet') {
    $town_id = $_GET['town_id'] ?? null;

    if ($town_id) {
        $stmt = $pdo->prepare("SELECT town_id, street_names FROM street_names WHERE town_id = :town_id ORDER BY street_names");
        $stmt->execute([':town_id' => $town_id]);
    } else {
        $stmt = $pdo->query("SELECT town_id, street_names FROM street_names ORDER BY street_names");
    }
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($action === 'getBlocks') {
    $town_id = $_GET['town_id'] ?? null;

    if ($town_id) {
        $stmt = $pdo->prepare("SELECT town_id, blocks FROM blocks WHERE town_id = :town_id ORDER BY blocks");
        $stmt->execute([':town_id' => $town_id]);
    } else {
        $stmt = $pdo->query("SELECT town_id, blocks FROM blocks ORDER BY blocks");
    }

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} else {
    echo json_encode(["error" => "Invalid action"]);
}
