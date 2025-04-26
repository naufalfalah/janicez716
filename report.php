<?php

require_once('database.php');

$lead_id = $_GET['lead_id'] ?? 1;

// Pehle lead ka data fetch karein
$sql_lead = "SELECT * FROM leads WHERE id = :lead_id";
$stmt = $pdo->prepare($sql_lead);
$stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
$stmt->execute();
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

// Agar lead nahi mili to return karein
if (!$lead) {
    die("No lead found with ID: $lead_id");
}

// Ab lead_details ka data fetch karein
$sql_details = "SELECT * FROM lead_details WHERE lead_id = :lead_id";
$stmt = $pdo->prepare($sql_details);
$stmt->bindParam(':lead_id', $lead_id, PDO::PARAM_INT);
$stmt->execute();
$lead_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Final formatted output
$response = [
    'lead' => $lead,  // Lead ka single record
    'lead_details' => $lead_details // Lead details ka array
];

function get_town_blocks($pdo, $town)
{
    $townQuery = "SELECT id FROM towns WHERE town = :town";

    $townStmt = $pdo->prepare($townQuery);
    $townStmt->bindParam(':town', $town);
    $townStmt->execute();
    $townResult = $townStmt->fetch(PDO::FETCH_ASSOC);

    $townId = $townResult['id'];

    // Prepare the SQL query to search for n-1 and n+1 values
    $query = "SELECT blocks FROM blocks WHERE town_id = :townId";

    // Bind parameters and execute the query
    $statement = $pdo->prepare($query);
    $statement->bindParam(':townId', $townId);
    $statement->execute();
    $result = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (empty($result)) {
        return null;
    }

    $data_arr = [];

    if (!empty($result)) {
        foreach ($result as $val) {
            $data_arr[] = $val['blocks'];
        }
    }

    return $data_arr;
}

$form_type = $response['lead']['form_type'];

if (isset($form_type) && $form_type == 'hdb') {
    $base_url = "https://data.gov.sg/api/action/datastore_search";
    $resource_id = "f1765b54-a209-4718-8d38-a39237f502b3";

    $is_submit = 0;
    $search_count = 1;
    $town_blocks = '';
    $town = '';
    $flat_type = '';
    $street_name = '';

    $first_request_url = '';
    $second_request_url = '';

    // Inputs
    $town_value = strtoupper($response['lead_details']['0']['lead_form_value'] ?? '');
    $street_name_value = strtoupper($response['lead_details']['1']['lead_form_value'] ?? '');
    $block_value = $response['lead_details']['2']['lead_form_value'] ?? '';
    $flat_type_value = strtoupper($response['lead_details']['3']['lead_form_value'] ?? '');

    $blocks = !empty($block_value) ? get_town_blocks($pdo, $town_value) : [];

    if (!empty($blocks)) {
        $town_blocks = json_encode($blocks);
    }

    if (!empty($town_value)) {
        $is_submit = 1;
        $town = $town_value;
        $flat_type = $flat_type_value;
        $street_name = $street_name_value;

        $dateRange = [];
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        for ($i = 11; $i >= 0; $i--) {
            $month = $currentMonth - $i;
            $year = $currentYear;
        
            if ($month <= 0) {
                $year -= 1;
                $month += 12;
            }
        
            $paddedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
            $dateRange[] = "{$year}-{$paddedMonth}";
        }

        // ===== FIRST REQUEST FILTER =====
        $first_filters = [
            "month" => $dateRange,
            "town" => $town,
        ];

        if (!empty($flat_type)) {
            $first_filters['flat_type'] = $flat_type;
        }

        if (!empty($street_name)) {
            $first_filters['street_name'] = $street_name;
        }

        $first_queryParams = [
            'resource_id' => $resource_id,
            "limit" => 12000,
            'filters' => json_encode($first_filters),
            'sort' => 'month desc'
        ];

        $first_request_url = $base_url . '?' . http_build_query($first_queryParams);


        // ===== SECOND REQUEST FILTER =====
        $second_filters = [
            "month" => $dateRange,
            "town" => $town,
        ];

        if (!empty($flat_type)) {
            $second_filters['flat_type'] = $flat_type;
        }

        if (!empty($blocks)) {
            $second_filters['block'] = $blocks;
        }

        $second_queryParams = [
            'resource_id' => $resource_id,
            "limit" => 12000,
            'filters' => json_encode($second_filters),
            'sort' => 'month desc'
        ];
        
        $second_request_url = $base_url . '?' . http_build_query($second_queryParams);
        
        // print_r($town_blocks);
        // print_r($dateRange);
        // print_r($first_request_url);
        // print_r($second_request_url);
    }
}

if (isset($form_type) && $form_type === 'condo') {
    $project_id = $response['lead_details'][1]['lead_form_value'] ?? null;
    $project_id = 4607;
    // var_dump($project_id);
    // die;

    if ($project_id === null) {
        die("Error: Project ID is missing.");
    }

    $stmt = $pdo->prepare("SELECT DISTINCT project_transactions.contractDate 
                           FROM project_transactions 
                           LEFT JOIN projects ON projects.id = project_transactions.project_id 
                           WHERE project_transactions.project_id = :project_id
                           ORDER BY contractDate ASC");
    $stmt->execute(['project_id' => $project_id]);
    $sales_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sales_date_option = '<option value="">Any Sales Year</option>';
    $sales_data_array = [];

    foreach ($sales_dates as $row) {
        $year = date("Y", strtotime($row['contractDate']));
        $sales_data_array[] = $year;
    }

    $sales_data_array = array_unique($sales_data_array);
    sort($sales_data_array);

    foreach ($sales_data_array as $value) {
        $selected = (isset($_GET['sales_dates']) && $_GET['sales_dates'] == $value) ? ' selected' : '';
        $sales_date_option .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars($value) . '</option>';
    }

    $query = "SELECT projects.project, projects.street, projects.marketSegment, project_transactions.* 
              FROM project_transactions
              JOIN projects ON projects.id = project_transactions.project_id 
              WHERE 1 ";

    $params = [];

    // Filter project_id
    if (!empty($project_id)) {
        $query .= " AND project_transactions.project_id = :project_id ";
        $params['project_id'] = $project_id;
    }

    // Filter type_of_sale
    if (!empty($_GET['type_of_sale'])) {
        $query .= " AND project_transactions.typeOfSale = :type_of_sale ";
        $params['type_of_sale'] = $_GET['type_of_sale'];
    }

    // Filter floor_range
    if (!empty($_GET['floor_range'])) {
        $floor_range = $_GET['floor_range'];
        if ($floor_range === '1-10') {
            $query .= " AND project_transactions.floorRange BETWEEN 1 AND 10 ";
        } elseif ($floor_range === '10-20') {
            $query .= " AND project_transactions.floorRange BETWEEN 10 AND 20 ";
        } elseif ($floor_range === '20 and above') {
            $query .= " AND project_transactions.floorRange >= 20 ";
        }
    }

    // Filter sales_dates
    if (!empty($_GET['sales_dates'])) {
        $query .= " AND YEAR(project_transactions.contractDate) = :sales_date ";
        $params['sales_date'] = $_GET['sales_dates'];
    }

    // Filter area_sqft
    if (!empty($_GET['area_sqft'])) {
        $area_sqft = $_GET['area_sqft'];
        $in_array = ['400-600', '600-700', '700-1300'];
        if (in_array($area_sqft, $in_array)) {
            list($min, $max) = explode('-', $area_sqft);
            $min_area = ceil($min / 10.76391042);
            $max_area = ceil($max / 10.76391042);
            $query .= " AND project_transactions.area BETWEEN :min_area AND :max_area ";
            $params['min_area'] = $min_area;
            $params['max_area'] = $max_area;
        } else {
            $min_area = ceil(1300 / 10.76391042);
            $query .= " AND project_transactions.area >= :min_area ";
            $params['min_area'] = $min_area;
        }
    }

    // Count total record
    $count_query = "SELECT COUNT(*) AS allcount FROM project_transactions 
                    JOIN projects ON projects.id = project_transactions.project_id 
                    WHERE 1 ";

    $stmt = $pdo->prepare($count_query . substr($query, strpos($query, "AND")));
    $stmt->execute($params);
    $records = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalRecords = $records['allcount'] ?? 0;
    $totalRecordwithFilter = $totalRecords;
    
    // Get data details
    $final_query = $query . " ORDER BY project_transactions.contractDate ASC";
    $stmt = $pdo->prepare($final_query);
    $stmt->execute($params);
    $empRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($empRecords as $row) {
        $formattedDate = date("Y F", strtotime($row['contractDate']));

        $typeOfSale = match ($row['typeOfSale']) {
            '1' => 'New Sale',
            '2' => 'Sub Sale',
            '3' => 'Resale',
            default => '-',
        };

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
            "price" => number_format($row['price']),
        ];
    }
        
    function analyzePropertyData($properties)
    {
        // Initialize the output array
        $output = [
            'highestPrice' => '',
            'lowestPrice' => '',
            'estimatedPrice' => []
        ];

        // Data processing functions
        function formatPrice($price)
        {
            return number_format($price, 0, '.', ',');
        }

        // Estimates price based on area and floor range
        function estimatePrice($area, $floorRange, $floorRanges, $avgPricePerSqFt)
        {
            if (isset($floorRanges[$floorRange])) {
                return formatPrice($area * $floorRanges[$floorRange]['avgPricePerSqFt']);
            } else {
                // Fallback to overall average
                return formatPrice($area * $avgPricePerSqFt);
            }
        }

        // Process the property data
        if ($properties) {
            // Convert price strings to numeric values and calculate price per square foot
            foreach ($properties as &$property) {
                $property['numericPrice'] = (float) str_replace(',', '', $property['price']);
                $property['pricePerSqFt'] = $property['numericPrice'] / $property['area'];
            }

            // Sort by highest price
            $highestPrice = $properties;
            usort($highestPrice, function ($a, $b) {
                return $b['numericPrice'] - $a['numericPrice'];
            });

            // Sort by lowest price
            $lowestPrice = $properties;
            usort($lowestPrice, function ($a, $b) {
                return $a['numericPrice'] - $b['numericPrice'];
            });

            // Calculate overall average price per square foot
            $totalPricePerSqFt = 0;
            foreach ($properties as $property) {
                $totalPricePerSqFt += $property['pricePerSqFt'];
            }
            $avgPricePerSqFt = $totalPricePerSqFt / count($properties);

            // Group by floor range and calculate average price per sq ft for each range
            $floorRanges = array();
            foreach ($properties as $property) {
                $floorRange = $property['floorRange'];
                if (!isset($floorRanges[$floorRange])) {
                    $floorRanges[$floorRange] = array(
                        'totalPricePerSqFt' => 0,
                        'count' => 0
                    );
                }
                $floorRanges[$floorRange]['totalPricePerSqFt'] += $property['pricePerSqFt'];
                $floorRanges[$floorRange]['count']++;
            }

            foreach ($floorRanges as $range => $data) {
                $floorRanges[$range]['avgPricePerSqFt'] = $data['totalPricePerSqFt'] / $data['count'];
            }

            // Get unique areas for estimation
            $uniqueAreas = array();
            foreach ($properties as $property) {
                if (!in_array($property['area'], $uniqueAreas)) {
                    $uniqueAreas[] = $property['area'];
                }
            }
            sort($uniqueAreas);

            // Set the highest and lowest prices
            $output['highestPrice'] = $highestPrice[0]['price'];
            $output['lowestPrice'] = $lowestPrice[0]['price'];

            // Generate estimated prices for each floor range and area
            foreach ($floorRanges as $floorRange => $data) {
                foreach ($uniqueAreas as $area) {
                    $output['estimatedPrice'][] = [
                        'floorRange' => $floorRange,
                        'area' => $area,
                        'price' => estimatePrice($area, $floorRange, $floorRanges, $avgPricePerSqFt)
                    ];
                }
            }
        } else {
            $output['error'] = "Error: Could not process property data.";
        }

        return $output;
    }

    $properties = json_decode(json_encode($data), true);
    $results = analyzePropertyData($properties);

    if (isset($results['highestPrice'])) {
        $highestPrice =  "$" . $results['highestPrice'];
    }

    if (isset($results['lowestPrice'])) {
        $lowestPrice = "$" . $results['lowestPrice'];
    }

    if (isset($results['estimatedPrice']) && count($results['estimatedPrice'])) {
        $estimatedSellingPrice = "$" . $results['estimatedPrice'][0]['price'];
    }
}

if (isset($form_type) && $form_type == 'landed') {
    $project_id = $response['lead_details'][1]['lead_form_value'] ?? null;

    if (!$project_id) {
        exit("Project ID not found.");
    }

    // Fetch distinct sales dates
    $sales_date_option = '<option value="">Any Sales Year</option>';

    $sales_date_stmt = $pdo->prepare("
        SELECT DISTINCT project_transactions.contractDate
        FROM project_transactions
        LEFT JOIN projects ON projects.id = project_transactions.project_id
        WHERE project_transactions.project_id = :project_id
        ORDER BY contractDate ASC
    ");
    $sales_date_stmt->execute(['project_id' => $project_id]);

    $sales_data_array = [];

    while ($row = $sales_date_stmt->fetch(PDO::FETCH_ASSOC)) {
        $sales_data_array[] = date("Y", strtotime($row['contractDate']));
    }

    // Unique and sort years
    $sales_data_array = array_values(array_unique($sales_data_array));
    sort($sales_data_array);

    foreach ($sales_data_array as $value) {
        $selected = (isset($_GET['sales_dates']) && $_GET['sales_dates'] == $value) ? 'selected' : '';
        $sales_date_option .= '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>' . htmlspecialchars($value) . '</option>';
    }

    // Query project transactions with filter
    $searchQuery = '';
    $params = [];

    if ($project_id) {
        $searchQuery .= " AND project_transactions.project_id = :project_id";
        $params['project_id'] = $project_id;
    }

    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) AS allcount
        FROM project_transactions
        JOIN projects ON projects.id = project_transactions.project_id
        WHERE 1 $searchQuery
    ");
    $count_stmt->execute($params);
    $records = $count_stmt->fetch(PDO::FETCH_ASSOC);

    $totalRecordwithFilter = $records['allcount'];
    $totalRecords = $records['allcount'];

    // Get the actual data
    $query = "
        SELECT projects.project, projects.street, projects.marketSegment, project_transactions.*
        FROM project_transactions
        JOIN projects ON projects.id = project_transactions.project_id
        WHERE 1 $searchQuery
    ";
    $data_stmt = $pdo->prepare($query);
    $data_stmt->execute($params);

    $data = [];

    while ($row = $data_stmt->fetch(PDO::FETCH_ASSOC)) {

        $formattedDate = date("Y F", strtotime($row['contractDate']));

        $typeOfSale = match ($row['typeOfSale']) {
            '1' => 'New Sale',
            '2' => 'Sub Sale',
            '3' => 'Resale',
            default => '-'
        };

        $area_in_sqft = ceil($row['area'] * 10.76391042);

        $data[] = [
            "contractDate" => $formattedDate,
            "project" => $row['project'],
            "street" => $row['street'],
            "district" => $row['district'],
            "marketSegment" => $row['marketSegment'],
            "tenure" => $row['tenure'],
            "typeOfSale" => $typeOfSale,
            "floorRange" => $row['floorRange'],
            "area" => $area_in_sqft,
            "price" => number_format($row['price']),
        ];
    }

    function analyzePropertyData(array $properties): array
    {
        $output = [
            'highestPrice' => '',
            'lowestPrice' => '',
            'estimatedPrice' => ''
        ];

        if (!empty($properties)) {
            $prices = array_map(fn($property) => (float) str_replace(',', '', $property['price']), $properties);

            $output['highestPrice'] = number_format(max($prices), 0, '.', ',');
            $output['lowestPrice'] = number_format(min($prices), 0, '.', ',');
            $output['estimatedPrice'] = number_format(array_sum($prices) / count($prices), 0, '.', ',');
        } else {
            $output['error'] = "Error: Could not process property data.";
        }

        return $output;
    }

    $results = analyzePropertyData($data);

    $highestPrice = "$" . $results['highestPrice'];
    $lowestPrice = "$" . $results['lowestPrice'];
    $estimatedSellingPrice = "$" . $results['estimatedPrice'];
}
