<?php

header('Content-Type: application/json');

$dataFile = __DIR__ . '/../data/todos.json';

// --- Helpers ---

function loadTodos(string $dataFile): array {
    if (!file_exists($dataFile)) {
        return [];
    }
    $raw = file_get_contents($dataFile);
    $decoded = json_decode($raw, true);
    // Reinitialize if corrupt
    if (!is_array($decoded)) {
        return [];
    }
    return $decoded;
}

function saveTodos(string $dataFile, array $todos): void {
    $dir = dirname($dataFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dataFile, json_encode(array_values($todos), JSON_PRETTY_PRINT));
}

function generateUuid(): string {
    // UUID v4 using random_bytes
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); // version 4
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // variant bits
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function respond(mixed $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// --- Routing ---

$method = $_SERVER['REQUEST_METHOD'];
$id     = $_GET['id'] ?? null;

switch ($method) {

    case 'GET':
        $todos = loadTodos($dataFile);
        respond(array_values($todos));

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true);
        $text = trim($body['text'] ?? '');

        if ($text === '') {
            respond(['error' => 'Text is required'], 400);
        }

        $todo = [
            'id'         => generateUuid(),
            'text'       => $text,
            'done'       => false,
            'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
        ];

        $todos   = loadTodos($dataFile);
        $todos[] = $todo;
        saveTodos($dataFile, $todos);

        respond($todo, 201);

    case 'PATCH':
        $todos = loadTodos($dataFile);
        $found = false;
        $updated = null;

        foreach ($todos as &$todo) {
            if ($todo['id'] === $id) {
                $todo['done'] = !$todo['done'];
                $updated = $todo;
                $found = true;
                break;
            }
        }
        unset($todo);

        if (!$found) {
            respond(['error' => 'Todo not found'], 404);
        }

        saveTodos($dataFile, $todos);
        respond($updated);

    case 'DELETE':
        $todos = loadTodos($dataFile);
        $initialCount = count($todos);

        $todos = array_filter($todos, fn($t) => $t['id'] !== $id);

        if (count($todos) === $initialCount) {
            respond(['error' => 'Todo not found'], 404);
        }

        saveTodos($dataFile, $todos);
        respond(['ok' => true]);

    default:
        respond(['error' => 'Method not allowed'], 405);
}
