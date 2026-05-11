<?php

function call_llm($messages, $tools = null) {
    $payload = [
        "model"       => "deepseek/deepseek-r1-0528-qwen3-8b",
        "messages"    => $messages,
        "temperature" => 0.2,  // baix: volem decisions consistents
        "max_tokens"  => 1000
    ];

    if ($tools) {
        $payload["tools"] = $tools;
        $payload["tool_choice"] = "auto"; // el model decideix si cal tool o no
    }

    $ch = curl_init("http://localhost:1234/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $response["choices"][0]["message"];
}