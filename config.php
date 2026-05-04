<?php
// Konfiguracja bazy danych
define('DB_HOST', '-');
define('DB_NAME', '-');
define('DB_USER', '-'); 
define('DB_PASS', '-'); 

// Ustawienia strefy czasowej
date_default_timezone_set('Europe/Warsaw');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch(PDOException $e) {
        error_log("Błąd połączenia z bazą danych: " . $e->getMessage());
        die("Wystąpił krytyczny błąd systemu. Prosimy spróbować później. Szczegóły błędu zostały zapisane.");
    }
}

// --- Funkcje pomocnicze ---
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Funkcja sanitize
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    $data = $data ?? ''; 
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}


function formatMoney($amount, $currency = 'zł') {
    if (!is_numeric($amount) || $amount === null) { // Sprawdzenie czy jest numeryczny i nie null
        return '0,00 ' . $currency;
    }
    return number_format((float)$amount, 2, ',', ' ') . ' ' . $currency;
}

function formatDate($dateString, $format = 'd.m.Y') {
    if (empty($dateString) || $dateString === '0000-00-00' || $dateString === null) {
        return '-';
    }
    try {
        $date = new DateTime($dateString);
        return $date->format($format);
    } catch (Exception $e) {
        return htmlspecialchars($dateString); // Zwróć oryginalny string, jeśli nie da się sparsować
    }
}

function generatePolicyNumber($existing_numbers_stmt = null) {
    $prefix = "PRO";
    $year = date('y'); // Dwucyfrowy rok
    $month = date('m');
    
    $max_tries = 10;
    $try_count = 0;
    do {
        $sequence = sprintf('%05d', mt_rand(1, 99999)); // 5-cyfrowy losowy numer
        $new_policy_number = "{$prefix}/{$year}/{$month}/{$sequence}";
        if ($existing_numbers_stmt === null) { // Jeśli nie ma jak sprawdzić, zakładamy unikalność
            return $new_policy_number;
        }
        $existing_numbers_stmt->execute([$new_policy_number]);
        $is_taken = $existing_numbers_stmt->fetchColumn();
        $try_count++;
    } while ($is_taken && $try_count < $max_tries);

    if ($is_taken) { // Jeśli po X próbach nadal nie ma unikalnego, dodaj timestamp
        return "{$prefix}/{$year}/{$month}/{$sequence}-" . time();
    }
    return $new_policy_number;
}

?>
