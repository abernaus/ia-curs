<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$category = $data['category'] ?? '';
$date = $data['date'] ?? '';

$prompt = "Genera una descripcio professional per un esdeveniment anomenat '$name', "
    . "categoria '$category', data '$date'. Maxim 100 paraules. Idioma: catala.";

$payload = json_encode([
    'model' => 'google/gemma-4-b4e',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Respon nomes amb el text final per a l usuari. No mostris raonament ni explicacions internes.',
        ],
        ['role' => 'user', 'content' => $prompt],
    ],
    'max_tokens' => 600,
]);

$apiUrl = getenv('LLM_API_URL') ?: 'http://host.docker.internal:1234/v1/chat/completions';

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $payload,
        'timeout' => 120,
        'ignore_errors' => true,
    ],
]);

$result = @file_get_contents($apiUrl, false, $context);
$error = $result === false ? 'No sha pogut contactar amb el model local.' : null;

if ($error) {
    echo json_encode(['error' => $error]);
    exit;
}

$json = json_decode($result, true);
$description = $json['choices'][0]['message']['content'] ?? '';

if ($description === '') {
    echo json_encode(['error' => 'El model ha respost sense text final visible.']);
    exit;
}

echo json_encode(['description' => $description]);