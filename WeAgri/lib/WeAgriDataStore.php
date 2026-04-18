<?php
declare(strict_types=1);

final class WeAgriDataStore
{
    private array $config;
    private ?PDO $pdo = null;
    private string $storagePath;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->storagePath = (string) ($config['storage_path'] ?? (__DIR__ . '/../storage/data.json'));
        date_default_timezone_set((string) ($config['timezone'] ?? 'UTC'));
        $this->ensureJsonStorageExists();
        $this->connectDatabase();
    }

    public function getBootstrap(?array $user = null): array
    {
        $consultations = $this->getConsultations($user);
        $experts = $this->getExperts();
        $notifications = $this->getNotifications($user, $consultations);
        $permissions = $this->permissionsFor($user);

        return [
            'source' => $this->pdo ? 'mysql' : 'demo-storage',
            'source_label' => $this->pdo ? 'Connected to MySQL' : 'Demo storage mode',
            'generated_at' => $this->now(),
            'auth' => [
                'authenticated' => $user !== null,
                'role' => $user['role'] ?? 'guest',
                'role_label' => $this->roleLabel($user['role'] ?? 'guest'),
                'user' => $this->publicUser($user),
            ],
            'permissions' => $permissions,
            'stats' => [
                'online_experts' => count(array_filter($experts, fn(array $expert): bool => $expert['status'] === 'online')),
                'active_consultations' => count(array_filter(
                    $consultations,
                    fn(array $consultation): bool => $consultation['status'] !== 'resolved'
                )),
                'average_response_minutes' => $this->averageResponseMinutes($experts),
                'unread_notifications' => count(array_filter(
                    $notifications,
                    fn(array $notification): bool => !$notification['is_read']
                )),
            ],
            'consultations' => $consultations,
            'experts' => $experts,
            'consultant_options' => array_map(fn(array $expert): array => [
                'id' => (int) $expert['id'],
                'full_name' => $expert['full_name'],
                'specialty' => $expert['specialty'],
                'status' => $expert['status'],
            ], $experts),
            'notifications' => $notifications,
            'knowledge_highlights' => $this->getKnowledgeHighlights(),
            'admin' => $user !== null && $user['role'] === 'admin' ? $this->getAdminOverview() : null,
        ];
    }

    public function askAssistant(string $message): array
    {
        return $this->assistant()->answer($message);
    }

    public function authenticateUser(string $email, string $password): ?array
    {
        $email = mb_strtolower($this->sanitizeString($email));
        if ($email === '' || $password === '') {
            return null;
        }

        $user = $this->getUserByEmail($email);
        if (!$user) {
            return null;
        }

        return password_verify($password, (string) $user['password_hash']) ? $user : null;
    }

    public function registerUser(array $payload): array
    {
        $role = mb_strtolower($this->sanitizeString((string) ($payload['role'] ?? '')));
        $fullName = $this->sanitizeString((string) ($payload['full_name'] ?? ''));
        $email = mb_strtolower($this->sanitizeString((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');
        $location = $this->sanitizeString((string) ($payload['location'] ?? ''));
        $primaryCrop = $this->sanitizeString((string) ($payload['primary_crop'] ?? ''));
        $specialty = $this->sanitizeString((string) ($payload['specialty'] ?? ''));
        $bio = $this->sanitizeString((string) ($payload['bio'] ?? ''));

        if ($fullName === '') {
            throw new InvalidArgumentException('Full name is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email is required.');
        }

        if (strlen($password) < 6) {
            throw new InvalidArgumentException('Password must be at least 6 characters long.');
        }

        if (!in_array($role, ['admin', 'farmer', 'consultant'], true)) {
            throw new InvalidArgumentException('Please choose a valid account role.');
        }

        if ($this->getUserByEmail($email)) {
            throw new InvalidArgumentException('That email is already registered.');
        }

        if ($this->pdo) {
            return $this->registerUserInDatabase([
                'role' => $role,
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'location' => $location,
                'primary_crop' => $primaryCrop,
                'specialty' => $specialty,
                'bio' => $bio,
            ]);
        }

        return $this->registerUserInJson([
            'role' => $role,
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'location' => $location,
            'primary_crop' => $primaryCrop,
            'specialty' => $specialty,
            'bio' => $bio,
        ]);
    }

    public function getUserById(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        if ($this->pdo) {
            $statement = $this->pdo->prepare(
                'SELECT id, full_name, email, password_hash, role, linked_farmer_id, linked_expert_id, created_at
                 FROM users
                 WHERE id = :id
                 LIMIT 1'
            );
            $statement->execute(['id' => $userId]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->hydrateUserRow($row) : null;
        }

        $data = $this->readJson();
        foreach ($data['users'] as $user) {
            if ((int) $user['id'] === $userId) {
                return $this->hydrateJsonUser($user, $data);
            }
        }

        return null;
    }

    public function getConsultation(int $consultationId, ?array $user = null): ?array
    {
        foreach ($this->getConsultations($user) as $consultation) {
            if ((int) $consultation['id'] === $consultationId) {
                return $consultation;
            }
        }

        return null;
    }

    public function createConsultation(array $payload, array $user): array
    {
        $this->assertRole($user, ['farmer']);

        $farmer = $this->getFarmerProfileForUser($user);
        if (!$farmer) {
            throw new InvalidArgumentException('Farmer profile is not linked to this account.');
        }

        $crop = $this->sanitizeString($payload['crop'] ?? $farmer['primary_crop'] ?? 'General farming');
        $location = $this->sanitizeString($payload['location'] ?? $farmer['location'] ?? 'Farm location');
        $concern = $this->sanitizeString($payload['concern'] ?? '');
        $urgency = $this->normalizeUrgency($payload['urgency'] ?? 'medium');

        if ($concern === '') {
            throw new InvalidArgumentException('Consultation concern is required.');
        }

        $assistantResult = $this->assistant()->answer($concern, [
            'crop' => $crop,
            'urgency' => $urgency,
        ]);

        $category = $assistantResult['category'] ?: 'General Advisory';
        $title = $this->sanitizeString($payload['title'] ?? $assistantResult['suggested_title']);
        $assignedExpert = $assistantResult['escalate_to_expert'] ? $this->matchExpert($category) : null;
        $status = $assignedExpert ? 'expert_assigned' : 'monitoring';
        $summary = $assignedExpert
            ? 'AI triage completed and assigned the case to an available consultant.'
            : 'AI triage generated a recommended action plan for continued monitoring.';

        $consultationData = [
            'farmer_id' => (int) $farmer['id'],
            'farmer_name' => $farmer['full_name'],
            'title' => $title,
            'crop' => $crop,
            'category' => $category,
            'urgency' => $urgency,
            'status' => $status,
            'location' => $location,
            'assigned_expert_id' => $assignedExpert['id'] ?? null,
            'assigned_expert_name' => $assignedExpert['full_name'] ?? null,
            'summary' => $summary,
        ];

        return $this->pdo
            ? $this->createConsultationInDatabase($consultationData, $concern, $assistantResult, $assignedExpert)
            : $this->createConsultationInJson($consultationData, $concern, $assistantResult, $assignedExpert);
    }

    public function addFarmerMessage(int $consultationId, string $message, array $user): ?array
    {
        $this->assertRole($user, ['farmer']);

        $cleanMessage = $this->sanitizeString($message);
        if ($cleanMessage === '') {
            throw new InvalidArgumentException('Message is required.');
        }

        $consultation = $this->getConsultation($consultationId, $user);
        if (!$consultation) {
            return null;
        }

        $assistantResult = $this->assistant()->answer($cleanMessage, [
            'crop' => $consultation['crop'],
            'category' => $consultation['category'],
            'urgency' => $consultation['urgency'],
        ]);

        $assignedExpert = $consultation['assigned_expert_id']
            ? $this->findExpertById((int) $consultation['assigned_expert_id'])
            : ($assistantResult['escalate_to_expert'] ? $this->matchExpert((string) $consultation['category']) : null);

        return $this->pdo
            ? $this->addFarmerMessageInDatabase($consultation, $cleanMessage, $assistantResult, $assignedExpert)
            : $this->addFarmerMessageInJson($consultation, $cleanMessage, $assistantResult, $assignedExpert);
    }

    public function addConsultantResponse(int $consultationId, string $message, array $user): ?array
    {
        $this->assertRole($user, ['consultant']);

        $cleanMessage = $this->sanitizeString($message);
        if ($cleanMessage === '') {
            throw new InvalidArgumentException('Response is required.');
        }

        $consultation = $this->getConsultation($consultationId, $user);
        if (!$consultation) {
            return null;
        }

        $expert = $this->getExpertProfileForUser($user);
        if (!$expert) {
            throw new InvalidArgumentException('Consultant profile is not linked to this account.');
        }

        if (!$this->canConsultantRespond($consultation, $expert)) {
            throw new InvalidArgumentException('This consultation is already assigned to another consultant.');
        }

        return $this->pdo
            ? $this->addConsultantResponseInDatabase($consultation, $cleanMessage, $expert)
            : $this->addConsultantResponseInJson($consultation, $cleanMessage, $expert);
    }

    public function assignConsultant(int $consultationId, int $consultantId, array $user): ?array
    {
        $this->assertRole($user, ['admin']);

        $consultation = $this->getConsultation($consultationId, $user);
        if (!$consultation) {
            return null;
        }

        $expert = $this->findExpertById($consultantId);
        if (!$expert) {
            throw new InvalidArgumentException('Consultant was not found.');
        }

        return $this->pdo
            ? $this->assignConsultantInDatabase($consultation, $expert)
            : $this->assignConsultantInJson($consultation, $expert);
    }

    public function updateConsultationStatus(int $consultationId, string $status, array $user): ?array
    {
        $this->assertRole($user, ['admin']);

        $status = mb_strtolower($this->sanitizeString($status));
        if (!in_array($status, ['ai_triage', 'expert_assigned', 'monitoring', 'resolved'], true)) {
            throw new InvalidArgumentException('Please choose a valid consultation status.');
        }

        $consultation = $this->getConsultation($consultationId, $user);
        if (!$consultation) {
            return null;
        }

        return $this->pdo
            ? $this->updateConsultationStatusInDatabase($consultation, $status)
            : $this->updateConsultationStatusInJson($consultation, $status);
    }

    public function markNotificationRead(int $notificationId, ?array $user = null): bool
    {
        if ($notificationId <= 0) {
            return false;
        }

        $visibleNotifications = $this->getNotifications($user, $this->getConsultations($user));
        $canRead = array_filter($visibleNotifications, fn(array $notification): bool => (int) $notification['id'] === $notificationId);

        if ($user !== null && $canRead === []) {
            return false;
        }

        if ($this->pdo) {
            $statement = $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id');
            return $statement->execute(['id' => $notificationId]);
        }

        $data = $this->readJson();
        foreach ($data['notifications'] as &$notification) {
            if ((int) $notification['id'] === $notificationId) {
                $notification['is_read'] = true;
            }
        }

        $this->writeJson($data);

        return true;
    }

    public function getNotifications(?array $user = null, ?array $consultations = null): array
    {
        $consultations = $consultations ?? $this->getConsultations($user);
        $visibleConsultationIds = array_map(fn(array $consultation): int => (int) $consultation['id'], $consultations);

        if ($this->pdo) {
            $statement = $this->pdo->query(
                'SELECT id, title, body, type, is_read, consultation_id, created_at
                 FROM notifications
                 ORDER BY created_at DESC, id DESC
                 LIMIT 50'
            );
            $notifications = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $notifications = array_map(function (array $notification): array {
                $notification['id'] = (int) $notification['id'];
                $notification['consultation_id'] = $notification['consultation_id'] !== null
                    ? (int) $notification['consultation_id']
                    : null;
                $notification['is_read'] = (bool) $notification['is_read'];
                return $notification;
            }, $notifications);

            return $this->filterNotifications($notifications, $user, $visibleConsultationIds);
        }

        $data = $this->readJson();
        usort($data['notifications'], fn(array $left, array $right): int => strcmp($right['created_at'], $left['created_at']));

        return $this->filterNotifications(array_slice($data['notifications'], 0, 50), $user, $visibleConsultationIds);
    }

    public function getExperts(): array
    {
        if ($this->pdo) {
            $statement = $this->pdo->query(
                "SELECT id, full_name, specialty, status, response_minutes, bio
                 FROM experts
                 ORDER BY FIELD(status, 'online', 'busy', 'offline'), response_minutes ASC, full_name ASC"
            );
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'full_name' => $row['full_name'],
                    'specialty' => $row['specialty'],
                    'status' => $row['status'],
                    'response_minutes' => (int) $row['response_minutes'],
                    'bio' => $row['bio'],
                ];
            }, $rows);
        }

        $data = $this->readJson();
        usort($data['experts'], function (array $left, array $right): int {
            $order = ['online' => 1, 'busy' => 2, 'offline' => 3];
            $leftRank = $order[$left['status']] ?? 99;
            $rightRank = $order[$right['status']] ?? 99;

            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }

            return $left['response_minutes'] <=> $right['response_minutes'];
        });

        return $data['experts'];
    }

    public function getKnowledgeHighlights(): array
    {
        $entries = $this->getKnowledgeBase();

        return array_map(function (array $entry): array {
            return [
                'id' => (int) $entry['id'],
                'title' => $entry['title'],
                'topic' => $entry['topic'],
                'source' => $entry['source'],
                'excerpt' => $this->excerpt((string) $entry['content'], 150),
                'recommendations' => array_slice($this->normalizeLines($entry['recommendations'] ?? []), 0, 2),
            ];
        }, array_slice($entries, 0, 4));
    }

    public function getConsultations(?array $user = null): array
    {
        return $this->pdo
            ? $this->getConsultationsFromDatabase($user)
            : $this->getConsultationsFromJson($user);
    }

    private function getUserByEmail(string $email): ?array
    {
        if ($this->pdo) {
            $statement = $this->pdo->prepare(
                'SELECT id, full_name, email, password_hash, role, linked_farmer_id, linked_expert_id, created_at
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->hydrateUserRow($row) : null;
        }

        $data = $this->readJson();
        foreach ($data['users'] as $user) {
            if (mb_strtolower((string) $user['email']) === $email) {
                return $this->hydrateJsonUser($user, $data);
            }
        }

        return null;
    }

    private function registerUserInDatabase(array $payload): array
    {
        $this->pdo->beginTransaction();

        try {
            $linkedFarmerId = null;
            $linkedExpertId = null;

            if ($payload['role'] === 'farmer') {
                $statement = $this->pdo->prepare(
                    'INSERT INTO farmers (full_name, location, primary_crop, created_at)
                     VALUES (:full_name, :location, :primary_crop, :created_at)'
                );
                $statement->execute([
                    'full_name' => $payload['full_name'],
                    'location' => $payload['location'] !== '' ? $payload['location'] : 'Farm location',
                    'primary_crop' => $payload['primary_crop'] !== '' ? $payload['primary_crop'] : 'Mixed crops',
                    'created_at' => $this->now(),
                ]);
                $linkedFarmerId = (int) $this->pdo->lastInsertId();
            }

            if ($payload['role'] === 'consultant') {
                $statement = $this->pdo->prepare(
                    'INSERT INTO experts (full_name, specialty, status, response_minutes, bio, created_at)
                     VALUES (:full_name, :specialty, :status, :response_minutes, :bio, :created_at)'
                );
                $statement->execute([
                    'full_name' => $payload['full_name'],
                    'specialty' => $payload['specialty'] !== '' ? $payload['specialty'] : 'General Agronomy',
                    'status' => 'online',
                    'response_minutes' => 12,
                    'bio' => $payload['bio'] !== '' ? $payload['bio'] : 'Supports farmers with crop, soil, and field diagnostics.',
                    'created_at' => $this->now(),
                ]);
                $linkedExpertId = (int) $this->pdo->lastInsertId();
            }

            $statement = $this->pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, linked_farmer_id, linked_expert_id, created_at)
                 VALUES (:full_name, :email, :password_hash, :role, :linked_farmer_id, :linked_expert_id, :created_at)'
            );
            $statement->execute([
                'full_name' => $payload['full_name'],
                'email' => $payload['email'],
                'password_hash' => $payload['password_hash'],
                'role' => $payload['role'],
                'linked_farmer_id' => $linkedFarmerId,
                'linked_expert_id' => $linkedExpertId,
                'created_at' => $this->now(),
            ]);

            $userId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $this->getUserById($userId) ?? [];
    }

    private function registerUserInJson(array $payload): array
    {
        $data = $this->readJson();
        $linkedFarmerId = null;
        $linkedExpertId = null;

        if ($payload['role'] === 'farmer') {
            $linkedFarmerId = $this->nextId($data['farmers']);
            $data['farmers'][] = [
                'id' => $linkedFarmerId,
                'full_name' => $payload['full_name'],
                'location' => $payload['location'] !== '' ? $payload['location'] : 'Farm location',
                'primary_crop' => $payload['primary_crop'] !== '' ? $payload['primary_crop'] : 'Mixed crops',
            ];
        }

        if ($payload['role'] === 'consultant') {
            $linkedExpertId = $this->nextId($data['experts']);
            $data['experts'][] = [
                'id' => $linkedExpertId,
                'full_name' => $payload['full_name'],
                'specialty' => $payload['specialty'] !== '' ? $payload['specialty'] : 'General Agronomy',
                'status' => 'online',
                'response_minutes' => 12,
                'bio' => $payload['bio'] !== '' ? $payload['bio'] : 'Supports farmers with crop, soil, and field diagnostics.',
            ];
        }

        $userId = $this->nextId($data['users']);
        $data['users'][] = [
            'id' => $userId,
            'full_name' => $payload['full_name'],
            'email' => $payload['email'],
            'password_hash' => $payload['password_hash'],
            'role' => $payload['role'],
            'linked_farmer_id' => $linkedFarmerId,
            'linked_expert_id' => $linkedExpertId,
            'created_at' => $this->now(),
        ];

        $this->writeJson($data);

        return $this->getUserById($userId) ?? [];
    }

    private function getConsultationsFromDatabase(?array $user): array
    {
        $consultationStatement = $this->pdo->query(
            'SELECT c.id, c.farmer_id, c.title, c.crop, c.category, c.urgency, c.status, c.location,
                    c.assigned_expert_id, c.summary, c.created_at, c.updated_at,
                    f.full_name AS farmer_name, e.full_name AS expert_name
             FROM consultations c
             INNER JOIN farmers f ON f.id = c.farmer_id
             LEFT JOIN experts e ON e.id = c.assigned_expert_id
             ORDER BY c.updated_at DESC, c.id DESC'
        );
        $consultations = $consultationStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $messageStatement = $this->pdo->query(
            'SELECT id, consultation_id, sender_type, sender_name, message, `references`, created_at
             FROM messages
             ORDER BY created_at ASC, id ASC'
        );
        $messageRows = $messageStatement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $messagesByConsultation = [];

        foreach ($messageRows as $message) {
            $messagesByConsultation[(int) $message['consultation_id']][] = [
                'id' => (int) $message['id'],
                'consultation_id' => (int) $message['consultation_id'],
                'sender_type' => $message['sender_type'],
                'sender_name' => $message['sender_name'],
                'message' => $message['message'],
                'references' => $this->decodeJsonArray($message['references'] ?? null),
                'created_at' => $message['created_at'],
            ];
        }

        $consultations = array_map(function (array $consultation) use ($messagesByConsultation): array {
            $consultationId = (int) $consultation['id'];
            $messages = $messagesByConsultation[$consultationId] ?? [];
            $lastMessage = $messages !== [] ? end($messages) : null;

            return [
                'id' => $consultationId,
                'farmer_id' => (int) $consultation['farmer_id'],
                'farmer_name' => $consultation['farmer_name'],
                'title' => $consultation['title'],
                'crop' => $consultation['crop'],
                'category' => $consultation['category'],
                'urgency' => $consultation['urgency'],
                'status' => $consultation['status'],
                'status_label' => $this->formatStatus((string) $consultation['status']),
                'location' => $consultation['location'],
                'assigned_expert_id' => $consultation['assigned_expert_id'] !== null
                    ? (int) $consultation['assigned_expert_id']
                    : null,
                'assigned_expert_name' => $consultation['expert_name'],
                'summary' => $consultation['summary'],
                'created_at' => $consultation['created_at'],
                'updated_at' => $consultation['updated_at'],
                'messages' => $messages,
                'message_count' => count($messages),
                'last_message_preview' => $lastMessage ? $this->excerpt((string) $lastMessage['message'], 92) : 'No messages yet.',
            ];
        }, $consultations);

        return array_values(array_filter($consultations, fn(array $consultation): bool => $this->canViewConsultation($consultation, $user)));
    }

    private function getConsultationsFromJson(?array $user): array
    {
        $data = $this->readJson();

        $farmers = [];
        foreach ($data['farmers'] as $farmer) {
            $farmers[(int) $farmer['id']] = $farmer;
        }

        $experts = [];
        foreach ($data['experts'] as $expert) {
            $experts[(int) $expert['id']] = $expert;
        }

        $messagesByConsultation = [];
        foreach ($data['messages'] as $message) {
            $messagesByConsultation[(int) $message['consultation_id']][] = $message;
        }

        usort($data['consultations'], fn(array $left, array $right): int => strcmp($right['updated_at'], $left['updated_at']));

        $consultations = array_map(function (array $consultation) use ($farmers, $experts, $messagesByConsultation): array {
            $consultationId = (int) $consultation['id'];
            $messages = $messagesByConsultation[$consultationId] ?? [];
            $lastMessage = $messages !== [] ? end($messages) : null;
            $farmer = $farmers[(int) $consultation['farmer_id']] ?? ['full_name' => 'Farmer'];
            $expert = $consultation['assigned_expert_id'] !== null
                ? ($experts[(int) $consultation['assigned_expert_id']] ?? null)
                : null;

            return [
                'id' => $consultationId,
                'farmer_id' => (int) $consultation['farmer_id'],
                'farmer_name' => $farmer['full_name'],
                'title' => $consultation['title'],
                'crop' => $consultation['crop'],
                'category' => $consultation['category'],
                'urgency' => $consultation['urgency'],
                'status' => $consultation['status'],
                'status_label' => $this->formatStatus((string) $consultation['status']),
                'location' => $consultation['location'],
                'assigned_expert_id' => $consultation['assigned_expert_id'] !== null
                    ? (int) $consultation['assigned_expert_id']
                    : null,
                'assigned_expert_name' => $expert['full_name'] ?? null,
                'summary' => $consultation['summary'],
                'created_at' => $consultation['created_at'],
                'updated_at' => $consultation['updated_at'],
                'messages' => $messages,
                'message_count' => count($messages),
                'last_message_preview' => $lastMessage ? $this->excerpt((string) $lastMessage['message'], 92) : 'No messages yet.',
            ];
        }, $data['consultations']);

        return array_values(array_filter($consultations, fn(array $consultation): bool => $this->canViewConsultation($consultation, $user)));
    }

    private function createConsultationInDatabase(
        array $consultationData,
        string $concern,
        array $assistantResult,
        ?array $assignedExpert
    ): array {
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO consultations
                    (farmer_id, title, crop, category, urgency, status, location, assigned_expert_id, summary, created_at, updated_at)
                 VALUES
                    (:farmer_id, :title, :crop, :category, :urgency, :status, :location, :assigned_expert_id, :summary, :created_at, :updated_at)'
            );
            $timestamp = $this->now();
            $statement->execute([
                'farmer_id' => $consultationData['farmer_id'],
                'title' => $consultationData['title'],
                'crop' => $consultationData['crop'],
                'category' => $consultationData['category'],
                'urgency' => $consultationData['urgency'],
                'status' => $consultationData['status'],
                'location' => $consultationData['location'],
                'assigned_expert_id' => $consultationData['assigned_expert_id'],
                'summary' => $consultationData['summary'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $consultationId = (int) $this->pdo->lastInsertId();

            $this->insertMessageRow($consultationId, 'farmer', (string) $consultationData['farmer_name'], $concern);
            $this->insertMessageRow($consultationId, 'ai', 'AgroLLM', (string) $assistantResult['reply'], $assistantResult['references']);

            if ($assignedExpert) {
                $this->insertNotificationRow(
                    'Consultation assigned',
                    $assignedExpert['full_name'] . ' has been assigned to review your concern.',
                    'consultation',
                    $consultationId
                );
            } else {
                $this->insertNotificationRow(
                    'AI action plan ready',
                    'AgroLLM generated first-response guidance for your new consultation.',
                    'advisory',
                    $consultationId
                );
            }

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $this->getConsultation($consultationId, ['role' => 'admin']) ?? [];
    }

    private function createConsultationInJson(
        array $consultationData,
        string $concern,
        array $assistantResult,
        ?array $assignedExpert
    ): array {
        $data = $this->readJson();
        $consultationId = $this->nextId($data['consultations']);
        $timestamp = $this->now();

        $data['consultations'][] = [
            'id' => $consultationId,
            'farmer_id' => $consultationData['farmer_id'],
            'title' => $consultationData['title'],
            'crop' => $consultationData['crop'],
            'category' => $consultationData['category'],
            'urgency' => $consultationData['urgency'],
            'status' => $consultationData['status'],
            'location' => $consultationData['location'],
            'assigned_expert_id' => $consultationData['assigned_expert_id'],
            'summary' => $consultationData['summary'],
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        $data['messages'][] = $this->messageRecord(
            $this->nextId($data['messages']),
            $consultationId,
            'farmer',
            (string) $consultationData['farmer_name'],
            $concern,
            [],
            $timestamp
        );
        $data['messages'][] = $this->messageRecord(
            $this->nextId($data['messages']),
            $consultationId,
            'ai',
            'AgroLLM',
            (string) $assistantResult['reply'],
            $assistantResult['references'],
            $timestamp
        );

        $data['notifications'][] = $this->notificationRecord(
            $this->nextId($data['notifications']),
            $assignedExpert ? 'Consultation assigned' : 'AI action plan ready',
            $assignedExpert
                ? $assignedExpert['full_name'] . ' has been assigned to review your concern.'
                : 'AgroLLM generated first-response guidance for your new consultation.',
            $assignedExpert ? 'consultation' : 'advisory',
            false,
            $consultationId,
            $timestamp
        );

        $this->writeJson($data);

        return $this->getConsultation($consultationId, ['role' => 'admin']) ?? [];
    }

    private function addFarmerMessageInDatabase(
        array $consultation,
        string $message,
        array $assistantResult,
        ?array $assignedExpert
    ): array {
        $this->pdo->beginTransaction();

        try {
            $this->insertMessageRow((int) $consultation['id'], 'farmer', (string) $consultation['farmer_name'], $message);
            $this->insertMessageRow((int) $consultation['id'], 'ai', 'AgroLLM', (string) $assistantResult['reply'], $assistantResult['references']);

            $status = $consultation['status'] === 'resolved' ? 'monitoring' : $consultation['status'];
            $expertId = $consultation['assigned_expert_id'] ?: ($assignedExpert['id'] ?? null);

            if ($expertId && $status !== 'resolved') {
                $status = 'expert_assigned';
            }

            $statement = $this->pdo->prepare(
                'UPDATE consultations
                 SET status = :status, assigned_expert_id = :assigned_expert_id, updated_at = :updated_at
                 WHERE id = :id'
            );
            $statement->execute([
                'status' => $status,
                'assigned_expert_id' => $expertId,
                'updated_at' => $this->now(),
                'id' => (int) $consultation['id'],
            ]);

            $notificationBody = $assignedExpert
                ? $assignedExpert['full_name'] . ' can now review the latest update in your consultation.'
                : 'AgroLLM added fresh guidance to your consultation.';

            $this->insertNotificationRow(
                'Consultation updated',
                $notificationBody,
                'consultation',
                (int) $consultation['id']
            );

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $this->getConsultation((int) $consultation['id'], ['role' => 'admin']) ?? [];
    }

    private function addFarmerMessageInJson(
        array $consultation,
        string $message,
        array $assistantResult,
        ?array $assignedExpert
    ): array {
        $data = $this->readJson();
        $timestamp = $this->now();

        $data['messages'][] = $this->messageRecord(
            $this->nextId($data['messages']),
            (int) $consultation['id'],
            'farmer',
            (string) $consultation['farmer_name'],
            $message,
            [],
            $timestamp
        );
        $data['messages'][] = $this->messageRecord(
            $this->nextId($data['messages']),
            (int) $consultation['id'],
            'ai',
            'AgroLLM',
            (string) $assistantResult['reply'],
            $assistantResult['references'],
            $timestamp
        );

        foreach ($data['consultations'] as &$row) {
            if ((int) $row['id'] !== (int) $consultation['id']) {
                continue;
            }

            if ($assignedExpert && !$row['assigned_expert_id']) {
                $row['assigned_expert_id'] = (int) $assignedExpert['id'];
            }

            if ($row['status'] === 'resolved') {
                $row['status'] = 'monitoring';
            }

            if ($row['assigned_expert_id']) {
                $row['status'] = 'expert_assigned';
            }

            $row['updated_at'] = $timestamp;
        }

        $data['notifications'][] = $this->notificationRecord(
            $this->nextId($data['notifications']),
            'Consultation updated',
            $assignedExpert
                ? $assignedExpert['full_name'] . ' can now review the latest update in your consultation.'
                : 'AgroLLM added fresh guidance to your consultation.',
            'consultation',
            false,
            (int) $consultation['id'],
            $timestamp
        );

        $this->writeJson($data);

        return $this->getConsultation((int) $consultation['id'], ['role' => 'admin']) ?? [];
    }

    private function addConsultantResponseInDatabase(array $consultation, string $message, array $expert): array
    {
        $this->pdo->beginTransaction();

        try {
            $this->insertMessageRow((int) $consultation['id'], 'expert', (string) $expert['full_name'], $message);

            $statement = $this->pdo->prepare(
                'UPDATE consultations
                 SET status = :status, assigned_expert_id = :assigned_expert_id, updated_at = :updated_at
                 WHERE id = :id'
            );
            $statement->execute([
                'status' => $consultation['status'] === 'resolved' ? 'monitoring' : 'expert_assigned',
                'assigned_expert_id' => (int) $expert['id'],
                'updated_at' => $this->now(),
                'id' => (int) $consultation['id'],
            ]);

            $this->insertNotificationRow(
                'Consultant response received',
                $expert['full_name'] . ' replied to your consultation.',
                'consultation',
                (int) $consultation['id']
            );

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return $this->getConsultation((int) $consultation['id'], ['role' => 'admin']) ?? [];
    }

    private function addConsultantResponseInJson(array $consultation, string $message, array $expert): array
    {
        $data = $this->readJson();
        $timestamp = $this->now();

        $data['messages'][] = $this->messageRecord(
            $this->nextId($data['messages']),
            (int) $consultation['id'],
            'expert',
            (string) $expert['full_name'],
            $message,
            [],
            $timestamp
        );

        foreach ($data['consultations'] as &$row) {
            if ((int) $row['id'] !== (int) $consultation['id']) {
                continue;
            }

            $row['assigned_expert_id'] = (int) $expert['id'];
            $row['status'] = $row['status'] === 'resolved' ? 'monitoring' : 'expert_assigned';
            $row['updated_at'] = $timestamp;
        }

        $data['notifications'][] = $this->notificationRecord(
            $this->nextId($data['notifications']),
            'Consultant response received',
            $expert['full_name'] . ' replied to your consultation.',
            'consultation',
            false,
            (int) $consultation['id'],
            $timestamp
        );

        $this->writeJson($data);

        return $this->getConsultation((int) $consultation['id'], ['role' => 'admin']) ?? [];
    }

    private function assignConsultantInDatabase(array $consultation, array $expert): array
    {
        $statement = $this->pdo->prepare(
            'UPDATE consultations
             SET assigned_expert_id = :assigned_expert_id,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $statement->execute([
            'assigned_expert_id' => (int) $expert['id'],
            'status' => $consultation['status'] === 'resolved' ? 'resolved' : 'expert_assigned',
            'updated_at' => $this->now(),
            'id' => (int) $consultation['id'],
        ]);

        $this->insertNotificationRow(
            'Consultant assignment updated',
            $expert['full_name'] . ' has been assigned by the administrator.',
            'consultation',
            (int) $consultation['id']
        );

        return $this->getConsultation((int) $consultation['id'], ['role' => 'admin']) ?? [];
    }

    private function assignConsultantInJson(array $consultation, array $expert): array
    {
        $data = $this->readJson();
        $timestamp = $this->now();

        foreach ($data['consultations'] as &$row) {
            if ((int) $row['id'] !== (int) $consultation['id']) {
                continue;
            }

            $row['assigned_expert_id'] = (int) $expert['id'];
            if ($row['status'] !== 'resolved') {
                $row['status'] = 'expert_assigned';
            }
            $row['updated_at'] = $timestamp;
        }

        $data['notifications'][] = $this->notificationRecord(
            $this->nextId($data['notifications']),
            'Consultant assignment updated',
            $expert['full_name'] . ' has been assigned by the administrator.',
            'consultation',
            false,
            (int) $consultation['id'],
            $timestamp
        );

        $this->writeJson($data);

        return $this->getConsultation((int) $consultation['id'], ['role' => 'admin']) ?? [];
    }

    private function updateConsultationStatusInDatabase(array $consultation, string $status): array
    {
        $statement = $this->pdo->prepare(
            'UPDATE consultations
             SET status = :status, updated_at = :updated_at
             WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'updated_at' => $this->now(),
            'id' => (int) $consultation['id'],
        ]);

        $this->insertNotificationRow(
            'Consultation status updated',
            'The administrator changed the consultation status to ' . $this->formatStatus($status) . '.',
            'consultation',
            (int) $consultation['id']
        );

        return $this->getConsultation((int) $consultation['id'], ['role' => 'admin']) ?? [];
    }

    private function updateConsultationStatusInJson(array $consultation, string $status): array
    {
        $data = $this->readJson();
        $timestamp = $this->now();

        foreach ($data['consultations'] as &$row) {
            if ((int) $row['id'] !== (int) $consultation['id']) {
                continue;
            }

            $row['status'] = $status;
            $row['updated_at'] = $timestamp;
        }

        $data['notifications'][] = $this->notificationRecord(
            $this->nextId($data['notifications']),
            'Consultation status updated',
            'The administrator changed the consultation status to ' . $this->formatStatus($status) . '.',
            'consultation',
            false,
            (int) $consultation['id'],
            $timestamp
        );

        $this->writeJson($data);

        return $this->getConsultation((int) $consultation['id'], ['role' => 'admin']) ?? [];
    }

    private function filterNotifications(array $notifications, ?array $user, array $visibleConsultationIds): array
    {
        if ($user === null) {
            return [];
        }

        return array_values(array_filter($notifications, function (array $notification) use ($user, $visibleConsultationIds): bool {
            if (in_array((string) $notification['type'], ['weather', 'system'], true)) {
                return true;
            }

            if ($user['role'] === 'admin') {
                return true;
            }

            $consultationId = $notification['consultation_id'] ?? null;
            if ($consultationId === null) {
                return false;
            }

            return in_array((int) $consultationId, $visibleConsultationIds, true);
        }));
    }

    private function getKnowledgeBase(): array
    {
        if ($this->pdo) {
            $statement = $this->pdo->query(
                'SELECT id, title, topic, content, recommendations, tags, source
                 FROM knowledge_base
                 ORDER BY id ASC'
            );

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $data = $this->readJson();
        return $data['knowledge_base'];
    }

    private function getFarmerProfileForUser(array $user): ?array
    {
        $farmerId = (int) ($user['linked_farmer_id'] ?? 0);
        if ($farmerId <= 0) {
            return null;
        }

        if ($this->pdo) {
            $statement = $this->pdo->prepare(
                'SELECT id, full_name, location, primary_crop
                 FROM farmers
                 WHERE id = :id
                 LIMIT 1'
            );
            $statement->execute(['id' => $farmerId]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }

        $data = $this->readJson();
        foreach ($data['farmers'] as $farmer) {
            if ((int) $farmer['id'] === $farmerId) {
                return $farmer;
            }
        }

        return null;
    }

    private function getExpertProfileForUser(array $user): ?array
    {
        $expertId = (int) ($user['linked_expert_id'] ?? 0);
        return $expertId > 0 ? $this->findExpertById($expertId) : null;
    }

    private function findExpertById(int $expertId): ?array
    {
        foreach ($this->getExperts() as $expert) {
            if ((int) $expert['id'] === $expertId) {
                return $expert;
            }
        }

        return null;
    }

    private function matchExpert(string $category): ?array
    {
        $target = match ($category) {
            'Pest and Disease' => 'Pest Management',
            'Soil Management' => 'Soil Health',
            'Crop Nutrition' => 'Crop Nutrition',
            'Water and Irrigation' => 'Irrigation & Farm Practices',
            default => null,
        };

        $experts = $this->getExperts();
        $preferred = array_values(array_filter($experts, function (array $expert) use ($target): bool {
            if ($expert['status'] === 'offline') {
                return false;
            }

            return $target ? $expert['specialty'] === $target : true;
        }));

        return $preferred[0] ?? $experts[0] ?? null;
    }

    private function getAdminOverview(): array
    {
        $users = $this->getUsers();
        $counts = ['admin' => 0, 'farmer' => 0, 'consultant' => 0];

        foreach ($users as $user) {
            $role = $user['role'];
            $counts[$role] = ($counts[$role] ?? 0) + 1;
        }

        return [
            'user_counts' => $counts,
            'users' => array_map(fn(array $user): array => [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'role_label' => $this->roleLabel($user['role']),
            ], array_slice($users, 0, 10)),
        ];
    }

    private function getUsers(): array
    {
        if ($this->pdo) {
            $statement = $this->pdo->query(
                'SELECT id, full_name, email, password_hash, role, linked_farmer_id, linked_expert_id, created_at
                 FROM users
                 ORDER BY created_at DESC, id DESC'
            );
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(fn(array $row): array => $this->hydrateUserRow($row), $rows);
        }

        $data = $this->readJson();
        $users = array_map(fn(array $user): array => $this->hydrateJsonUser($user, $data), $data['users']);
        usort($users, fn(array $left, array $right): int => strcmp($right['created_at'], $left['created_at']));

        return $users;
    }

    private function hydrateUserRow(array $row): array
    {
        $user = [
            'id' => (int) $row['id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'password_hash' => $row['password_hash'],
            'role' => $row['role'],
            'linked_farmer_id' => $row['linked_farmer_id'] !== null ? (int) $row['linked_farmer_id'] : null,
            'linked_expert_id' => $row['linked_expert_id'] !== null ? (int) $row['linked_expert_id'] : null,
            'created_at' => $row['created_at'],
        ];

        $farmer = $user['linked_farmer_id'] ? $this->getFarmerProfileForUser($user) : null;
        $expert = $user['linked_expert_id'] ? $this->getExpertProfileForUser($user) : null;

        if ($farmer) {
            $user['location'] = $farmer['location'];
            $user['primary_crop'] = $farmer['primary_crop'];
        }

        if ($expert) {
            $user['specialty'] = $expert['specialty'];
            $user['consultant_status'] = $expert['status'];
        }

        return $user;
    }

    private function hydrateJsonUser(array $user, array $data): array
    {
        $user['id'] = (int) $user['id'];
        $user['linked_farmer_id'] = $user['linked_farmer_id'] !== null ? (int) $user['linked_farmer_id'] : null;
        $user['linked_expert_id'] = $user['linked_expert_id'] !== null ? (int) $user['linked_expert_id'] : null;

        if ($user['linked_farmer_id']) {
            foreach ($data['farmers'] as $farmer) {
                if ((int) $farmer['id'] === (int) $user['linked_farmer_id']) {
                    $user['location'] = $farmer['location'];
                    $user['primary_crop'] = $farmer['primary_crop'];
                }
            }
        }

        if ($user['linked_expert_id']) {
            foreach ($data['experts'] as $expert) {
                if ((int) $expert['id'] === (int) $user['linked_expert_id']) {
                    $user['specialty'] = $expert['specialty'];
                    $user['consultant_status'] = $expert['status'];
                }
            }
        }

        return $user;
    }

    private function publicUser(?array $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'role_label' => $this->roleLabel($user['role']),
            'location' => $user['location'] ?? null,
            'primary_crop' => $user['primary_crop'] ?? null,
            'specialty' => $user['specialty'] ?? null,
            'linked_farmer_id' => $user['linked_farmer_id'] ?? null,
            'linked_expert_id' => $user['linked_expert_id'] ?? null,
        ];
    }

    private function permissionsFor(?array $user): array
    {
        $role = $user['role'] ?? 'guest';

        return [
            'can_create_consultation' => $role === 'farmer',
            'can_send_farmer_message' => $role === 'farmer',
            'can_send_consultant_response' => $role === 'consultant',
            'can_manage_assignments' => $role === 'admin',
            'can_manage_status' => $role === 'admin',
            'can_view_private_dashboard' => $role !== 'guest',
        ];
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Administrator',
            'farmer' => 'Farmer',
            'consultant' => 'Consultant',
            default => 'Guest',
        };
    }

    private function canViewConsultation(array $consultation, ?array $user): bool
    {
        if ($user === null) {
            return false;
        }

        return match ($user['role']) {
            'admin' => true,
            'farmer' => (int) $consultation['farmer_id'] === (int) ($user['linked_farmer_id'] ?? 0),
            'consultant' => true,
            default => false,
        };
    }

    private function canConsultantRespond(array $consultation, array $expert): bool
    {
        $assignedExpertId = (int) ($consultation['assigned_expert_id'] ?? 0);
        return $assignedExpertId === 0 || $assignedExpertId === (int) $expert['id'];
    }

    private function averageResponseMinutes(array $experts): int
    {
        $available = array_filter($experts, fn(array $expert): bool => $expert['status'] !== 'offline');
        if ($available === []) {
            return 0;
        }

        $total = array_sum(array_map(fn(array $expert): int => (int) $expert['response_minutes'], $available));

        return (int) round($total / count($available));
    }

    private function insertMessageRow(
        int $consultationId,
        string $senderType,
        string $senderName,
        string $message,
        array $references = []
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO messages (consultation_id, sender_type, sender_name, message, `references`, created_at)
             VALUES (:consultation_id, :sender_type, :sender_name, :message, :references, :created_at)'
        );
        $statement->execute([
            'consultation_id' => $consultationId,
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'message' => $message,
            'references' => json_encode(array_values($references), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $this->now(),
        ]);
    }

    private function insertNotificationRow(
        string $title,
        string $body,
        string $type,
        ?int $consultationId = null
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO notifications (title, body, type, is_read, consultation_id, created_at)
             VALUES (:title, :body, :type, 0, :consultation_id, :created_at)'
        );
        $statement->execute([
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'consultation_id' => $consultationId,
            'created_at' => $this->now(),
        ]);
    }

    private function assistant(): AgroAssistant
    {
        return new AgroAssistant($this->getKnowledgeBase(), $this->getExperts());
    }

    private function connectDatabase(): void
    {
        $db = $this->config['db'] ?? [];
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $db['host'] ?? '127.0.0.1',
            $db['port'] ?? '3306',
            $db['name'] ?? 'weagri'
        );

        try {
            $pdo = new PDO(
                $dsn,
                (string) ($db['user'] ?? 'root'),
                (string) ($db['pass'] ?? ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $check = $pdo->query("SHOW TABLES LIKE 'consultations'");
            if ($check->fetchColumn() === false) {
                return;
            }

            $this->ensureUsersTable($pdo);
            $this->ensureDefaultUsersInDatabase($pdo);
            $this->pdo = $pdo;
        } catch (Throwable) {
            $this->pdo = null;
        }
    }

    private function ensureUsersTable(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                full_name VARCHAR(120) NOT NULL,
                email VARCHAR(160) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'farmer', 'consultant') NOT NULL,
                linked_farmer_id INT NULL,
                linked_expert_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_users_farmer FOREIGN KEY (linked_farmer_id) REFERENCES farmers(id) ON DELETE SET NULL,
                CONSTRAINT fk_users_expert FOREIGN KEY (linked_expert_id) REFERENCES experts(id) ON DELETE SET NULL
            )"
        );
    }

    private function ensureDefaultUsersInDatabase(PDO $pdo): void
    {
        $defaults = [
            [
                'full_name' => 'WeAgri Administrator',
                'email' => 'admin@weagri.local',
                'password' => 'admin123',
                'role' => 'admin',
                'linked_farmer_id' => null,
                'linked_expert_id' => null,
            ],
            [
                'full_name' => 'Nestor Reyes',
                'email' => 'farmer@weagri.local',
                'password' => 'farmer123',
                'role' => 'farmer',
                'linked_farmer_id' => 1,
                'linked_expert_id' => null,
            ],
            [
                'full_name' => 'Dr. Liza Dizon',
                'email' => 'liza@weagri.local',
                'password' => 'consultant123',
                'role' => 'consultant',
                'linked_farmer_id' => null,
                'linked_expert_id' => 1,
            ],
            [
                'full_name' => 'Engr. Mateo Cruz',
                'email' => 'mateo@weagri.local',
                'password' => 'consultant123',
                'role' => 'consultant',
                'linked_farmer_id' => null,
                'linked_expert_id' => 2,
            ],
            [
                'full_name' => 'Dr. Ana Velasco',
                'email' => 'ana@weagri.local',
                'password' => 'consultant123',
                'role' => 'consultant',
                'linked_farmer_id' => null,
                'linked_expert_id' => 3,
            ],
            [
                'full_name' => 'Engr. Paulo Ramirez',
                'email' => 'paulo@weagri.local',
                'password' => 'consultant123',
                'role' => 'consultant',
                'linked_farmer_id' => null,
                'linked_expert_id' => 4,
            ],
        ];

        $lookup = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $insert = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, linked_farmer_id, linked_expert_id, created_at)
             VALUES (:full_name, :email, :password_hash, :role, :linked_farmer_id, :linked_expert_id, :created_at)'
        );

        foreach ($defaults as $default) {
            $lookup->execute(['email' => $default['email']]);
            if ($lookup->fetchColumn() !== false) {
                continue;
            }

            $insert->execute([
                'full_name' => $default['full_name'],
                'email' => $default['email'],
                'password_hash' => password_hash($default['password'], PASSWORD_DEFAULT),
                'role' => $default['role'],
                'linked_farmer_id' => $default['linked_farmer_id'],
                'linked_expert_id' => $default['linked_expert_id'],
                'created_at' => $this->now(),
            ]);
        }
    }

    private function ensureJsonStorageExists(): void
    {
        if (is_file($this->storagePath)) {
            return;
        }

        $directory = dirname($this->storagePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $this->storagePath,
            json_encode($this->seedData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function readJson(): array
    {
        $raw = file_get_contents($this->storagePath);
        $data = json_decode((string) $raw, true);

        return is_array($data) ? $data : $this->seedData();
    }

    private function writeJson(array $data): void
    {
        file_put_contents(
            $this->storagePath,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function seedData(): array
    {
        return [
            'users' => [
                [
                    'id' => 1,
                    'full_name' => 'WeAgri Administrator',
                    'email' => 'admin@weagri.local',
                    'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'linked_farmer_id' => null,
                    'linked_expert_id' => null,
                    'created_at' => '2026-04-18 08:00:00',
                ],
                [
                    'id' => 2,
                    'full_name' => 'Nestor Reyes',
                    'email' => 'farmer@weagri.local',
                    'password_hash' => password_hash('farmer123', PASSWORD_DEFAULT),
                    'role' => 'farmer',
                    'linked_farmer_id' => 1,
                    'linked_expert_id' => null,
                    'created_at' => '2026-04-18 08:05:00',
                ],
                [
                    'id' => 3,
                    'full_name' => 'Dr. Liza Dizon',
                    'email' => 'liza@weagri.local',
                    'password_hash' => password_hash('consultant123', PASSWORD_DEFAULT),
                    'role' => 'consultant',
                    'linked_farmer_id' => null,
                    'linked_expert_id' => 1,
                    'created_at' => '2026-04-18 08:10:00',
                ],
                [
                    'id' => 4,
                    'full_name' => 'Engr. Mateo Cruz',
                    'email' => 'mateo@weagri.local',
                    'password_hash' => password_hash('consultant123', PASSWORD_DEFAULT),
                    'role' => 'consultant',
                    'linked_farmer_id' => null,
                    'linked_expert_id' => 2,
                    'created_at' => '2026-04-18 08:12:00',
                ],
                [
                    'id' => 5,
                    'full_name' => 'Dr. Ana Velasco',
                    'email' => 'ana@weagri.local',
                    'password_hash' => password_hash('consultant123', PASSWORD_DEFAULT),
                    'role' => 'consultant',
                    'linked_farmer_id' => null,
                    'linked_expert_id' => 3,
                    'created_at' => '2026-04-18 08:14:00',
                ],
                [
                    'id' => 6,
                    'full_name' => 'Engr. Paulo Ramirez',
                    'email' => 'paulo@weagri.local',
                    'password_hash' => password_hash('consultant123', PASSWORD_DEFAULT),
                    'role' => 'consultant',
                    'linked_farmer_id' => null,
                    'linked_expert_id' => 4,
                    'created_at' => '2026-04-18 08:16:00',
                ],
            ],
            'farmers' => [
                [
                    'id' => 1,
                    'full_name' => 'Nestor Reyes',
                    'location' => 'Science City of Munoz, Nueva Ecija',
                    'primary_crop' => 'Rice',
                ],
            ],
            'experts' => [
                [
                    'id' => 1,
                    'full_name' => 'Dr. Liza Dizon',
                    'specialty' => 'Pest Management',
                    'status' => 'online',
                    'response_minutes' => 4,
                    'bio' => 'Helps farmers respond to disease pressure, insects, and crop protection risks.',
                ],
                [
                    'id' => 2,
                    'full_name' => 'Engr. Mateo Cruz',
                    'specialty' => 'Soil Health',
                    'status' => 'online',
                    'response_minutes' => 7,
                    'bio' => 'Focuses on soil fertility, pH correction, and field rehabilitation planning.',
                ],
                [
                    'id' => 3,
                    'full_name' => 'Dr. Ana Velasco',
                    'specialty' => 'Crop Nutrition',
                    'status' => 'busy',
                    'response_minutes' => 11,
                    'bio' => 'Supports nutrient planning and deficiency diagnosis for vegetables and cereals.',
                ],
                [
                    'id' => 4,
                    'full_name' => 'Engr. Paulo Ramirez',
                    'specialty' => 'Irrigation & Farm Practices',
                    'status' => 'online',
                    'response_minutes' => 9,
                    'bio' => 'Guides irrigation scheduling, drainage, and practical field recovery decisions.',
                ],
            ],
            'consultations' => [
                [
                    'id' => 101,
                    'farmer_id' => 1,
                    'title' => 'Rice pest and disease: brown diamond-shaped leaf spots',
                    'crop' => 'Rice',
                    'category' => 'Pest and Disease',
                    'urgency' => 'high',
                    'status' => 'expert_assigned',
                    'location' => 'Science City of Munoz, Nueva Ecija',
                    'assigned_expert_id' => 1,
                    'summary' => 'AI triage detected a likely fungal issue and assigned the case to a consultant.',
                    'created_at' => '2026-04-19 08:18:00',
                    'updated_at' => '2026-04-19 08:30:00',
                ],
                [
                    'id' => 102,
                    'farmer_id' => 1,
                    'title' => 'Pechay crop nutrition: yellowing leaves after fertilizer change',
                    'crop' => 'Pechay',
                    'category' => 'Crop Nutrition',
                    'urgency' => 'medium',
                    'status' => 'monitoring',
                    'location' => 'Science City of Munoz, Nueva Ecija',
                    'assigned_expert_id' => null,
                    'summary' => 'AgroLLM suggested a first-response nutrition check and monitoring plan.',
                    'created_at' => '2026-04-18 15:05:00',
                    'updated_at' => '2026-04-18 15:16:00',
                ],
            ],
            'messages' => [
                $this->messageRecord(
                    1001,
                    101,
                    'farmer',
                    'Nestor Reyes',
                    'My rice leaves have brown diamond-shaped spots after three days of rain.',
                    [],
                    '2026-04-19 08:18:00'
                ),
                $this->messageRecord(
                    1002,
                    101,
                    'ai',
                    'AgroLLM',
                    "AgroLLM matched your concern to Pest and Disease guidance for Rice.\n\nImmediate steps:\n- Remove heavily infected leaves if the infection is still localized.\n- Improve air flow and avoid prolonged leaf wetness where possible.\n- Monitor whether lesions expand after continuous rain.\n\nThis looks urgent or complex enough that a human agricultural adviser should confirm the diagnosis and next action.\n\nKnowledge used:\n- Managing rice blast after wet weather - Philippine Rice Disease Field Guide",
                    ['Managing rice blast after wet weather - Philippine Rice Disease Field Guide'],
                    '2026-04-19 08:20:00'
                ),
                $this->messageRecord(
                    1003,
                    101,
                    'expert',
                    'Dr. Liza Dizon',
                    "I reviewed your Rice concern and the leaf symptoms still suggest blast pressure after continuous rain. Please keep monitoring the spread and avoid actions that keep the leaves wet for long periods.",
                    [],
                    '2026-04-19 08:30:00'
                ),
                $this->messageRecord(
                    1004,
                    102,
                    'farmer',
                    'Nestor Reyes',
                    'My pechay leaves started turning pale after I changed fertilizer this week.',
                    [],
                    '2026-04-18 15:05:00'
                ),
                $this->messageRecord(
                    1005,
                    102,
                    'ai',
                    'AgroLLM',
                    "AgroLLM matched your concern to Crop Nutrition guidance for Pechay.\n\nImmediate steps:\n- Compare recent fertilizer rates with the previous schedule before applying more.\n- Inspect whether yellowing starts from older leaves, which may suggest nitrogen deficiency.\n- Water evenly and avoid overcorrecting with concentrated fertilizer.\n\nThis looks suitable for AI first-level guidance, but you should keep monitoring the crop and escalate if symptoms spread.\n\nKnowledge used:\n- Nitrogen deficiency in leafy vegetables - DA Crop Nutrition Notes",
                    ['Nitrogen deficiency in leafy vegetables - DA Crop Nutrition Notes'],
                    '2026-04-18 15:16:00'
                ),
            ],
            'notifications' => [
                $this->notificationRecord(
                    201,
                    'Weather advisory',
                    'Heavy rainfall is expected this afternoon. Check field drainage and standing water areas.',
                    'weather',
                    false,
                    null,
                    '2026-04-19 07:55:00'
                ),
                $this->notificationRecord(
                    202,
                    'Consultation assigned',
                    'Dr. Liza Dizon has been assigned to review your rice disease concern.',
                    'consultation',
                    false,
                    101,
                    '2026-04-19 08:21:00'
                ),
                $this->notificationRecord(
                    203,
                    'AI update available',
                    'AgroLLM posted a first-response nutrition plan for your pechay concern.',
                    'advisory',
                    true,
                    102,
                    '2026-04-18 15:16:00'
                ),
            ],
            'knowledge_base' => [
                [
                    'id' => 1,
                    'title' => 'Managing rice blast after wet weather',
                    'topic' => 'Pest and Disease',
                    'content' => 'Rice blast often appears as spindle or diamond-shaped lesions and becomes more aggressive after long wet periods and dense crop canopy conditions.',
                    'recommendations' => [
                        'Remove heavily infected leaves if the infection is still localized.',
                        'Improve air flow and avoid prolonged leaf wetness where possible.',
                        'Monitor whether lesions expand after continuous rain.',
                    ],
                    'tags' => ['rice', 'blast', 'fungus', 'brown spots', 'wet weather', 'leaf lesions'],
                    'source' => 'Philippine Rice Disease Field Guide',
                ],
                [
                    'id' => 2,
                    'title' => 'Early response for corn fall armyworm',
                    'topic' => 'Pest and Disease',
                    'content' => 'Corn fall armyworm pressure often begins with ragged leaves, windowpane feeding, and frass deep in the whorl.',
                    'recommendations' => [
                        'Inspect the whorl and leaf funnel for larvae and frass.',
                        'Target interventions early while damage is still localized.',
                        'Record the percentage of affected plants before deciding the next control step.',
                    ],
                    'tags' => ['corn', 'armyworm', 'pest', 'whorl', 'frass'],
                    'source' => 'Regional Maize Pest Management Brief',
                ],
                [
                    'id' => 3,
                    'title' => 'Tomato leaf curl linked to whiteflies',
                    'topic' => 'Pest and Disease',
                    'content' => 'Tomato leaf curl combined with whitefly activity can indicate viral pressure that should be contained quickly.',
                    'recommendations' => [
                        'Remove severely affected plants if viral symptoms are obvious.',
                        'Reduce whitefly pressure around the crop perimeter.',
                        'Avoid moving tools from affected plants to healthy blocks without cleaning them.',
                    ],
                    'tags' => ['tomato', 'leaf curl', 'whitefly', 'virus'],
                    'source' => 'Protected Vegetable Production Notes',
                ],
                [
                    'id' => 4,
                    'title' => 'Soil acidity correction before planting',
                    'topic' => 'Soil Management',
                    'content' => 'Acidic soil can restrict nutrient availability and root development. Early correction is usually more effective than late in-season fixes.',
                    'recommendations' => [
                        'Test field pH before changing the amendment rate.',
                        'Apply lime based on actual pH and soil texture recommendations.',
                        'Mix amendments evenly into the root zone when possible.',
                    ],
                    'tags' => ['soil', 'ph', 'acidity', 'lime', 'amendment'],
                    'source' => 'Soil Fertility Extension Manual',
                ],
                [
                    'id' => 5,
                    'title' => 'Nitrogen deficiency in leafy vegetables',
                    'topic' => 'Crop Nutrition',
                    'content' => 'Leafy vegetables may show pale older leaves, slower growth, and reduced vigor when nitrogen supply is low or poorly timed.',
                    'recommendations' => [
                        'Compare recent fertilizer rates with the previous schedule before applying more.',
                        'Inspect whether yellowing starts from older leaves, which may suggest nitrogen deficiency.',
                        'Water evenly and avoid overcorrecting with concentrated fertilizer.',
                    ],
                    'tags' => ['pechay', 'vegetable', 'yellowing', 'nitrogen', 'fertilizer'],
                    'source' => 'DA Crop Nutrition Notes',
                ],
                [
                    'id' => 6,
                    'title' => 'Waterlogging response after heavy rain',
                    'topic' => 'Water and Irrigation',
                    'content' => 'Standing water can quickly stress roots, reduce oxygen in the soil, and worsen disease pressure if drainage stays blocked.',
                    'recommendations' => [
                        'Clear drainage channels first before applying new inputs.',
                        'Avoid field traffic while the soil is saturated to limit compaction.',
                        'Reassess plant recovery 24 to 48 hours after water recedes.',
                    ],
                    'tags' => ['waterlogging', 'rain', 'drainage', 'flood', 'irrigation'],
                    'source' => 'Climate-Smart Farm Recovery Guide',
                ],
            ],
        ];
    }

    private function assertRole(array $user, array $roles): void
    {
        if (!in_array($user['role'] ?? '', $roles, true)) {
            throw new InvalidArgumentException('You do not have permission for this action.');
        }
    }

    private function sanitizeString(mixed $value): string
    {
        $value = is_string($value) ? $value : '';
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private function normalizeUrgency(mixed $value): string
    {
        $value = mb_strtolower($this->sanitizeString((string) $value));
        return in_array($value, ['low', 'medium', 'high', 'critical'], true) ? $value : 'medium';
    }

    private function normalizeLines(array|string|null $lines): array
    {
        if ($lines === null) {
            return [];
        }

        if (is_string($lines)) {
            $lines = preg_split('/\r\n|\r|\n|\|{2}/', $lines, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return array_values(array_filter(array_map(
            fn(string $line): string => trim($line),
            $lines
        )));
    }

    private function decodeJsonArray(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function nextId(array $items): int
    {
        $ids = array_map(fn(array $item): int => (int) ($item['id'] ?? 0), $items);
        return $ids === [] ? 1 : (max($ids) + 1);
    }

    private function messageRecord(
        int $id,
        int $consultationId,
        string $senderType,
        string $senderName,
        string $message,
        array $references,
        string $createdAt
    ): array {
        return [
            'id' => $id,
            'consultation_id' => $consultationId,
            'sender_type' => $senderType,
            'sender_name' => $senderName,
            'message' => $message,
            'references' => array_values($references),
            'created_at' => $createdAt,
        ];
    }

    private function notificationRecord(
        int $id,
        string $title,
        string $body,
        string $type,
        bool $isRead,
        ?int $consultationId,
        string $createdAt
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'is_read' => $isRead,
            'consultation_id' => $consultationId,
            'created_at' => $createdAt,
        ];
    }

    private function excerpt(string $text, int $length): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length - 3)) . '...';
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'ai_triage' => 'AI triage',
            'expert_assigned' => 'Expert assigned',
            'monitoring' => 'Monitoring',
            'resolved' => 'Resolved',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
