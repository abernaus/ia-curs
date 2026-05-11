<?php
header('Content-Type: application/json');

$data     = json_decode(file_get_contents('php://input'), true);
$feedback = $data['feedback'] ?? '';

// Generem un ID únic per aquest job
$jobId = uniqid('job_', true);

// "Cua" simplificada: fitxer JSON a disc
$job = [
    'id'       => $jobId,
    'status'   => 'pending',
    'feedback' => $feedback,
    'result'   => null,
];
file_put_contents("jobs/$jobId.json", json_encode($job));

// Retornem immediat — no esperem l'LLM
echo json_encode(['job_id' => $jobId, 'status' => 'pending']);