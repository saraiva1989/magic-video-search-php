<?php
// db.php
function getDatabaseConnection() {
    return new SQLite3('../Decks.db');
}
?>