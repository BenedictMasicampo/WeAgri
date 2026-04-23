<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

weagri_dashboard_headers();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST is required.'], JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);

if (!is_array($payload)) {
    $payload = [];
}

$consultantId = (int) ($payload['consultant_id'] ?? 0);
$messageText = trim((string) ($payload['message_text'] ?? ''));

if ($consultantId <= 0 || $messageText === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'consultant_id and message_text are required.'], JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = weagri_dashboard_pdo();

    $consultantStatement = $pdo->prepare('SELECT id FROM consultants WHERE id = :consultant_id LIMIT 1');
    $consultantStatement->execute(['consultant_id' => $consultantId]);

    if (!$consultantStatement->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Consultant not found.'], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $insertStatement = $pdo->prepare(
        'INSERT INTO messages (sender_id, receiver_id, message_text, created_at)
         VALUES (:sender_id, :receiver_id, :message_text, NOW())'
    );
    $insertStatement->execute([
        'sender_id' => 0,
        'receiver_id' => $consultantId,
        'message_text' => $messageText,
    ]);

    echo json_encode([
        'ok' => true,
        'message' => 'Message sent.',
        'message_id' => (int) $pdo->lastInsertId(),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(200);
    echo json_encode([
        'ok' => false,
        'source' => 'error',
        'message' => 'Message database is unavailable. Import database/dashboard_schema.sql to enable live chat.',
    ], JSON_UNESCAPED_SLASHES);
}
