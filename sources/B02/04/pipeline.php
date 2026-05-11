<?php
header('Content-Type: application/json');

$data     = json_decode(file_get_contents('php://input'), true);
$feedback = $data['feedback'] ?? '';
$apiUrl   = getenv('LLM_API_URL') ?: 'http://host.docker.internal:1234/v1/chat/completions';

// ── Helper: crida genèrica a l'LLM ──────────────────────────
function callLLM(string $prompt): string {
    $payload = json_encode([
        'model'      => 'google/gemma-4-b4e',
        'messages'   => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 300,
    ]);

    global $apiUrl;

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $result = curl_exec($ch);
    curl_close($ch);

    $json    = json_decode($result, true);
    
    // Defensem l'estructura (DeepSeek + models estàndard)
    if (isset($json['error'])) return 'ERROR: ' . $json['error']['message'];
    $message = $json['choices'][0]['message'] ?? [];
    return $message['content'] ?? $message['reasoning_content'] ?? '';
}

// ── PAS 1: Classificar sentiment ─────────────────────────────
$classifyPrompt = "Classifica el sentiment d'aquest feedback en UNA sola paraula: 
                   positiu, neutre, o negatiu.
                   Respon NOMÉS amb la paraula, sense puntuació.
                   Feedback: \"$feedback\"";

$sentiment = strtolower(trim(callLLM($classifyPrompt)));

// Normalitzar per si el model afegeix text extra
if (str_contains($sentiment, 'negatiu'))  $sentiment = 'negatiu';
elseif (str_contains($sentiment, 'positiu')) $sentiment = 'positiu';
else $sentiment = 'neutre';

// ── PAS 2: Generar resposta segons sentiment ──────────────────
if ($sentiment === 'negatiu') {
    $responsePrompt = "Un assistent d'un event ha deixat aquest feedback negatiu: \"$feedback\".
                       Escriu una resposta breu (màx 80 paraules), empàtica i professional, 
                       reconeixent el problema i oferint millores per a futures edicions.";
    $response = callLLM($responsePrompt);
} else {
    $response = "Gràcies pel teu feedback! Ens alegra que hagis gaudit de l'esdeveniment. 
                 Esperem veure't a la propera edició.";
}

// ── Retornem els resultats de tots els passos ─────────────────
echo json_encode([
    'feedback'  => $feedback,
    'sentiment' => $sentiment,
    'response'  => $response,
]);