<?php
// S'executa: php worker.php (procés continu o cron)

$apiUrl = getenv('LLM_API_URL') ?: 'http://host.docker.internal:1234/v1/chat/completions';
$runOnce = in_array('--once', $argv, true);

while (true) {
    $files = glob('jobs/*.json');
    $processedJobs = 0;

    foreach ($files as $file) {
        $job = json_decode(file_get_contents($file), true);

        if (!is_array($job) || ($job['status'] ?? null) !== 'pending') {
            continue;
        }

        // Marquem com a "processing" per evitar doble processament
        $job['status'] = 'processing';
        file_put_contents($file, json_encode($job));

        // Crida a l'LLM
        $prompt = "Analitza aquest feedback d'un esdeveniment i retorna:
                   1. Sentiment general (positiu/neutre/negatiu)
                   2. 3 punts forts
                   3. 3 punts de millora
                   Feedback: {$job['feedback']}";

        $payload = json_encode([
            'model'      => 'google/gemma-4-b4e',
            'messages'   => [
                [
                    'role' => 'system',
                    'content' => 'Respon nomes amb el text final per a l usuari. No mostris raonament ni explicacions internes.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 600,
        ]);

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

        if ($result === false) {
            $job['status'] = 'failed';
            $job['result'] = 'No sha pogut contactar amb el model local.';
            file_put_contents($file, json_encode($job));
            echo "Job {$job['id']} fallit.\n";
            $processedJobs++;
            continue;
        }

        $json = json_decode($result, true);
        $description = $json['choices'][0]['message']['content'] ?? '';

        if ($description === '') {
            $job['status'] = 'failed';
            $job['result'] = 'El model ha respost sense text final visible.';
            file_put_contents($file, json_encode($job));
            echo "Job {$job['id']} fallit.\n";
            $processedJobs++;
            continue;
        }

        // Guardem resultat
        $job['status'] = 'done';
        $job['result'] = $description;
        file_put_contents($file, json_encode($job));

        echo "Job {$job['id']} completat.\n";
        $processedJobs++;
    }

    if ($runOnce && $processedJobs > 0) {
        break;
    }

    if ($runOnce) {
        exit(0);
    }

    sleep(2); // comprova cada 2 segons
}