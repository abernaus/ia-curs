<?php

function get_tools_definition() {
    return [
        [
            "type" => "function",
            "function" => [
                "name" => "list_active_events",
                "description" => "Retorna la llista d'events actius d'un organitzador amb el seu ID i nom.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "organizer_id" => ["type" => "integer"]
                    ],
                    "required" => ["organizer_id"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "get_event_stats",
                "description" => "Retorna estadístiques d'un event: confirmats, capacitat, ingressos cobrats i pendents.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "event_id" => ["type" => "integer"]
                    ],
                    "required" => ["event_id"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "get_event_feedback_score",
                "description" => "Retorna la puntuació mitjana de satisfacció dels assistents d'un event (escala 1-5).",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "event_id" => ["type" => "integer"]
                    ],
                    "required" => ["event_id"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "get_pending_tasks",
                "description" => "Retorna tasques pendents crítiques d'un event (pagaments, contractes, permisos).",
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

function execute_tool($name, $args) {

    // ── Dades simulades (en producció: queries reals) ──────────────────
    $events = [
        101 => ["name" => "Summit Tech BCN",   "date" => "2025-06-10"],
        102 => ["name" => "Fira Gastronòmica", "date" => "2025-06-22"],
        103 => ["name" => "HR Leaders Day",    "date" => "2025-07-05"],
    ];

    $stats = [
        101 => ["confirmed" => 847, "capacity" => 900, "revenue_paid" => 38200, "revenue_pending" => 4100],
        102 => ["confirmed" => 290, "capacity" => 300, "revenue_paid" => 14500, "revenue_pending" => 8900],
        103 => ["confirmed" => 120, "capacity" => 500, "revenue_paid" =>  6000, "revenue_pending" => 1200],
    ];

    $feedback = [
        101 => ["score" => 4.2, "responses" => 312],
        102 => ["score" => 3.1, "responses" => 89],
        103 => ["score" => null, "responses" => 0], // event futur, sense feedback
    ];

    $tasks = [
        101 => ["pending" => ["Confirmació catering (2 dies)", "Revisió llista VIP"]],
        102 => ["pending" => ["Permís municipal PENDENT", "8 pagaments vençuts +30 dies", "Contracte recinte no signat"]],
        103 => ["pending" => ["Confirmar ponents principals"]],
    ];
    // ──────────────────────────────────────────────────────────────────

    if ($name === "list_active_events") {
        return ["events" => array_map(
            fn($id, $e) => ["event_id" => $id, "name" => $e["name"], "date" => $e["date"]],
            array_keys($events), $events
        )];
    }

    if ($name === "get_event_stats") {
        $id = $args["event_id"];
        return $stats[$id] ?? ["error" => "event no trobat"];
    }

    if ($name === "get_event_feedback_score") {
        $id = $args["event_id"];
        return $feedback[$id] ?? ["error" => "event no trobat"];
    }

    if ($name === "get_pending_tasks") {
        $id = $args["event_id"];
        return $tasks[$id] ?? ["error" => "event no trobat"];
    }

    return ["error" => "tool desconeguda: $name"];
}