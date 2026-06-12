<?php
echo password_hash("ad@mndy", PASSWORD_DEFAULT) . "<br>";
echo password_hash("org1@mndy", PASSWORD_DEFAULT) . "<br>";
echo password_hash("org2@mndy", PASSWORD_DEFAULT) . "<br>";
echo password_hash("org3@mndy", PASSWORD_DEFAULT) . "<br>";
echo password_hash("org4@mndy", PASSWORD_DEFAULT) . "<br>";
?>



INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@campus.com', 'PASTE_HASH_HERE', 'admin'),

('Organizer 1', 'org1@campus.com', 'PASTE_HASH_HERE', 'organizer'),
('Organizer 2', 'org2@campus.com', 'PASTE_HASH_HERE', 'organizer'),
('Organizer 3', 'org3@campus.com', 'PASTE_HASH_HERE', 'organizer'),
('Organizer 4', 'org4@campus.com', 'PASTE_HASH_HERE', 'organizer');