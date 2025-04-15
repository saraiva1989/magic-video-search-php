<?php

require_once 'repository/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['canal'])) {
    $canal = $_GET['canal'];
    $db = getDatabaseConnection();

    $stmt = $db->prepare("SELECT Link FROM Decks WHERE YoutubeChannel = :canal ORDER BY DataPublish DESC LIMIT 1");
    $stmt->bindValue(':canal', $canal, SQLITE3_TEXT);
    $result = $stmt->execute();
    $video = $result->fetchArray(SQLITE3_ASSOC);

    if ($video) {
        parse_str(parse_url($video['Link'], PHP_URL_QUERY), $query);
        echo json_encode(['video_id' => $query['v']]);
    } else {
        echo json_encode(['error' => 'Nenhum vídeo encontrado para este canal.']);
    }
    exit;
}