<?php
$db = new SQLite3('../Decks.db');

$verificationCode = "190196";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $youtubeChannel = $_POST['youtubeChannel'];
    $link = $_POST['link'];
    $dataPublish = $_POST['dataPublish'];
    $gameFormat = $_POST['gameFormat'];
    $deckList = $_POST['deckList'];
    $inputCode = $_POST['verificationCode'];

    if ($inputCode !== $verificationCode) {
        $message = "Código de verificação incorreto!";
    } else {
        $deckList = str_replace("deck", "", $deckList);
        $deckList = str_replace("Deck", "", $deckList);
        $deckList = str_replace(["\r\n", "\r"], "\n", $deckList);

        $deckList = explode("\n", $deckList);
        $deckList = array_map('trim', $deckList);
        $deckListformat = implode(";", $deckList);

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM Decks WHERE Link = :link");
        $stmt->bindValue(':link', $link, SQLITE3_TEXT);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row['count'] > 0) {
            $message = "O link do YouTube já existe!";
        } else {
            $stmt = $db->prepare("INSERT INTO Decks (YoutubeChannel, Link, DataPublish, GameFormat, DeckList) VALUES (:youtubeChannel, :link, :dataPublish, :gameFormat, :deckList)");
            $stmt->bindValue(':youtubeChannel', $youtubeChannel, SQLITE3_TEXT);
            $stmt->bindValue(':link', $link, SQLITE3_TEXT);
            $stmt->bindValue(':dataPublish', $dataPublish, SQLITE3_TEXT);
            $stmt->bindValue(':gameFormat', $gameFormat, SQLITE3_TEXT);
            $stmt->bindValue(':deckList', $deckListformat, SQLITE3_TEXT);
            $stmt->execute();
            $message = "Vídeo cadastrado com sucesso!";
        }
    }
}

if (isset($_GET['searchLink'])) {
    $searchLink = $_GET['searchLink'];
    $stmt = $db->prepare("SELECT * FROM Decks WHERE Link = :link");
    $stmt->bindValue(':link', $searchLink, SQLITE3_TEXT);
    $result = $stmt->execute();
    $videoData = $result->fetchArray(SQLITE3_ASSOC);
}

if (isset($_POST['deleteVideo'])) {
    $inputCode = $_POST['verificationCode'];
    if ($inputCode === $verificationCode) {
        $stmt = $db->prepare("DELETE FROM Decks WHERE Link = :link");
        $stmt->bindValue(':link', $searchLink, SQLITE3_TEXT);
        $stmt->execute();
        $message = "Vídeo excluído com sucesso!";
        $videoData = null;
    } else {
        $message = "Código de verificação incorreto para exclusão!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar Vídeos</title>
    <link rel="stylesheet" href="style.css?3">
</head>
<body>
    <header>
        <h1>Cadastrar Vídeos</h1>
    </header>
    <div class="form-container" style="display: flex; justify-content: space-between;">
        <div class="card" style="flex: 1; margin-right: 10px; padding: 20px; background: #292942; border-radius: 10px;">
            <h2>Cadastrar Vídeos</h2>
            <form method="POST">
                <label for="youtubeChannel">Canal:</label>
                <select name="youtubeChannel" id="youtubeChannel" required>
                    <option value="UMOTIVO">UMOTIVO</option>
                    <option value="MTGABRASIL">MTGABRASIL</option>
                    <option value="POBREPLANINAUTA">POBREPLANINAUTA</option>
                    <option value="TODOS">TODOS CANAIS</option>
                </select>

                <label for="link">URL do YouTube:</label>
                <input type="text" name="link" id="link" required>

                <label for="dataPublish">Data de Publicação:</label>
                <input type="date" name="dataPublish" id="dataPublish" value="<?= date('Y-m-d') ?>" required>

                <label for="gameFormat">Formato do Jogo:</label>
                <input type="text" name="gameFormat" id="gameFormat">

                <label for="deckList">Lista de Cartas:</label>
                <textarea name="deckList" id="deckList" rows="5" required></textarea>

                <label for="verificationCode">Código de Verificação:</label>
                <input type="text" name="verificationCode" id="verificationCode" required>

                <button type="submit">Cadastrar Vídeo</button>
            </form>

            <?php if (isset($message)) : ?>
                <p><?= $message ?></p>
            <?php endif; ?>
        </div>

        <div class="card" style="flex: 1; margin-left: 10px; padding: 20px; background: #292942; border-radius: 10px;">
            <h2>Pesquisar Vídeo</h2>
            <form method="GET">
                <input type="text" name="searchLink" placeholder="Digite o link do vídeo" required>
                <button type="submit">Pesquisar</button>
            </form>

            <?php if (isset($videoData)) : ?>
                <h3>Dados do Vídeo Encontrado:</h3>
                <p><strong>Canal:</strong> <?= htmlspecialchars($videoData['YoutubeChannel']) ?></p>
                <p><strong>Link:</strong> <?= htmlspecialchars($videoData['Link']) ?></p>
                <p><strong>Data de Publicação:</strong> <?= htmlspecialchars($videoData['DataPublish']) ?></p>
                <p><strong>Formato do Jogo:</strong> <?= htmlspecialchars($videoData['GameFormat']) ?></p>
                <p><strong>Lista de Cartas:</strong> <?= htmlspecialchars($videoData['DeckList']) ?></p>

                <form method="POST">
                    <label for="verificationCode">Código de Verificação para Exclusão:</label>
                    <input type="text" name="verificationCode" id="verificationCode" required>
                    <input type="hidden" name="searchLink" value="<?= htmlspecialchars($videoData['Link']) ?>">
                    <button type="submit" name="deleteVideo">Excluir Vídeo</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
