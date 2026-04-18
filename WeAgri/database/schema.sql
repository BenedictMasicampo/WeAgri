CREATE DATABASE IF NOT EXISTS weagri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE weagri;

CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    location VARCHAR(160) NOT NULL,
    primary_crop VARCHAR(80) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS experts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    specialty VARCHAR(120) NOT NULL,
    status ENUM('online', 'busy', 'offline') NOT NULL DEFAULT 'online',
    response_minutes INT NOT NULL DEFAULT 10,
    bio VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
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
);

CREATE TABLE IF NOT EXISTS consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    crop VARCHAR(80) NOT NULL,
    category VARCHAR(80) NOT NULL,
    urgency ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('ai_triage', 'expert_assigned', 'monitoring', 'resolved') NOT NULL DEFAULT 'ai_triage',
    location VARCHAR(160) NOT NULL,
    assigned_expert_id INT NULL,
    summary TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_consultations_farmer FOREIGN KEY (farmer_id) REFERENCES farmers(id),
    CONSTRAINT fk_consultations_expert FOREIGN KEY (assigned_expert_id) REFERENCES experts(id)
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    sender_type ENUM('farmer', 'ai', 'expert') NOT NULL,
    sender_name VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    `references` TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_consultation FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    type ENUM('advisory', 'consultation', 'system', 'weather') NOT NULL DEFAULT 'consultation',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    consultation_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_consultation FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS knowledge_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    topic VARCHAR(120) NOT NULL,
    content TEXT NOT NULL,
    recommendations TEXT NOT NULL,
    tags TEXT NOT NULL,
    source VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO farmers (id, full_name, location, primary_crop)
VALUES
    (1, 'Nestor Reyes', 'Science City of Muñoz, Nueva Ecija', 'Rice')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    location = VALUES(location),
    primary_crop = VALUES(primary_crop);

INSERT INTO experts (id, full_name, specialty, status, response_minutes, bio)
VALUES
    (1, 'Dr. Liza Dizon', 'Pest Management', 'online', 4, 'Helps farmers respond to disease pressure, insects, and crop protection risks.'),
    (2, 'Engr. Mateo Cruz', 'Soil Health', 'online', 7, 'Focuses on soil fertility, pH correction, and field rehabilitation planning.'),
    (3, 'Dr. Ana Velasco', 'Crop Nutrition', 'busy', 11, 'Supports nutrient planning and deficiency diagnosis for vegetables and cereals.'),
    (4, 'Engr. Paulo Ramirez', 'Irrigation & Farm Practices', 'online', 9, 'Guides irrigation scheduling, drainage, and practical field recovery decisions.')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    specialty = VALUES(specialty),
    status = VALUES(status),
    response_minutes = VALUES(response_minutes),
    bio = VALUES(bio);

INSERT INTO users (id, full_name, email, password_hash, role, linked_farmer_id, linked_expert_id, created_at)
VALUES
    (1, 'WeAgri Administrator', 'admin@weagri.local', '$2y$10$ScmM4mbIFRyhCBMkUhc73eItb7POIlbRGLWAS19E68yFEFB61ol6.', 'admin', NULL, NULL, '2026-04-18 08:00:00'),
    (2, 'Nestor Reyes', 'farmer@weagri.local', '$2y$10$4aUGis.rsF8xe5zfsglELeeeVTRCiQOd.VqvO9g/UEn5oKRIdd/46', 'farmer', 1, NULL, '2026-04-18 08:05:00'),
    (3, 'Dr. Liza Dizon', 'liza@weagri.local', '$2y$10$je2XEliX3pAATKtE8dQxzeqS1VdvYISZxVUh34oLLCM2puqnqsu5m', 'consultant', NULL, 1, '2026-04-18 08:10:00'),
    (4, 'Engr. Mateo Cruz', 'mateo@weagri.local', '$2y$10$je2XEliX3pAATKtE8dQxzeqS1VdvYISZxVUh34oLLCM2puqnqsu5m', 'consultant', NULL, 2, '2026-04-18 08:12:00'),
    (5, 'Dr. Ana Velasco', 'ana@weagri.local', '$2y$10$je2XEliX3pAATKtE8dQxzeqS1VdvYISZxVUh34oLLCM2puqnqsu5m', 'consultant', NULL, 3, '2026-04-18 08:14:00'),
    (6, 'Engr. Paulo Ramirez', 'paulo@weagri.local', '$2y$10$je2XEliX3pAATKtE8dQxzeqS1VdvYISZxVUh34oLLCM2puqnqsu5m', 'consultant', NULL, 4, '2026-04-18 08:16:00')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    linked_farmer_id = VALUES(linked_farmer_id),
    linked_expert_id = VALUES(linked_expert_id),
    created_at = VALUES(created_at);

INSERT INTO knowledge_base (id, title, topic, content, recommendations, tags, source)
VALUES
    (1, 'Managing rice blast after wet weather', 'Pest and Disease', 'Rice blast often appears as spindle or diamond-shaped lesions and becomes more aggressive after long wet periods and dense crop canopy conditions.', 'Remove heavily infected leaves if the infection is still localized.\nImprove air flow and avoid prolonged leaf wetness where possible.\nMonitor whether lesions expand after continuous rain.', 'rice, blast, fungus, brown spots, wet weather, leaf lesions', 'Philippine Rice Disease Field Guide'),
    (2, 'Early response for corn fall armyworm', 'Pest and Disease', 'Corn fall armyworm pressure often begins with ragged leaves, windowpane feeding, and frass deep in the whorl.', 'Inspect the whorl and leaf funnel for larvae and frass.\nTarget interventions early while damage is still localized.\nRecord the percentage of affected plants before deciding the next control step.', 'corn, armyworm, pest, whorl, frass', 'Regional Maize Pest Management Brief'),
    (3, 'Tomato leaf curl linked to whiteflies', 'Pest and Disease', 'Tomato leaf curl combined with whitefly activity can indicate viral pressure that should be contained quickly.', 'Remove severely affected plants if viral symptoms are obvious.\nReduce whitefly pressure around the crop perimeter.\nAvoid moving tools from affected plants to healthy blocks without cleaning them.', 'tomato, leaf curl, whitefly, virus', 'Protected Vegetable Production Notes'),
    (4, 'Soil acidity correction before planting', 'Soil Management', 'Acidic soil can restrict nutrient availability and root development. Early correction is usually more effective than late in-season fixes.', 'Test field pH before changing the amendment rate.\nApply lime based on actual pH and soil texture recommendations.\nMix amendments evenly into the root zone when possible.', 'soil, ph, acidity, lime, amendment', 'Soil Fertility Extension Manual'),
    (5, 'Nitrogen deficiency in leafy vegetables', 'Crop Nutrition', 'Leafy vegetables may show pale older leaves, slower growth, and reduced vigor when nitrogen supply is low or poorly timed.', 'Compare recent fertilizer rates with the previous schedule before applying more.\nInspect whether yellowing starts from older leaves, which may suggest nitrogen deficiency.\nWater evenly and avoid overcorrecting with concentrated fertilizer.', 'pechay, vegetable, yellowing, nitrogen, fertilizer', 'DA Crop Nutrition Notes'),
    (6, 'Waterlogging response after heavy rain', 'Water and Irrigation', 'Standing water can quickly stress roots, reduce oxygen in the soil, and worsen disease pressure if drainage stays blocked.', 'Clear drainage channels first before applying new inputs.\nAvoid field traffic while the soil is saturated to limit compaction.\nReassess plant recovery 24 to 48 hours after water recedes.', 'waterlogging, rain, drainage, flood, irrigation', 'Climate-Smart Farm Recovery Guide')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    topic = VALUES(topic),
    content = VALUES(content),
    recommendations = VALUES(recommendations),
    tags = VALUES(tags),
    source = VALUES(source);

INSERT INTO consultations (id, farmer_id, title, crop, category, urgency, status, location, assigned_expert_id, summary, created_at, updated_at)
VALUES
    (101, 1, 'Rice Pest and Disease: brown diamond-shaped leaf spots', 'Rice', 'Pest and Disease', 'high', 'expert_assigned', 'Science City of Muñoz, Nueva Ecija', 1, 'AI triage detected a likely fungal issue and escalated to an expert.', '2026-04-19 08:18:00', '2026-04-19 08:30:00'),
    (102, 1, 'Pechay Crop Nutrition: yellowing leaves after fertilizer change', 'Pechay', 'Crop Nutrition', 'medium', 'monitoring', 'Science City of Muñoz, Nueva Ecija', NULL, 'AgroLLM suggested a first-response nutrition check and monitoring plan.', '2026-04-18 15:05:00', '2026-04-18 15:16:00')
ON DUPLICATE KEY UPDATE
    farmer_id = VALUES(farmer_id),
    title = VALUES(title),
    crop = VALUES(crop),
    category = VALUES(category),
    urgency = VALUES(urgency),
    status = VALUES(status),
    location = VALUES(location),
    assigned_expert_id = VALUES(assigned_expert_id),
    summary = VALUES(summary),
    updated_at = VALUES(updated_at);

INSERT INTO messages (id, consultation_id, sender_type, sender_name, message, `references`, created_at)
VALUES
    (1001, 101, 'farmer', 'Nestor Reyes', 'My rice leaves have brown diamond-shaped spots after three days of rain.', JSON_ARRAY(), '2026-04-19 08:18:00'),
    (1002, 101, 'ai', 'AgroLLM', 'AgroLLM matched your concern to Pest and Disease guidance for Rice.\n\nImmediate steps:\n- Remove heavily infected leaves if the infection is still localized.\n- Improve air flow and avoid prolonged leaf wetness where possible.\n- Monitor whether lesions expand after continuous rain.\n\nThis looks urgent or complex enough that a human agricultural adviser should confirm the diagnosis and next action.\n\nKnowledge used:\n- Managing rice blast after wet weather - Philippine Rice Disease Field Guide', JSON_ARRAY('Managing rice blast after wet weather - Philippine Rice Disease Field Guide'), '2026-04-19 08:20:00'),
    (1003, 101, 'expert', 'Dr. Liza Dizon', 'Dr. Liza Dizon here. I reviewed your Rice concern about Pest and Disease.\n\nPriority actions:\n- Remove heavily infected leaves if the infection is still localized. - Improve air flow and avoid prolonged leaf wetness where possible.\n\nPlease keep monitoring the affected area and share photos or field conditions if the issue spreads further.', JSON_ARRAY(), '2026-04-19 08:30:00'),
    (1004, 102, 'farmer', 'Nestor Reyes', 'My pechay leaves started turning pale after I changed fertilizer this week.', JSON_ARRAY(), '2026-04-18 15:05:00'),
    (1005, 102, 'ai', 'AgroLLM', 'AgroLLM matched your concern to Crop Nutrition guidance for Pechay.\n\nImmediate steps:\n- Compare recent fertilizer rates with the previous schedule before applying more.\n- Inspect whether yellowing starts from older leaves, which may suggest nitrogen deficiency.\n- Water evenly and avoid overcorrecting with concentrated fertilizer.\n\nThis looks suitable for AI first-level guidance, but you should keep monitoring the crop and escalate if symptoms spread.\n\nKnowledge used:\n- Nitrogen deficiency in leafy vegetables - DA Crop Nutrition Notes', JSON_ARRAY('Nitrogen deficiency in leafy vegetables - DA Crop Nutrition Notes'), '2026-04-18 15:16:00')
ON DUPLICATE KEY UPDATE
    consultation_id = VALUES(consultation_id),
    sender_type = VALUES(sender_type),
    sender_name = VALUES(sender_name),
    message = VALUES(message),
    `references` = VALUES(`references`),
    created_at = VALUES(created_at);

INSERT INTO notifications (id, title, body, type, is_read, consultation_id, created_at)
VALUES
    (201, 'Weather advisory', 'Heavy rainfall is expected this afternoon. Check field drainage and standing water areas.', 'weather', 0, NULL, '2026-04-19 07:55:00'),
    (202, 'Consultation assigned', 'Dr. Liza Dizon has been assigned to review your rice disease concern.', 'consultation', 0, 101, '2026-04-19 08:21:00'),
    (203, 'AI update available', 'AgroLLM posted a first-response nutrition plan for your pechay concern.', 'advisory', 1, 102, '2026-04-18 15:16:00')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    body = VALUES(body),
    type = VALUES(type),
    is_read = VALUES(is_read),
    consultation_id = VALUES(consultation_id),
    created_at = VALUES(created_at);
