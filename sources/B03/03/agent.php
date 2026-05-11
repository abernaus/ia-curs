<?php
require "tools.php";
require "llm.php";

$organizer_id = 7; // simulem l'organitzador autenticat
$user_input   = "Com van els meus events actius? Quin té més risc ara mateix?";

$messages = [
    [
        "role"    => "system",
        "content" => "Ets l'assistent de gestió d'events de biPeek.
Tens accés a eines per consultar dades reals dels events.
Normes:
- Sempre comença llistant els events actius abans de consultar detalls.
- Per avaluar risc, considera: % ocupació, pagaments pendents, tasques crítiques i puntuació de satisfacció.
- Resumeix de forma executiva: l'organitzador vol decisions, no taules de dades.
- No inventes dades. Si una eina retorna error, informa-ho explícitament.
- L'organizer_id és sempre: $organizer_id"
    ],
    [
        "role"    => "user",
        "content" => $user_input
    ]
];

echo "─────────────────────────────────────\n";
echo "Usuari: $user_input\n";
echo "─────────────────────────────────────\n\n";

$max_iterations = 10;

for ($i = 0; $i < $max_iterations; $i++) {

    $response = call_llm($messages, get_tools_definition());

    // El model vol cridar eines
    if (!empty($response["tool_calls"])) {

        // Afegim el missatge de l'assistent amb les tool_calls al context
        $messages[] = $response;

        foreach ($response["tool_calls"] as $tool_call) {
            $name   = $tool_call["function"]["name"];
            $args   = json_decode($tool_call["function"]["arguments"], true);
            $result = execute_tool($name, $args);

            echo "→ " . $name . "(" . json_encode($args) . ")\n";
            echo "← " . json_encode($result) . "\n\n";

            // Tornem el resultat al model
            $messages[] = [
                "role"         => "tool",
                "tool_call_id" => $tool_call["id"],
                "content"      => json_encode($result)
            ];
        }

        continue; // tornem a cridar el model
    }

    // El model ha generat la resposta final
    echo "─────────────────────────────────────\n";
    echo "Agent:\n\n" . $response["content"] . "\n";
    echo "─────────────────────────────────────\n";
    echo "[Iteracions: $i | Tools usades: " . (count($messages) - 2) / 2 . "]\n";
    break;
}