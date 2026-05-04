<?php
require_once 'config.php'; // Zawiera session_start()

// Usuń wszystkie zmienne sesji.
$_SESSION = array();

// Jeśli jest pożądane zniszczenie sesji, usuń także cookie sesyjne.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

redirect('index.php');
?>
