<?php
require_once 'config.php';

if (isLoggedIn()) {
    if (isAdmin()) redirect('admin_panel.php');
    else redirect('client_panel.php');
}

$error = '';
$login_attempt_email = ''; // Zachowaj wartość wpisaną przez użytkownika

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_attempt_email = isset($_POST['login_identifier']) ? trim($_POST['login_identifier']) : '';
    $password_input = isset($_POST['login_password']) ? $_POST['login_password'] : '';
    $user_type_input = isset($_POST['user_type']) ? $_POST['user_type'] : 'client';

    $pdo = getConnection();
    if (empty($login_attempt_email) || empty($password_input)) {
        $error = 'Proszę wypełnić wszystkie pola.';
    } else {
        if ($user_type_input == 'client') {
            $stmt = $pdo->prepare("SELECT IDKlienta, ImieNazwisko, Haslo FROM Klienci WHERE Email = ?");
            $stmt->execute([$login_attempt_email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password_input, $user['Haslo'])) { // Używamy password_verify dla bezpiecznego porównania
                $_SESSION['user_id'] = $user['IDKlienta'];
                $_SESSION['user_name'] = $user['ImieNazwisko'];
                $_SESSION['user_type'] = 'client';
                redirect('client_panel.php');
            } else {
                $error = 'Nieprawidłowy email lub hasło.';
            }
        } elseif ($user_type_input == 'admin') {
            $stmt = $pdo->prepare("SELECT IDPracownika, ImieNazwisko, Haslo, Stanowisko FROM Agenci WHERE Login = ?");
            $stmt->execute([$login_attempt_email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password_input, $user['Haslo'])) { // Używamy password_verify dla bezpiecznego porównania
                $_SESSION['user_id'] = $user['IDPracownika'];
                $_SESSION['user_name'] = $user['ImieNazwisko'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['position'] = $user['Stanowisko'];
                redirect('admin_panel.php');
            } else {
                $error = 'Nieprawidłowy login lub hasło.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl-PL">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie - System Ubezpieczeń Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3a7bd5; /* Główny niebieski gradientu */
            --primary-dark: #2a5a9e;
            --gradient-start: #3a7bd5;
            --gradient-end: #00d2ff;
            --accent-color: #ffc107; /* Żółty jako akcent */
            --text-color: #4A5568; /* Ciemnoszary dla tekstu */
            --text-light: #718096;
            --bg-light: #F7FAFC;
            --white: #ffffff;
            --danger-color: #e53e3e;
            --danger-light: #fed7d7;
            --border-color: #E2E8F0;
            --border-radius-md: 0.5rem; /* 8px */
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background-image: linear-gradient(120deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-color);
            padding: 20px;
        }
        .login-container {
            background-color: var(--white);
            border-radius: var(--border-radius-md);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
            animation: fadeInDrop 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        }
        @keyframes fadeInDrop {
            0% { opacity: 0; transform: translateY(-30px) scale(0.95); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-header {
            padding: 30px;
            text-align: center;
            background-color: var(--bg-light); /* Lekkie tło dla nagłówka */
            border-bottom: 1px solid var(--border-color);
        }
        .login-header .logo-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: inline-block; /* Aby animacja działała */
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .login-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0;
        }
        .login-body { padding: 30px; }
        .user-type-selector {
            display: flex;
            margin-bottom: 25px;
            border-radius: var(--border-radius-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .user-type-selector label {
            flex: 1;
            text-align: center;
            padding: 12px 10px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-light);
            background-color: var(--bg-light);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .user-type-selector input[type="radio"] { display: none; }
        .user-type-selector input[type="radio"]:checked + label {
            background-color: var(--primary-color);
            color: var(--white);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .user-type-selector label:not(:last-child) { border-right: 1px solid var(--border-color); }

        .form-group { position: relative; margin-bottom: 20px; }
        .form-group .input-icon {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 1rem;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px; /* Padding for icon */
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-md);
            font-size: 0.95rem;
            color: var(--text-color);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 123, 213, 0.2);
        }
        .submit-btn {
            width: 100%;
            padding: 14px;
            background-image: linear-gradient(to right, var(--gradient-start) 0%, var(--primary-color) 50%, var(--gradient-end) 100%);
            background-size: 200% auto;
            color: var(--white);
            border: none;
            border-radius: var(--border-radius-md);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-position 0.4s ease, transform 0.1s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }
        .submit-btn:hover { background-position: right center; }
        .submit-btn:active { transform: scale(0.98); }

        .error-message {
            background-color: var(--danger-light);
            color: var(--danger-color);
            padding: 12px 15px;
            border-radius: var(--border-radius-md);
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
            border: 1px solid var(--danger-color);
            display: flex;
            align-items: center;
        }
        .error-message i { margin-right: 10px; font-size: 1.1rem; }
        .test-data-info {
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-light);
            margin-top: 30px;
        }
        .test-data-info p { margin-bottom: 3px; }
        .test-data-info strong { color: var(--text-color); }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-shield-alt logo-icon"></i>
            <h1>System Ubezpieczeń</h1>
        </div>
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="error-message"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php" id="loginForm">
                <div class="user-type-selector">
                    <input type="radio" name="user_type" id="type_client" value="client" <?php echo (!isset($_POST['user_type']) || (isset($_POST['user_type']) && $_POST['user_type'] == 'client')) ? 'checked' : ''; ?> onchange="updateInputType()">
                    <label for="type_client"><i class="fas fa-user"></i> Klientę</label>
                    <input type="radio" name="user_type" id="type_admin" value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'checked' : ''; ?> onchange="updateInputType()">
                    <label for="type_admin"><i class="fas fa-user-shield"></i> Agent/Admin</label>
                </div>

                <div class="form-group">
                    <i class="fas fa-envelope input-icon" id="identifierIcon"></i>
                    <input type="text" name="login_identifier" id="login_identifier" value="<?php echo htmlspecialchars($login_attempt_email); ?>" required>
                </div>

                <div class="form-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="login_password" id="login_password" placeholder="Hasło" required>
                </div>

                <button type="submit" class="submit-btn">Zaloguj się <i class="fas fa-arrow-right" style="margin-left: 5px;"></i></button>
            </form>
            <div class="test-data-info">
                <p><strong>Dane testowe:</strong></p>
                <p>Klient: piotr@example.com / piotrpass</p>
                <p>Admin: admin / adminpass</p>
            </div>
        </div>
    </div>

    <script>
        function updateInputType() {
            const isAdmin = document.getElementById('type_admin').checked;
            const identifierInput = document.getElementById('login_identifier');
            const identifierIcon = document.getElementById('identifierIcon');
            if (isAdmin) {
                identifierInput.placeholder = 'Login';
                identifierInput.type = 'text';
                identifierIcon.className = 'fas fa-user-tie input-icon';
            } else {
                identifierInput.placeholder = 'Email';
                identifierInput.type = 'email';
                identifierIcon.className = 'fas fa-envelope input-icon';
            }
        }
        // Inicjalizacja przy ładowaniu strony
        document.addEventListener('DOMContentLoaded', updateInputType);
        // Upewnij się, że przy błędnym logowaniu zachowany jest wybór i placeholder
        <?php if (isset($_POST['user_type'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const selectedType = '<?php echo htmlspecialchars($_POST['user_type']); ?>';
            if (selectedType === 'admin') {
                document.getElementById('type_admin').checked = true;
            } else {
                document.getElementById('type_client').checked = true;
            }
            updateInputType(); // Ponownie ustaw placeholder po ustawieniu radio
        });
        <?php endif; ?>
    </script>
</body>
</html>
