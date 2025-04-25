<?php

require_once('database.php');

$draw = $_GET['draw'] ?? 0;
$row = $_GET['start'] ?? 0;
$rowperpage = $_GET['length'] ?? 10; // Rows display per page
$order = $_GET['order'][0] ?? ['column' => 0, 'dir' => 'asc'];
$columns = $_GET['columns'] ?? [];

$columnIndex = $order['column'];
$columnSortOrder = $order['dir'];

$columnName = $columns[$columnIndex]['data'] ?? 'contractDate';
$searchValue = $_GET['search']['value'] ?? '';

$query = "
    SELECT projects.project, projects.street, projects.marketSegment, project_transactions.*
    FROM project_transactions
    JOIN projects ON projects.id = project_transactions.project_id
    WHERE 1
";

$params = [];

if (!empty($_GET['project'])) {
    $query .= " AND project_transactions.project_id = :project_id";
    $params[':project_id'] = $_GET['project'];
}

if (!empty($_GET['type_of_sale'])) {
    $query .= " AND project_transactions.typeOfSale = :type_of_sale";
    $params[':type_of_sale'] = $_GET['type_of_sale'];
}

if (!empty($_GET['floor_range'])) {
    $floor_range = $_GET['floor_range'];

    if ($floor_range === '1-10') {
        $query .= " AND project_transactions.floorRange BETWEEN 1 AND 10";
    } elseif ($floor_range === '10-20') {
        $query .= " AND project_transactions.floorRange BETWEEN 10 AND 20";
    } elseif ($floor_range === '20 and above') {
        $query .= " AND project_transactions.floorRange >= 20";
    }
}

if (!empty($_GET['sales_dates'])) {
    $query .= " AND YEAR(project_transactions.contractDate) = :sales_dates";
    $params[':sales_dates'] = $_GET['sales_dates'];
}

if (!empty($_GET['area_sqft'])) {
    $area_sqft = $_GET['area_sqft'];
    $in_array = ['400-600', '600-700', '700-1300'];

    if (in_array($area_sqft, $in_array)) {
        [$min_sqft, $max_sqft] = explode('-', $area_sqft);

        $min_area = ceil(($min_sqft / 10.76391042));
        $max_area = ceil(($max_sqft / 10.76391042));

        $query .= " AND project_transactions.area BETWEEN :min_area AND :max_area";
        $params[':min_area'] = $min_area;
        $params[':max_area'] = $max_area;

    } else {
        $min_area = ceil((1300 / 10.76391042));
        $query .= " AND project_transactions.area >= :min_area";
        $params[':min_area'] = $min_area;
    }
}

if (!empty($searchValue)) {
    $query .= " AND (
        project_transactions.area LIKE :search
        OR project_transactions.floorRange LIKE :search
        OR project_transactions.contractDate LIKE :search
        OR project_transactions.typeOfSale LIKE :search
        OR project_transactions.price LIKE :search
        OR project_transactions.propertyType LIKE :search
        OR project_transactions.district LIKE :search
        OR project_transactions.typeOfArea LIKE :search
        OR project_transactions.tenure LIKE :search
        OR projects.project LIKE :search
        OR projects.street LIKE :search
    )";
    $params[':search'] = '%' . $searchValue . '%';
}

$countQuery = "
    SELECT COUNT(*) AS allcount
    FROM project_transactions
    JOIN projects ON projects.id = project_transactions.project_id
    WHERE 1
";

if (strpos($query, 'AND') !== false) {
    $countQuery .= substr($query, strpos($query, 'AND'));
}

$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

$totalRecords = $record['allcount'];
$totalRecordwithFilter = $record['allcount'];

$query .= " ORDER BY project_transactions.contractDate $columnSortOrder LIMIT :offset, :limit";

$params[':offset'] = (int)$row;
$params[':limit'] = (int)$rowperpage;

$stmt = $pdo->prepare($query);

foreach ($params as $key => &$val) {
    if (is_int($val)) {
        $stmt->bindParam($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindParam($key, $val);
    }
}

$stmt->execute();

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $formattedDate = date("Y F", strtotime($row['contractDate']));

    switch ($row['typeOfSale']) {
        case '1':
            $typeOfSale = 'New Sale';
            break;
        case '2':
            $typeOfSale = 'Sub Sale';
            break;
        case '3':
            $typeOfSale = 'Resale';
            break;
        default:
            $typeOfSale = '-';
    }

    $data[] = [
        "contractDate" => $formattedDate,
        "project" => $row['project'],
        "street" => $row['street'],
        "district" => $row['district'],
        "marketSegment" => $row['marketSegment'],
        "tenure" => $row['tenure'],
        "typeOfSale" => $typeOfSale,
        "floorRange" => $row['floorRange'],
        "area" => ceil($row['area'] * 10.76391042),
        "price" => '$'.number_format($row['price'])
    ];
}

$response = [
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
