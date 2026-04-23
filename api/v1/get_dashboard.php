<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

weagri_dashboard_headers();

function weagri_demo_dashboard_payload(string $source, string $message): array
{
    $recordedAt = date('Y-m-d H:i:s');
    $weather = [];
    $soilHealth = [];

    foreach ([50, 40, 30, 20, 10, 0] as $index => $minutesAgo) {
        $timestamp = date('Y-m-d H:i:s', strtotime(sprintf('-%d minutes', $minutesAgo)));
        $weather[] = [
            'label' => date('H:i', strtotime($timestamp)),
            'value' => [27.8, 28.1, 28.7, 29.2, 28.6, 28.4][$index],
            'recorded_at' => $timestamp,
        ];
        $soilHealth[] = [
            'label' => date('H:i', strtotime($timestamp)),
            'soil_moisture' => [67.2, 65.9, 64.1, 62.7, 63.8, 64.5][$index],
            'crop_health' => [92.0, 91.4, 90.8, 89.6, 90.2, 91.0][$index],
            'recorded_at' => $timestamp,
        ];
    }

    $marketPrices = [
        ['id' => 0, 'crop_name' => 'Rice', 'price' => 52.4, 'trend' => 'up', 'updated_at' => $recordedAt],
        ['id' => 0, 'crop_name' => 'Corn', 'price' => 31.75, 'trend' => 'down', 'updated_at' => $recordedAt],
        ['id' => 0, 'crop_name' => 'Tomato', 'price' => 68.2, 'trend' => 'up', 'updated_at' => $recordedAt],
        ['id' => 0, 'crop_name' => 'Eggplant', 'price' => 58.0, 'trend' => 'stable', 'updated_at' => $recordedAt],
    ];

    return [
        'ok' => $source !== 'error',
        'source' => $source,
        'message' => $message,
        'metrics' => [
            'temperature' => 28.4,
            'soil_moisture' => 64.5,
            'crop_health' => 91.0,
            'timestamp' => $recordedAt,
        ],
        'market_prices' => $marketPrices,
        'trends' => [
            'weather' => $weather,
            'soil_health' => $soilHealth,
            'market_prices' => array_map(
                static fn (array $row): array => [
                    'label' => $row['crop_name'],
                    'value' => $row['price'],
                    'trend' => $row['trend'],
                ],
                $marketPrices
            ),
        ],
        'insight' => 'Field readings are steady. Soil moisture is in a workable range, crop health is strong, and market prices are mixed, so keep monitoring before making large selling or irrigation decisions.',
    ];
}

function fetch_latest_metrics_log(PDO $pdo): ?array
{
    try {
        $statement = $pdo->prepare(
            'SELECT id, sensor_node_id, temperature, soil_moisture, crop_health_index, recorded_at
             FROM agri_metrics_log
             ORDER BY recorded_at DESC, id DESC
             LIMIT 1'
        );
        $statement->execute();
        $row = $statement->fetch();

        return $row ?: null;
    } catch (Throwable $exception) {
        return null;
    }
}

function fetch_legacy_metrics(PDO $pdo): ?array
{
    try {
        $statement = $pdo->prepare(
            'SELECT id, temperature, soil_moisture, crop_health, timestamp
             FROM agri_metrics
             ORDER BY timestamp DESC, id DESC
             LIMIT 1'
        );
        $statement->execute();
        $row = $statement->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'sensor_node_id' => 'legacy-node',
            'temperature' => (float) $row['temperature'],
            'soil_moisture' => (float) $row['soil_moisture'],
            'crop_health_index' => (float) $row['crop_health'],
            'recorded_at' => (string) $row['timestamp'],
        ];
    } catch (Throwable $exception) {
        return null;
    }
}

function fetch_metrics_trends(PDO $pdo): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT temperature, soil_moisture, crop_health_index, recorded_at
             FROM agri_metrics_log
             ORDER BY recorded_at DESC, id DESC
             LIMIT 12'
        );
        $statement->execute();
        $rows = array_reverse($statement->fetchAll());

        return array_map(
            static fn (array $row): array => [
                'label' => date('H:i', strtotime((string) $row['recorded_at'])),
                'temperature' => (float) $row['temperature'],
                'soil_moisture' => (float) $row['soil_moisture'],
                'crop_health' => (float) $row['crop_health_index'],
                'recorded_at' => (string) $row['recorded_at'],
            ],
            $rows
        );
    } catch (Throwable $exception) {
        return [];
    }
}

function fetch_legacy_metrics_trends(PDO $pdo): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT temperature, soil_moisture, crop_health, timestamp
             FROM agri_metrics
             ORDER BY timestamp DESC, id DESC
             LIMIT 12'
        );
        $statement->execute();
        $rows = array_reverse($statement->fetchAll());

        return array_map(
            static fn (array $row): array => [
                'label' => date('H:i', strtotime((string) $row['timestamp'])),
                'temperature' => (float) $row['temperature'],
                'soil_moisture' => (float) $row['soil_moisture'],
                'crop_health' => (float) $row['crop_health'],
                'recorded_at' => (string) $row['timestamp'],
            ],
            $rows
        );
    } catch (Throwable $exception) {
        return [];
    }
}

function fetch_market_prices(PDO $pdo): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT id, crop_name, price_per_kg, trend_direction, updated_at
             FROM market_hub_prices
             ORDER BY crop_name ASC'
        );
        $statement->execute();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'crop_name' => (string) $row['crop_name'],
                'price' => (float) $row['price_per_kg'],
                'trend' => (string) $row['trend_direction'],
                'updated_at' => (string) $row['updated_at'],
            ],
            $statement->fetchAll()
        );
    } catch (Throwable $exception) {
        return [];
    }
}

function fetch_legacy_market_prices(PDO $pdo): array
{
    try {
        $statement = $pdo->prepare(
            'SELECT id, crop_name, price, trend
             FROM market_prices
             ORDER BY crop_name ASC'
        );
        $statement->execute();

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'crop_name' => (string) $row['crop_name'],
                'price' => (float) $row['price'],
                'trend' => (string) $row['trend'],
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            $statement->fetchAll()
        );
    } catch (Throwable $exception) {
        return [];
    }
}

function build_dashboard_insight(array $metrics, array $marketPrices): string
{
    $soil = (float) $metrics['soil_moisture'];
    $health = (float) $metrics['crop_health'];
    $temperature = (float) $metrics['temperature'];
    $risingMarkets = count(array_filter($marketPrices, static fn (array $row): bool => $row['trend'] === 'up'));

    if ($soil < 45.0) {
        return 'Soil moisture is trending low. Check the field surface and root zone before irrigating, then water deeply rather than lightly if the crop is stressed.';
    }

    if ($health < 70.0) {
        return 'Crop health needs attention. Walk the field today, check leaf color, pest marks, and drainage, then open a consultation if symptoms are spreading.';
    }

    if ($temperature >= 33.0) {
        return 'Temperature is high. Avoid midday spraying, monitor wilting, and prioritize early morning irrigation checks.';
    }

    return $risingMarkets > 1
        ? 'Field conditions look steady and several market prices are rising. Keep monitoring moisture and consider timing harvest or sales carefully.'
        : 'Field readings are steady. Keep scouting weekly, maintain even moisture, and watch market changes before making large selling decisions.';
}

try {
    $pdo = weagri_dashboard_pdo();
    $latest = fetch_latest_metrics_log($pdo) ?? fetch_legacy_metrics($pdo);
    $trendRows = fetch_metrics_trends($pdo);

    if (!$trendRows) {
        $trendRows = fetch_legacy_metrics_trends($pdo);
    }

    $marketPrices = fetch_market_prices($pdo);

    if (!$marketPrices) {
        $marketPrices = fetch_legacy_market_prices($pdo);
    }

    if (!$latest) {
        echo json_encode(
            weagri_demo_dashboard_payload('empty', 'No dashboard sensor readings have been recorded yet.'),
            JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    $metrics = [
        'id' => (int) ($latest['id'] ?? 0),
        'sensor_node_id' => (string) ($latest['sensor_node_id'] ?? 'field-node'),
        'temperature' => (float) $latest['temperature'],
        'soil_moisture' => (float) $latest['soil_moisture'],
        'crop_health' => (float) $latest['crop_health_index'],
        'timestamp' => (string) $latest['recorded_at'],
    ];

    echo json_encode([
        'ok' => true,
        'source' => 'mysql',
        'message' => 'Live dashboard metrics loaded.',
        'metrics' => $metrics,
        'market_prices' => $marketPrices,
        'trends' => [
            'weather' => array_map(
                static fn (array $row): array => [
                    'label' => $row['label'],
                    'value' => (float) $row['temperature'],
                    'recorded_at' => $row['recorded_at'],
                ],
                $trendRows
            ),
            'soil_health' => array_map(
                static fn (array $row): array => [
                    'label' => $row['label'],
                    'soil_moisture' => (float) $row['soil_moisture'],
                    'crop_health' => (float) $row['crop_health'],
                    'recorded_at' => $row['recorded_at'],
                ],
                $trendRows
            ),
            'market_prices' => array_map(
                static fn (array $row): array => [
                    'label' => $row['crop_name'],
                    'value' => (float) $row['price'],
                    'trend' => $row['trend'],
                ],
                $marketPrices
            ),
        ],
        'insight' => build_dashboard_insight($metrics, $marketPrices),
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(200);
    echo json_encode(
        weagri_demo_dashboard_payload(
            'error',
            'Dashboard database is unavailable. Import database/dashboard_schema.sql in phpMyAdmin to enable live MySQL data.'
        ),
        JSON_UNESCAPED_SLASHES
    );
}
