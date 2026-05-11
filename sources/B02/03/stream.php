<?php
// Capçaleres SSE — mantenir connexió oberta
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Important si hi ha nginx al davant

$apiUrl = getenv('LLM_API_URL') ?: 'http://host.docker.internal:1234/v1/chat/completions';

$data     = json_decode(file_get_contents('php://input'), true);
$name     = $data['name']     ?? '';
$category = $data['category'] ?? '';

$prompt = "Escriu un email de confirmació per als assistents de l'esdeveniment 
           '$name' de categoria '$category'. Inclou: benvinguda, logística bàsica 
           i missatge de cloenda. Màxim 200 paraules.";

$payload = json_encode([
    'model'    => 'google/gemma-4-b4e',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Respon nomes amb el text final per a l usuari. No mostris raonament ni explicacions internes.',
        ],
        ['role' => 'user', 'content' => $prompt],
    ],
    'max_tokens' => 600,
    'stream'   => true,  // <-- clau
]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // no acumular — enviar directe
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

// Callback: s'executa per cada chunk que arriba de l'LLM
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) {
    $lines = explode("\n", $chunk);

    foreach ($lines as $line) {
        $line = trim($line);
        if (!str_starts_with($line, 'data: ')) continue;

        $json_str = substr($line, 6); // treure "data: "
        if ($json_str === '[DONE]') {
            echo "data: [DONE]\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
            break;
        }

        $json  = json_decode($json_str, true);

        // Suport DeepSeek R1 i models estàndard
        $delta = $json['choices'][0]['delta'] ?? [];
        $token = $delta['content'] ?? $delta['reasoning_content'] ?? '';

        if ($token !== '') {
            // Format SSE
            echo "data: " . json_encode(['token' => $token]) . "\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush(); // enviar immediatament sense buffer
        }
    }

    return strlen($chunk); // obligatori per curl
});

$result = curl_exec($ch);

if ($result === false) {
    $error = curl_error($ch) ?: 'Error desconegut en l stream.';
    echo 'data: ' . json_encode(['token' => "[ERROR] $error"]) . "\n\n";
    echo "data: [DONE]\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

curl_close($ch);