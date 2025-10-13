<?php
require_once __DIR__ . '/../BusinessLogic/PropertyController.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$data = json_decode(file_get_contents('php://input'), true);
if(!$data)
{
    print_r($data);
    foreach ($data as $value) { 
                $result[] = [ 
            'ID' => $value[0],
            'Name' => $value[1]
        ];
    }
    echo json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
}

echo json_encode([
        'success' => false,
        'error' => '$e->getMessage()'
    ]);