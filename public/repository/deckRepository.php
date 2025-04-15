<?php
require_once 'db.php';

function convertYoutubeToEmbed($url) {
    if (preg_match('/v=([^&]+)/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    return $url;
}

function getDecksBase($termoEn, $canal) {
    $wherequery = " WHERE LOWER(DeckList) LIKE :termo ";
    if ($termoEn != '') {
        $wherequery = "WHERE (LOWER(DeckList) LIKE :termo OR LOWER(DeckList) LIKE :termoEn) ";
    }
    if ($canal !== 'TODOS') {
        $wherequery .= " AND LOWER(YoutubeChannel) = :canal ";
    }
    return $wherequery;
}

function getDecks($termo, $termoEn, $canal, $pagina = 1, $itensPorPagina = 6) {
    $db = getDatabaseConnection();
    $offset = ($pagina - 1) * $itensPorPagina;

    if ($termo === '') {
        $wherequery = "";
        if ($canal !== 'TODOS') {
            $wherequery = " WHERE LOWER(YoutubeChannel) = :canal ";
        }

        $stmt = $db->prepare("SELECT *, :cartaPesquisada as cartaPesquisada FROM Decks $wherequery ORDER BY DataPublish DESC LIMIT :offset, :itensPorPagina");
        $stmt->bindValue(':cartaPesquisada', $termo, SQLITE3_TEXT);
        $stmt->bindValue(':itensPorPagina', $itensPorPagina, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

        if ($canal !== 'TODOS') {
            $stmt->bindValue(':canal', strtolower($canal), SQLITE3_TEXT);
        }

        $result = $stmt->execute();
        $videos = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['DataPublish'] = date('d-m-Y', strtotime($row['DataPublish']));
            $videos[] = $row;
        }
        return $videos;
    }

    $wherequery = getDecksBase($termoEn, $canal);
    $orderBy = " ORDER BY DataPublish DESC LIMIT :offset, :itensPorPagina ";
    $query = " SELECT *, :cartaPesquisada as cartaPesquisada FROM Decks " . $wherequery . " " . $orderBy;

    $stmt = $db->prepare($query);
    $stmt->bindValue(':termo', '%' . $termo . '%', SQLITE3_TEXT);
    $stmt->bindValue(':termoEn', '%' . $termoEn . '%', SQLITE3_TEXT);
    $stmt->bindValue(':cartaPesquisada', $termo, SQLITE3_TEXT);
    $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
    $stmt->bindValue(':itensPorPagina', $itensPorPagina, SQLITE3_INTEGER);

    if ($canal !== 'TODOS') {
        $stmt->bindValue(':canal', strtolower($canal), SQLITE3_TEXT);
    }

    $result = $stmt->execute();
    $videos = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['DataPublish'] = date('d-m-Y', strtotime($row['DataPublish']));
        $videos[] = $row;
    }

    return $videos;
}

function contarDecks($termo, $termoEn, $canal) {
    if ($termo === '') {
        $termo = " ";
    }
    $db = getDatabaseConnection();
    $wherequery = getDecksBase($termoEn, $canal);

    $query = "SELECT COUNT(1) as total FROM Decks " . $wherequery;

    $stmt = $db->prepare($query);
    $stmt->bindValue(':termo', '%' . $termo . '%', SQLITE3_TEXT);
    $stmt->bindValue(':termoEn', '%' . $termoEn . '%', SQLITE3_TEXT);

    if ($canal !== 'TODOS') {
        $stmt->bindValue(':canal', strtolower($canal), SQLITE3_TEXT);
    }

    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    return $row['total'];
}
?>