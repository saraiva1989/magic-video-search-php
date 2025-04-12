<?php

function convertYoutubeToEmbed($url) {
  // Extrai o ID do vídeo
  if (preg_match('/v=([^&]+)/', $url, $matches)) {
      return "https://www.youtube.com/embed/" . $matches[1];
  }
  return $url;
}

function getDecks($termo, $termoEn, $pagina = 1, $itensPorPagina = 6) {
  $db = new SQLite3('../Decks.db');
  if ($termo === '') {
        return [];
  }
  
  $offset = ($pagina - 1) * $itensPorPagina;
  $wherequery = " WHERE LOWER(DeckList) LIKE :termo ";
  if($termoEn != '') {
    $wherequery = $wherequery . " or LOWER(DeckList) LIKE :termoEn ";
  }
  $orderBy = " ORDER BY DataPublish DESC LIMIT :offset, :itensPorPagina ";

  $query = " SELECT *, :cartaPesquisada as cartaPesquisada FROM Decks " . $wherequery . " " . $orderBy;

  $stmt = $db->prepare($query);
  $stmt->bindValue(':termo', '%' . $termo . '%', SQLITE3_TEXT);
  $stmt->bindValue(':termoEn', '%' . $termoEn . '%', SQLITE3_TEXT);
  $stmt->bindValue(':cartaPesquisada', $termo, SQLITE3_TEXT);
  $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
  $stmt->bindValue(':itensPorPagina', $itensPorPagina, SQLITE3_INTEGER);

  $result = $stmt->execute();
  
  $videos = [];
  while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $videos[] = $row;
  }
  
  return $videos;
}

function contarDecks($termo, $termoEn) {
  if ($termo === '') {
    0;
    return;
  }
  $db = new SQLite3('../Decks.db');
  $wherequery = " WHERE LOWER(DeckList) LIKE :termo ";
  if($termoEn != '') {
    $wherequery = $wherequery . " or LOWER(DeckList) LIKE :termoEn ";
  }

  $query = "SELECT COUNT(1) as total FROM Decks " . $wherequery;

  $stmt = $db->prepare($query);
  $stmt->bindValue(':termo', '%' . $termo . '%', SQLITE3_TEXT);
  $stmt->bindValue(':termoEn', '%' . $termoEn . '%', SQLITE3_TEXT);
  $result = $stmt->execute();
  $row = $result->fetchArray(SQLITE3_ASSOC);
  return $row['total'];
}

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$termo = isset($_GET['termo']) ? strtolower($_GET['termo']) : '';
$termoEn = isset($_GET['termoEn']) ? strtolower($_GET['termoEn']) : '';


$videos = getDecks($termo, $termoEn, $pagina);
$totalDecks = contarDecks($termo, $termoEn);
$totalPaginas = ceil($totalDecks / 6);


?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Magic Arena - Vídeos de Decks</title>
  <link rel="icon" href="https://rosybrown-eagle-401041.hostingersite.com/logo.png" type="image/x-icon">
  <link rel="stylesheet" href="style.css?2">
  <meta property="og:title" content="Magic Arena - Vídeos de Decks">
  <meta property="og:description" content="Consulte gameplay de decks pesquisando pela carta no Magic Arena.">
  <meta property="og:image" content="https://rosybrown-eagle-401041.hostingersite.com/logo.png">
  <meta property="og:url" content="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
</head>

<body>
  <header>
    <h1>Magic Arena - Vídeos de Decks</h1>
  </header>
  <div class="search-container">
    <input type="text" autocomplete="off" id="searchInput" placeholder="Digite o nome da carta..." value="<?= htmlspecialchars($termo) ?>">
    <input type="hidden" autocomplete="off" id="searchInputEn" value="<?= htmlspecialchars($termoen) ?>">
    <button onclick="buscarCarta()">Pesquisar</button>
    <div id="autocompleteResults" class="autocomplete-results"></div>

  </div>


  <div class="loading" id="loading">
    <div class="spinner"></div>
  </div>

  <div class="results" id="results">
    <?php if (empty($termo)): ?>
    <p>informe uma carta para pesquisa!</p>
    <?php elseif (empty($videos)): ?>
    <p>Nenhum vídeo encontrado para essa carta.</p>
    <?php else: ?>
    <?php foreach ($videos as $video): ?>
    <?php
          $listaCartas = explode(";", $video['DeckList']);
          $cartasHtml = "Deck\n\n";
        ?>
    <div class="video-card">
      <iframe src="<?= $embedUrl = convertYoutubeToEmbed(htmlspecialchars($video['Link'])); ?>"
        allowfullscreen></iframe>
      <h3>
        <?= htmlspecialchars($video['cartaPesquisada']) ?>
      </h3>
      <p><strong>Canal:</strong>
        <?= htmlspecialchars(strtoupper($video['YoutubeChannel'])) ?>
      </p>
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
    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
    <a href="?termo=<?= urlencode($termo) ?>&pagina=<?= $i ?>" class="<?= $i === $pagina ? 'active' : '' ?>">
      <?= $i ?>
    </a>
    <?php endfor; ?>
  </div>

  <script>

    let timeoutSearch = null;

    function buscarCarta() {
      const termo = document.getElementById("searchInput").value;
      const termoEn = document.getElementById("searchInputEn").value;
      const url = new URL(window.location.href);
      url.searchParams.set('termo', termo);
      url.searchParams.set('termoEn', termoEn);
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

  </script>

</body>

</html>