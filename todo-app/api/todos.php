<?php

header('Content-Type: application/json');

$DATA_FILE = __DIR__ . '/../data/todos.json';
$DATA_DIR  = dirname($DATA_FILE);

function fail(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

function uuidv4(): string {
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function load_todos(string $file): array {
    if (!file_exists($file)) return [];
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_todos(string $file, array $todos): void {
    if (file_put_contents($file, json_encode(array_values($todos), JSON_PRETTY_PRINT)) === false) {
        fail(500, 'No s\'ha pogut escriure el fitxer');
    }
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $data = json_decode($raw, true);
    if (!is_array($data)) fail(400, 'JSON invàlid');
    return $data;
}

try {
    if (!is_dir($DATA_DIR)) {
        if (!@mkdir($DATA_DIR, 0775, true) && !is_dir($DATA_DIR)) {
            fail(500, 'No s\'ha pogut crear el directori de dades');
        }
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $todos  = load_todos($DATA_FILE);

    switch ($method) {
        case 'GET':
            echo json_encode(array_values($todos));
            break;

        case 'POST': {
            $body = read_json_body();
            $text = isset($body['text']) ? trim((string)$body['text']) : '';
            if ($text === '') fail(400, 'El camp "text" és obligatori');

            $todo = [
                'id'         => uuidv4(),
                'text'       => $text,
                'done'       => false,
                'created_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ];
            $todos[] = $todo;
            save_todos($DATA_FILE, $todos);
            http_response_code(201);
            echo json_encode($todo);
            break;
        }

        case 'PATCH': {
            $id = $_GET['id'] ?? '';
            if ($id === '') fail(400, 'Falta el paràmetre "id"');
            $body = read_json_body();
            if (!array_key_exists('done', $body)) fail(400, 'El camp "done" és obligatori');

            $found = false;
            foreach ($todos as &$t) {
                if (($t['id'] ?? null) === $id) {
                    $t['done'] = (bool)$body['done'];
                    $found = true;
                    $updated = $t;
                    break;
                }
            }
            unset($t);
            if (!$found) fail(404, 'Todo no trobat');
            save_todos($DATA_FILE, $todos);
            echo json_encode($updated);
            break;
        }

        case 'DELETE': {
            $id = $_GET['id'] ?? '';
            if ($id === '') fail(400, 'Falta el paràmetre "id"');

            $new = array_values(array_filter($todos, fn($t) => ($t['id'] ?? null) !== $id));
            if (count($new) === count($todos)) fail(404, 'Todo no trobat');
            save_todos($DATA_FILE, $new);
            echo json_encode(['ok' => true]);
            break;
        }

        default:
            fail(405, 'Mètode no permès');
    }
} catch (Throwable $e) {
    fail(500, $e->getMessage());
}
