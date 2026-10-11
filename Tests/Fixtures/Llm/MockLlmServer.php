<?php

/**
 * Router-Skript für `php -S`, das eine OpenAI-kompatible Chat-Completions-API nachbildet
 * (POST /v1/chat/completions). Die Antwort hängt von der letzten Nutzernachricht ab:
 * - "__error__": HTTP 500
 * - "__slow__": antwortet erst nach 2 Sekunden
 * - "__invalid__": Inhalt ist kein JSON
 * - "__think__": JSON mit vorangestelltem <think>-Block (Reasoning-Modelle)
 * - sonst: Antwort aus responses.json (Schlüssel = Frage) oder eine leere Zerlegung
 *
 * Die zuletzt empfangene Anfrage (Header und Body) wird in die Datei aus MOCK_LLM_REQUEST_LOG geschrieben.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $path !== '/v1/chat/completions') {
    http_response_code(404);
    echo json_encode(['error' => 'not found']);
    return true;
}

$body = (string)file_get_contents('php://input');
$request = json_decode($body, true) ?: [];

$log = getenv('MOCK_LLM_REQUEST_LOG');
if ($log) {
    file_put_contents($log, json_encode([
        'authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
        'body' => $request,
    ], JSON_UNESCAPED_UNICODE));
}

$question = '';
foreach (array_reverse($request['messages'] ?? []) as $message) {
    if (($message['role'] ?? '') === 'user') {
        $question = (string)$message['content'];
        break;
    }
}

$empty = [
    'text' => '', 'persons' => [], 'topics' => [], 'mediaTypes' => [], 'formContent' => [],
    'languages' => [], 'places' => [], 'dateFrom' => null, 'dateTo' => null, 'digitalOnly' => false,
];

switch ($question) {
    case '__error__':
        http_response_code(500);
        echo json_encode(['error' => 'internal error']);
        return true;
    case '__slow__':
        sleep(2);
        $content = json_encode($empty);
        break;
    case '__invalid__':
        $content = 'Das ist kein JSON';
        break;
    case '__think__':
        $content = "<think>Überlegung …</think>\n" . json_encode(['text' => 'gedacht'] + $empty);
        break;
    default:
        $responses = json_decode((string)file_get_contents(__DIR__ . '/responses.json'), true) ?: [];
        $content = json_encode($responses[$question] ?? $empty, JSON_UNESCAPED_UNICODE);
}

header('Content-Type: application/json');
echo json_encode([
    'id' => 'chatcmpl-mock',
    'object' => 'chat.completion',
    'model' => $request['model'] ?? '',
    'choices' => [[
        'index' => 0,
        'message' => ['role' => 'assistant', 'content' => $content],
        'finish_reason' => 'stop',
    ]],
    'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20, 'total_tokens' => 120],
], JSON_UNESCAPED_UNICODE);
return true;
