<?php
require_once '/../db/connection.php';

function logAction($user_id, $action) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('INSERT INTO activity_log (user_id, activity) VALUES (?, ?)');
    $stmt->execute([$user_id, $action]);
}
?>