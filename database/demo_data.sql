-- Optional local presentation data. Uses an example.com email and demo-only credentials.
USE sun_son_solar;

-- Local demo administrator. Change this password at first sign-in.
INSERT IGNORE INTO users (first_name, middle_name, last_name, birthdate, gender, email, phone, address, username, password_hash, role, status, must_change_password)
VALUES ('Demo', NULL, 'Administrator', '1970-01-01', 'Prefer not to say', 'demo.admin@example.com', NULL, 'Demo account', 'admin', '$2y$10$L4VAVXDpsiIJNjgnz1dsGOF4VYVeUnJwr29UQhv6hfv12y7zQ3Eo6', 'admin', 'active', 1);

INSERT IGNORE INTO users (first_name, middle_name, last_name, birthdate, gender, email, phone, address, username, password_hash, role, status)
VALUES ('Mia', NULL, 'Santos', '1992-04-18', 'Female', 'mia.santos@example.com', '+63 917 555 0142', '14 Narra Street, Quezon City, Metro Manila', 'mia.santos', '$2y$10$Zr0.o7T7FKDF48T.CHvEQeeW7rt6RNsOzho/UYeEwnRAploOteuP6', 'customer', 'active');

INSERT IGNORE INTO users (first_name, middle_name, last_name, birthdate, gender, email, phone, address, username, password_hash, role, status, must_change_password)
VALUES ('Leo', NULL, 'Rivera', '1990-08-12', 'Male', 'leo.rivera@example.com', NULL, 'Staff account', 'solar.tech', '$2y$10$4B/6k.SHdm80c.NoyucgSu3p9jbqe4rwTzCi7KtzOQ0WYPhehQ5Wa', 'employee', 'active', 1);

INSERT INTO quote_requests (user_id, package_id, monthly_bill, roof_type, message, status, admin_note)
SELECT u.id, p.id, 9800, 'Concrete', 'DEMO: We use more electricity in the afternoon and want to understand the right system size.', 'Proposal Sent', 'Your home assessment is complete. The Daylight proposal is ready for review.'
FROM users u JOIN solar_packages p ON p.slug = 'daylight-8kw'
WHERE u.username = 'mia.santos' AND u.email = 'mia.santos@example.com' AND NOT EXISTS (SELECT 1 FROM quote_requests q WHERE q.user_id = u.id AND q.message LIKE 'DEMO:%');

INSERT INTO installations (user_id, package_id, site_address, status, progress, scheduled_date, admin_note)
SELECT u.id, p.id, u.address, 'Design', 28, '2026-10-22', 'DEMO: System layout is being prepared. Our team will confirm the next site milestone.'
FROM users u JOIN solar_packages p ON p.slug = 'daylight-8kw'
WHERE u.username = 'mia.santos' AND u.email = 'mia.santos@example.com' AND NOT EXISTS (SELECT 1 FROM installations i WHERE i.user_id = u.id AND i.admin_note LIKE 'DEMO:%');

INSERT INTO service_requests (user_id, subject, category, details, status, admin_note)
SELECT u.id, 'Demo: Solar monitoring setup', 'Other', 'I would like help understanding the energy monitoring app after installation.', 'In Progress', 'Our support team has shared the monitoring setup guide and is ready to help.'
FROM users u WHERE u.username = 'mia.santos' AND u.email = 'mia.santos@example.com' AND NOT EXISTS (SELECT 1 FROM service_requests s WHERE s.user_id = u.id AND s.subject = 'Demo: Solar monitoring setup');

INSERT INTO activity_log (actor_user_id, customer_user_id, entity_type, entity_id, event_type, description)
SELECT a.id, u.id, 'quote', q.id, 'quote.demo', 'Quote proposal prepared. Sign in to review the next steps.'
FROM users u JOIN quote_requests q ON q.user_id = u.id JOIN users a ON a.username = 'admin'
WHERE u.username = 'mia.santos' AND q.message LIKE 'DEMO:%'
AND NOT EXISTS (SELECT 1 FROM activity_log l WHERE l.event_type = 'quote.demo' AND l.entity_id = q.id);

INSERT INTO activity_log (actor_user_id, customer_user_id, entity_type, entity_id, event_type, description)
SELECT a.id, u.id, 'installation', i.id, 'installation.demo', 'Installation planning is underway at 28% progress.'
FROM users u JOIN installations i ON i.user_id = u.id JOIN users a ON a.username = 'admin'
WHERE u.username = 'mia.santos' AND i.admin_note LIKE 'DEMO:%'
AND NOT EXISTS (SELECT 1 FROM activity_log l WHERE l.event_type = 'installation.demo' AND l.entity_id = i.id);

INSERT INTO activity_log (actor_user_id, customer_user_id, entity_type, entity_id, event_type, description)
SELECT a.id, u.id, 'service', s.id, 'service.demo', 'Support request is in progress. Our team has shared the monitoring setup guide.'
FROM users u JOIN service_requests s ON s.user_id = u.id JOIN users a ON a.username = 'admin'
WHERE u.username = 'mia.santos' AND s.subject = 'Demo: Solar monitoring setup'
AND NOT EXISTS (SELECT 1 FROM activity_log l WHERE l.event_type = 'service.demo' AND l.entity_id = s.id);
