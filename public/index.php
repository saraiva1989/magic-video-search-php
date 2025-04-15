<?php
require_once 'repository/deckRepository.php';

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$termo = isset($_GET['termo']) ? strtolower($_GET['termo']) : '';
$termoEn = isset($_GET['termoEn']) ? strtolower($_GET['termoEn']) : '';
$canal = isset($_GET['canal']) ? $_GET['canal'] : 'TODOS';

$videos = getDecks($termo, $termoEn, $canal, $pagina);
$totalDecks = contarDecks($termo, $termoEn, $canal);
$totalPaginas = ceil($totalDecks / 6);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Magic Arena: Decks Gameplay</title>
    <link rel="icon" href="https://rosybrown-eagle-401041.hostingersite.com/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="style.css?5">
    <meta name="description" content="Descubra gameplay de decks de Magic Arena. Pesquise cartas e veja como elas se comportam em partidas reais.">
    <meta name="keywords" content="Magic Arena, gameplay, decks, cartas, vídeos, cards, deck, mtga, mtgarena">
    <meta property="og:title" content="Magic Arena - Gameplay de decks, pesquise pela carta">
    <meta property="og:description" content="Descubra gameplay de decks de Magic Arena. Pesquise cartas e veja como elas se comportam em partidas reais.">
    <meta property="og:image" content="https://rosybrown-eagle-401041.hostingersite.com/logo.png">
    <meta property="og:url" content="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
</head>
<body>
<a class="link-header" href="/">
    <header class="magic-header">
        <h1>Magic Arena: Decks Gameplay</h1>
    </header>
    </a>
    <div class="search-container">
        <input type="text" autocomplete="off" id="searchInput" placeholder="Digite o nome da carta..." value="<?= htmlspecialchars($termo) ?>">
        <div id="autocompleteResults" class="autocomplete-results"></div>
        <select id="canalSelect" class="canal-select">
            <option value="TODOS" <?= $canal === 'TODOS' ? 'selected' : '' ?>>TODOS CANAIS</option>
            <option value="UMOTIVO" <?= $canal === 'UMOTIVO' ? 'selected' : '' ?>>UMOTIVO</option>
            <option value="MTGABRASIL" <?= $canal === 'MTGABRASIL' ? 'selected' : '' ?>>MTGABRASIL</option>
            <option value="POBREPLANINAUTA" <?= $canal === 'POBREPLANINAUTA' ? 'selected' : '' ?>>POBREPLANINAUTA</option>
            <option value="BLACKMANAMTG" <?= $canal === 'BLACKMANAMTG' ? 'selected' : '' ?>>BLACKMANAMTG</option>
        </select>
        <input type="hidden" autocomplete="off" id="searchInputEn" value="<?= htmlspecialchars($termoEn) ?>">
        <button onclick="buscarCarta()">Pesquisar</button>
    </div>

    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>

    <div class="results" id="results">
        <?php if (empty($videos)): ?>
            <p>Nenhum vídeo encontrado para essa carta.</p>
        <?php else: ?>
            <?php foreach ($videos as $video): ?>
                <?php
                $listaCartas = explode(";", $video['DeckList']);
                $cartasHtml = "Deck\n\n";
                ?>
                <div class="video-card">
                    <iframe src="<?= $embedUrl = convertYoutubeToEmbed(htmlspecialchars($video['Link'])); ?>" allowfullscreen></iframe>
                    <h3><?= htmlspecialchars($video['cartaPesquisada']) ?></h3>
                    <p><strong>Canal:</strong> <?= htmlspecialchars(strtoupper($video['YoutubeChannel'])) ?></p>
                    <p><strong>Data de Publicação:</strong> <?= htmlspecialchars($video['DataPublish']) ?></p>
                    <div class="decklist">
                        <?php  
                        foreach ($listaCartas as $carta) {
                            $cartasHtml .= $carta . "\n";
                        }
                        echo $cartasHtml;
                        ?>
                        <button class="copiar-button" onclick="copiarDeck(`<?= htmlspecialchars($cartasHtml) ?>`)">Copiar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="pagination">
        <?php if ($pagina > 1): ?>
            <a href="?termo=<?= urlencode($termo) ?>&termoEn=<?= urlencode($termoEn) ?>&canal=<?= urlencode($canal) ?>&pagina=<?= $pagina - 1 ?>" class="prev">Anterior</a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="?termo=<?= urlencode($termo) ?>&termoEn=<?= urlencode($termoEn) ?>&canal=<?= urlencode($canal) ?>&pagina=<?= $pagina + 1 ?>" class="next">Próximo</a>
        <?php endif; ?>
    </div>

    <script>
        let timeoutSearch = null;

        function buscarCarta() {
            const termo = document.getElementById("searchInput").value;
            const termoEn = document.getElementById("searchInputEn").value;
            const canal = document.getElementById("canalSelect").value;
            const url = new URL(window.location.href);
            url.searchParams.set('termo', termo);
            url.searchParams.set('termoEn', termoEn);
            url.searchParams.set('canal', canal);
            url.searchParams.set('pagina', 1); 
            document.getElementById("loading").style.display = "flex"; 
            window.location.href = url;
        }

        function copiarDeck(deck) {
            navigator.clipboard.writeText(deck).then(() => {
                alert('Deck copiado para a área de transferência!');
            }, (err) => {
                console.error('Erro ao copiar: ', err);
            });
        }

        document.getElementById("searchInput").addEventListener("keypress", function (event) {
            if (event.key === "Enter") {
                buscarCarta();
            }
        });

        document.getElementById('searchInput').addEventListener('input', function () {
            clearTimeout(timeoutSearch);
            timeoutSearch = setTimeout(() => {
                autoCompleteSerach(this.value)
            }, 250);
        });

        async function autoCompleteSerach(query) {
            if (query.length < 1) {
                document.getElementById('autocompleteResults').style.display = "none";
                document.getElementById('autocompleteResults').innerHTML = '';
                return;
            }

            const request = await fetch(`https://ac.ligamagic.com.br/api/cardsearch?tcg=1&maxQuantity=8&maintype=1&query=${encodeURIComponent(query)}`)
            const data = await request.json();
            const resultsContainer = document.getElementById('autocompleteResults');
            resultsContainer.innerHTML = '';

            data.data.forEach(card => {
                document.getElementById('autocompleteResults').style.display = "block";
                let nome = card.sNomeIdiomaPrincipal == "" ? decodeHtml(card.sNomeIdiomaSecundario) : decodeHtml(card.sNomeIdiomaPrincipal);
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.textContent = nome; 
                item.addEventListener('click', function () {
                    document.getElementById('autocompleteResults').style.display = "none";
                    document.getElementById('searchInput').value = nome;
                    document.getElementById('searchInputEn').value = decodeHtml(card.sNomeIdiomaSecundario); 
                    resultsContainer.innerHTML = '';
                    buscarCarta();
                });
                resultsContainer.appendChild(item);
            });
        }

        function decodeHtml(html) {
            const txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }

        document.getElementById("canalSelect").addEventListener("change", function () {
            buscarCarta();
        });
    </script>
</body>
</html>