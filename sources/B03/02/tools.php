<?php

// Definició que enviem al LLM
function get_tools_definition() {
    return [
        [
            "type" => "function",
            "function" => [
                "name" => "get_confirmed_attendees",
                "description" => "Retorna el nombre d'assistents amb estat confirmat per un event donat. Usar quan calgui saber quanta gent ha confirmat assistència.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "event_id" => ["type" => "integer", "description" => "ID de l'event"]
                    ],
                    "required" => ["event_id"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "get_venue_capacity",
                "description" => "Retorna la capacitat màxima del recinte associat a un event.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "event_id" => ["type" => "integer"]
                    ],
                    "required" => ["event_id"]
                ]
            ]
        ]
    ];
}

// Execució real de les eines (aquí aniria la teva BD)
function execute_tool($name, $args) {
    // Simulem BD — en producció seria una query real
    $fake_db = [
        42 => ["confirmed" => 847, "capacity" => 900],
        99 => ["confirmed" => 320, "capacity" => 300], // sold out!
    ];

    $event_id = $args["event_id"];

    if ($name === "get_confirmed_attendees") {
        return ["confirmed" => $fake_db[$event_id]["confirmed"] ?? 0];
    }

    if ($name === "get_venue_capacity") {
        return ["capacity" => $fake_db[$event_id]["capacity"] ?? 0];
    }

    return ["error" => "tool not found"];
}