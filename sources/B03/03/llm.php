<?php

function call_llm($messages, $tools = null) {
    $maxTokens = 2000;

    for ($attempt = 0; $attempt < 2; $attempt++) {
        $payload = [
            "model"       => "deepseek/deepseek-r1-0528-qwen3-8b",
            "messages"    => $messages,
            "temperature" => 0.2,
            "max_tokens"  => $maxTokens
        ];

        if ($tools) {
            $payload["tools"]       = $tools;
            $payload["tool_choice"] = "auto";
        }

        $ch = curl_init("http://localhost:1234/v1/chat/completions");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = curl_error($ch);
            echo "[ERROR LLM] Error CURL: $error\n";
            exit(1);
        }

        $response = json_decode($raw, true);
        if (!$response || empty($response["choices"])) {
            echo "[ERROR LLM] Resposta inesperada: $raw\n";
            exit(1);
        }

        $choice       = $response["choices"][0];
        $message      = $choice["message"] ?? [];
        $content      = trim($message["content"] ?? "");
        $finishReason = $choice["finish_reason"] ?? "";
        $hasToolCalls = !empty($message["tool_calls"]);

        // Alguns models de reasoning poden gastar tot el pressupost i deixar la resposta final buida.
        if ($content === "" && !$hasToolCalls && $finishReason === "length" && $attempt === 0) {
            $maxTokens = 4000;
            continue;
        }

        return $message;
    }

    echo "[ERROR LLM] No s'ha pogut obtenir resposta final del model.\n";
    exit(1);
}