<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

weagri_dashboard_headers();

function demo_consultants_payload(string $source, string $message): array
{
    return [
        'ok' => $source !== 'error',
        'source' => $source,
        'message' => $message,
        'consultants' => [
            ['id' => 1, 'name' => 'Dr. Liza Santos', 'specialty' => 'Plant Pathology', 'is_online' => true, 'rating' => 4.9],
            ['id' => 2, 'name' => 'Marco Reyes', 'specialty' => 'Soil Health', 'is_online' => true, 'rating' => 4.8],
            ['id' => 3, 'name' => 'Ana Villanueva', 'specialty' => 'Pest Management', 'is_online' => false, 'rating' => 4.7],
            ['id' => 4, 'name' => 'Rafael Cruz', 'specialty' => 'Irrigation Planning', 'is_online' => true, 'rating' => 4.8],
        ],
    ];
}

try {
    $pdo = weagri_dashboard_pdo();
    $statement = $pdo->prepare(
        'SELECT id, name, specialty, is_online
         FROM consultants
         ORDER BY is_online DESC, name ASC'
    );
    $statement->execute();

    $consultants = array_map(
        static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'specialty' => (string) $row['specialty'],
            'is_online' => (bool) $row['is_online'],
            'rating' => round(4.6 + (((int) $row['id'] % 4) / 10), 1),
        ],
        $statement->fetchAll()
    );

    echo json_encode([
        'ok' => true,
        'source' => 'mysql',
        'consultants' => $consultants,
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(200);
    echo json_encode(
        demo_consultants_payload('error', 'Consultant database is unavailable. Import database/dashboard_schema.sql to enable live consultants.'),
        JSON_UNESCAPED_SLASHES
    );
}
