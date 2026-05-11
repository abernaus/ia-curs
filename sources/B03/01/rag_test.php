<?php

// ---- CONFIGURACIÓ ----
$LM_STUDIO = 'http://localhost:1234/v1';
$EMBED_MODEL = 'text-embedding-nomic-embed-text-v1.5';
$GEN_MODEL   = 'google/gemma-4-e4b';

// ---- BASE DE CONEIXEMENT (simula docs de biPeek) ----
$documents = [
    "Per crear un event a biPeek, ves a Dashboard > Events > Nou Event. Omple el títol, data, lloc i aforament màxim.",
    "L'aforament d'un event es configura al camp 'Capacitat màxima'. biPeek bloqueja la venda quan s'arriba al límit.",
    "Per exportar assistents en CSV, ves a l'event > Assistents > Exportar. Inclou nom, email i telèfon si s'ha recollit.",
    "biPeek permet crear categories d'entrades: General, VIP, Early Bird. Cada categoria té preu i aforament propi.",
    "Les estadístiques d'un event mostren quantes entrades s'han venut, vendes per dia, canals de venda i conversió. Per veure quantes entrades has venut, ves a l'event > Stats.",
];

// ---- FUNCIONS ----

function getEmbedding(string $text, string $model, string $baseUrl, bool $isQuery = false): array {
    $prefix = $isQuery ? 'search_query: ' : 'search_document: ';
    $response = httpPost("$baseUrl/embeddings", [
        'model' => $model,
        'input' => $prefix . $text,
    ]);
    return $response['data'][0]['embedding'];
}

function cosineSimilarity(array $a, array $b): float {
    $dot = 0; $normA = 0; $normB = 0;
    for ($i = 0; $i < count($a); $i++) {
        $dot   += $a[$i] * $b[$i];
        $normA += $a[$i] ** 2;
        $normB += $b[$i] ** 2;
    }
    return $dot / (sqrt($normA) * sqrt($normB));
}

function httpPost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($data),
    ]);
    $result = curl_exec($ch);
    return json_decode($result, true);
}

// ---- PIPELINE RAG ----

$userQuestion = "Com puc veure quantes entrades he venut?";
echo "Pregunta: $userQuestion\n\n";

// 1. Indexar documents (en producció, es fa una sola vegada)
echo "Indexant documents...\n";
$index = [];
foreach ($documents as $doc) {
    $index[] = [
        'text'      => $doc,
        'embedding' => getEmbedding($doc, $EMBED_MODEL, $LM_STUDIO, false),
    ];
}

// 2. Embedding de la pregunta
$queryEmbedding = getEmbedding($userQuestion, $EMBED_MODEL, $LM_STUDIO, true);

// 3. Cercar els 2 chunks més rellevants
$scores = [];
foreach ($index as $i => $item) {
    $scores[$i] = cosineSimilarity($queryEmbedding, $item['embedding']);
}
arsort($scores);
$topK = array_slice(array_keys($scores), 0, 2);

echo "Chunks recuperats:\n";
foreach ($topK as $i) {
    echo "  [" . round($scores[$i], 2) . "] {$index[$i]['text']}\n";
}

// 4. Construir prompt amb context
$context = implode("\n", array_map(fn($i) => $index[$i]['text'], $topK));
$prompt  = "Ets un assistent de suport de biPeek.\n"
         . "Usa NOMÉS aquest context per respondre:\n\n"
         . $context
         . "\n\nPregunta: $userQuestion";

// 5. Generar resposta
$result = httpPost("$LM_STUDIO/chat/completions", [
    'model'    => $GEN_MODEL,
    'messages' => [['role' => 'user', 'content' => $prompt]],
]);

echo "\nResposta:\n" . $result['choices'][0]['message']['content'] . "\n";