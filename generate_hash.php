<?php
$passwordToHash = 'piotrpass';
$hashedPassword = password_hash($passwordToHash, PASSWORD_DEFAULT);
echo "Hasło: " . htmlspecialchars($passwordToHash) . "<br>";
echo "Wygenerowany hash: " . htmlspecialchars($hashedPassword) . "<br>";

// Test weryfikacji
if (password_verify($passwordToHash, $hashedPassword)) {
    echo "Weryfikacja z nowym hashem: OK<br>";
} else {
    echo "Weryfikacja z nowym hashem: BŁĄD<br>";
}
?>
