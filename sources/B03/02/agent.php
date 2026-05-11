<?php
require "tools.php";
require "llm.php";

$user_input = "Tinc l'event 42. Quants confirmats tinc i puc afegir 20 places més?";

$messages = [
    ["role" => "system", "content" => "Ets un assistent de gestió d'esdeveniments. Usa les eines disponibles per respondre amb dades reals."],
    ["role" => "user",   "content" => $user_input]
];

echo "Usuari: $user_input\n\n";

// Bucle agent: màxim 5 iteracions per seguretat
for ($i = 0; $i < 5; $i++) {
    $response = call_llm($messages, get_tools_definition());

    // Si el model vol cridar una eina
    if (!empty($response["tool_calls"])) {
        foreach ($response["tool_calls"] as $tool_call) {
            $name = $tool_call["function"]["name"];
            $args = json_decode($tool_call["function"]["arguments"], true);

            echo "→ Agent crida: $name(" . json_encode($args) . ")\n";

            $result = execute_tool($name, $args);

            echo "← Resultat: " . json_encode($result) . "\n";

            // Afegim la crida i el resultat al context
            $messages[] = $response; // missatge de l'assistent amb tool_call
            $messages[] = [
                "role"         => "tool",
                "tool_call_id" => $tool_call["id"],
                "content"      => json_encode($result)
            ];
        }
        continue; // tornem a cridar el model amb el resultat
    }

    // Si no hi ha tool_call, el model ha generat la resposta final
    echo "\nAgent: " . $response["content"] . "\n";
    break;
}