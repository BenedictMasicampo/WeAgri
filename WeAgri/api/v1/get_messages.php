<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

weagri_dashboard_headers();

$consultantId = (int) ($_GET['consultant_id'] ?? 0);

if ($consultantId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'consultant_id is required.'], JSON_UNESCAPED_SLASHES);
    exit;
}

function demo_messages_payload(int $consultantId): array
{
    return [
        'ok' => true,
        'source' => 'demo',
        'messages' => [
            [
                'id' => 0,
                'sender_id' => $consultantId,
                'receiver_id' => 0,
                'sender_type' => 'consultant',
                'message_text' => 'Hello. Share the crop, symptoms, and how long you have seen the problem.',
                'created_at' => date('Y-m-d H:i:s', strtotime('-7 minutes')),
            ],
        ],
    ];
}

try {
    $pdo = weagri_dashboard_pdo();
    $statement = $pdo->prepare(
        'SELECT id, sender_id, receiver_id, message_text, created_at
         FROM messages
         WHERE (sender_id = :farmer_id AND receiver_id = :consultant_id)
            OR (sender_id = :consultant_id AND receiver_id = :farmer_id)
         ORDER BY created_at ASC, id ASC'
    );
    $statement->execute([
        'farmer_id' => 0,
        'consultant_id' => $consultantId,
    ]);

    $messages = array_map(
        static fn (array $row): array => [
            'id' => (int) $row['id'],
            'sender_id' => (int) $row['sender_id'],
            'receiver_id' => (int) $row['receiver_id'],
            'sender_type' => (int) $row['sender_id'] === $consultantId ? 'consultant' : 'farmer',
            'message_text' => (string) $row['message_text'],
            'created_at' => (string) $row['created_at'],
        ],
        $statement->fetchAll()
    );

    echo json_encode([
        'ok' => true,
        'source' => 'mysql',
        'messages' => $messages,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(200);
    echo json_encode(demo_messages_payload($consultantId), JSON_UNESCAPED_SLASHES);
}
