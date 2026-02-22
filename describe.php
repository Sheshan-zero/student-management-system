<?php
require 'config/db.php';
$schema = [];
$stmt = $pdo->query('DESCRIBE lecturers');
$schema['lecturers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query('DESCRIBE students');
$schema['students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

file_put_contents('schema.json', json_encode($schema, JSON_PRETTY_PRINT));
