<?php
header('Content-Type: application/json');

$jobId = $_GET['job_id'] ?? '';
$file  = "jobs/$jobId.json";

if (!file_exists($file)) {
    echo json_encode(['error' => 'Job no trobat']);
    exit;
}

echo file_get_contents($file);