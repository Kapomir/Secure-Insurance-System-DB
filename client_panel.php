<?php
require_once 'config.php';

// Sprawdź czy użytkownik jest zalogowany jako klient
if (!isLoggedIn() || isAdmin()) {
    redirect('index.php');
}

$pdo = getConnection();
$clientId = $_SESSION['user_id'];

// Pobierz dane klienta
$stmt_client = $pdo->prepare("SELECT * FROM Klienci WHERE IDKlienta = ?");
$stmt_client->execute([$clientId]);
$client = $stmt_client->fetch();

if (!$client) { // Jeśli z jakiegoś powodu nie ma danych klienta, wyloguj
    session_destroy();
    redirect('index.php');
}

// Pobierz polisy klienta
$stmt_policies = $pdo->prepare("
    SELECT p.*, poj.Marka, poj.Model, poj.NrRejestracyjny 
    FROM Polisy p
    LEFT JOIN Pojazdy poj ON p.IDPojazdu = poj.IDPojazdu
    WHERE p.IDKlienta = ?
    ORDER BY p.DataZakonczenia DESC, p.DataRozpoczecia DESC
");
$stmt_policies->execute([$clientId]);
$policies = $stmt_policies->fetchAll();

// Pobierz szkody klienta
$stmt_damages = $pdo->prepare("
    SELECT s.*, p.NrPolisy, p.TypUbezpieczenia AS TypPolisyUbezp, 
           poj.Marka, poj.Model, poj.NrRejestracyjny 
    FROM Szkody s
    LEFT JOIN Polisy p ON s.IDPolisy = p.IDPolisy
    LEFT JOIN Pojazdy poj ON s.IDPojazdu = poj.IDPojazdu
    WHERE s.IDKlienta = ?
    ORDER BY s.DataZdarzenia DESC
");
$stmt_damages->execute([$clientId]);
$damages = $stmt_damages->fetchAll();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Klienta - Ubezpieczenia Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3a7bd5; 
            --primary-dark: #2a5a9e;
            --gradient-start: #3a7bd5;
            --gradient-end: #00d2ff;
            --accent-color: #2ecc71; /* Bardziej żywy zielony */
            --text-color: #4A5568; 
            --text-light: #718096;
            --bg-color: #F9FAFB; /* Jaśniejsze tło */
            --card-bg: #ffffff;
            --border-color: #E2E8F0;
            --border-radius-lg: 0.75rem; /* 12px */
            --border-radius-md: 0.5rem;  /* 8px */
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.65;
            font-size: 16px;
        }
        .header {
            background-color: var(--card-bg);
            padding: 1rem 0;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-content {
            max-width: 1500px; margin: 0 auto; padding: 0 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header-logo {
            font-size: 1.7rem; font-weight: 700; color: var(--primary-color); text-decoration: none;
            display: flex; align-items: center;
        }
        .header-logo i { margin-right: 10px; font-size: 2rem; }
        .logout-btn {
            background-color: var(--primary-color); color: var(--card-bg); padding: 0.65rem 1.3rem;
            text-decoration: none; border-radius: var(--border-radius-md); font-weight: 500;
            transition: background-color 0.3s ease, transform 0.1s ease;
        }
        .logout-btn:hover { background-color: var(--primary-dark); transform: translateY(-1px); }
        .logout-btn i { margin-right: 0.6rem; }

        .container { max-width: 1500px; margin: 2.5rem auto; padding: 0 2rem; }
        
        .welcome-banner {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: var(--card-bg);
            padding: 2.5rem 2rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }
        .welcome-banner h1 { font-size: 2.2rem; font-weight: 600; margin-bottom: 0.5rem; }
        .welcome-banner p { font-size: 1.1rem; opacity: 0.9; }

        .card {
            background-color: var(--card-bg); border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md); padding: 2.2rem; margin-bottom: 2.5rem;
            animation: slideUpIn 0.6s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
            opacity:0;
        }
        @keyframes slideUpIn {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .card-header {
            display: flex; align-items: center;
            font-size: 1.6rem; font-weight: 600; color: var(--primary-dark);
            margin-bottom: 1.8rem; padding-bottom: 1rem; 
            border-bottom: 1px solid var(--border-color);
        }
        .card-header i { margin-right: 1rem; color: var(--primary-color); font-size: 2rem; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.8rem; }
        .info-item {
            background-color: var(--bg-color); padding: 1.2rem; border-radius: var(--border-radius-md);
            border-left: 5px solid var(--primary-color);
            transition: box-shadow 0.3s ease;
        }
        .info-item:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .info-label { font-weight: 500; color: var(--text-light); display: block; margin-bottom: 0.3rem; font-size:0.85rem; text-transform: uppercase; }
        .info-value { font-size: 1.05rem; font-weight: 500; color: var(--text-color); }

        .table-wrapper { overflow-x: auto; margin-top: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.2rem; text-align: left; border-bottom: 1px solid var(--border-color); font-size:0.95rem; }
        th { background-color: var(--bg-color); font-weight: 600; color: var(--primary-dark); text-transform: uppercase; font-size:0.8rem;}
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #fdfdff; } /* Subtle hover */
        
        .status-badge {
            padding: 0.35rem 0.8rem; border-radius: 50px; /* Pills */
            font-size: 0.8rem; font-weight: 600; display: inline-block; 
            text-transform: capitalize; letter-spacing: 0.5px;
        }
        /* Consistent status colors */
        .status-aktywna { background-color: rgba(46, 204, 113, 0.15); color: #27ae60; }
        .status-wygasla, .status-wygasła, .status-zakonczona, .status-anulowana { background-color: rgba(149, 165, 166, 0.15); color: #7f8c8d; }
        .status-zgłoszona, .status-zgloszona { background-color: rgba(243, 156, 18, 0.15); color: #d35400; }
        .status-w-trakcie-likwidacji { background-color: rgba(52, 152, 219, 0.15); color: #2980b9; }
        .status-zlikwidowana { background-color: rgba(46, 204, 113, 0.15); color: #27ae60; } /* Same as active */
        .status-odmowa { background-color: rgba(231, 76, 60, 0.15); color: #c0392b; }
        
        .no-data { text-align: center; padding: 3rem 1rem; color: var(--text-light); font-style: italic; background-color:var(--card-bg); border-radius:var(--border-radius-lg); box-shadow:var(--shadow-md); }
        .no-data i { display: block; font-size: 3.5rem; margin-bottom: 1rem; color: var(--border-color); }

        .footer { text-align: center; padding: 2.5rem 1rem; margin-top: 2rem; color: var(--text-light); font-size: 0.9rem; border-top: 1px solid var(--border-color); }
        .footer a { color: var(--primary-color); text-decoration: none; }
        .footer a:hover { text-decoration: underline; }

        @media (max-width: 992px) {
            .header-content, .container { padding: 0 1.5rem; }
            .card h2, .welcome-banner h1 { font-size: 1.8rem; }
            .info-grid { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        }
        @media (max-width: 768px) {
            body { font-size: 15px; }
            .header-logo { font-size: 1.5rem; }
            .header-logo i { font-size: 1.7rem; }
            .logout-btn { padding: 0.5rem 1rem; font-size:0.9rem; }
            .container { margin-top: 1.5rem; }
            .card { padding: 1.5rem; }
            .card h2 { font-size: 1.5rem; margin-bottom: 1.5rem; }
            .card h2 i { font-size: 1.7rem; }
            .welcome-banner { padding: 2rem 1.5rem; }
            .welcome-banner h1 { font-size: 1.9rem; }
            .welcome-banner p { font-size: 1rem; }
            th, td { padding: 0.8rem; font-size:0.9rem; }
            .info-grid { gap: 1rem; }
            .info-item { padding: 1rem; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="client_panel.php" class="header-logo"><i class="fas fa-user-shield"></i>Panel Klienta</a>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Wyloguj się</a>
        </div>
    </header>

    <div class="container">
        <section class="welcome-banner">
            <h1>Witaj, <?php echo htmlspecialchars($client['ImieNazwisko']); ?>!</h1>
            <p>Przeglądaj swoje polisy i historię szkód. Jesteśmy do Twojej dyspozycji.</p>
        </section>

        <section class="card client-details">
            <div class="card-header"><i class="fas fa-id-card"></i>Twoje Dane</div>
            <div class="info-grid">
                <div class="info-item"><span class="info-label">Email</span> <span class="info-value"><?php echo htmlspecialchars($client['Email']); ?></span></div>
                <div class="info-item"><span class="info-label">Telefon</span> <span class="info-value"><?php echo htmlspecialchars($client['Telefon'] ?? '-'); ?></span></div>
                <div class="info-item"><span class="info-label">PESEL</span> <span class="info-value"><?php echo htmlspecialchars($client['PESEL'] ?? '-'); ?></span></div>
                <div class="info-item"><span class="info-label">Data urodzenia</span> <span class="info-value"><?php echo formatDate($client['DataUrodzenia']); ?></span></div>
                <div class="info-item">
                    <span class="info-label">Adres</span>
                    <span class="info-value">
                        <?php 
                        $adres_parts = [];
                        if (!empty($client['Ulica'])) $adres_parts[] = $client['Ulica'] . (!empty($client['NumerDomu']) ? ' ' . $client['NumerDomu'] : '');
                        if (!empty($client['KodPocztowy'])) $adres_parts[] = $client['KodPocztowy'];
                        if (!empty($client['Miasto'])) $adres_parts[] = $client['Miasto'];
                        echo htmlspecialchars(implode(', ', array_filter($adres_parts))) ?: 'Nie podano';
                        ?>
                    </span>
                </div>
                <div class="info-item"><span class="info-label">Data prawa jazdy</span> <span class="info-value"><?php echo formatDate($client['DataPrawkoJazdy']); ?></span></div>
                <div class="info-item"><span class="info-label">Lata bezszkodowej jazdy</span> <span class="info-value"><?php echo $client['LataBezSzkody']; ?> lat</span></div>
                <div class="info-item"><span class="info-label">Data pierwszej polisy</span> <span class="info-value"><?php echo formatDate($client['DataPierwszejPolisy']); ?></span></div>
            </div>
        </section>

        <section class="card policies-section">
            <div class="card-header"><i class="fas fa-file-invoice-dollar"></i>Twoje Polisy</div>
            <?php if (count($policies) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Nr Polisy</th><th>Typ</th><th>Pojazd</th><th>Okres Ochrony</th><th>Suma Ubezp.</th><th>Składka</th><th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($policies as $policy): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($policy['NrPolisy']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($policy['TypUbezpieczenia']); ?></td>
                                    <td><?php echo htmlspecialchars(($policy['Marka'] ?? 'N/A') . ' ' . ($policy['Model'] ?? '') . ' (' . ($policy['NrRejestracyjny'] ?? 'Brak') . ')'); ?></td>
                                    <td><?php echo formatDate($policy['DataRozpoczecia']); ?> - <?php echo formatDate($policy['DataZakonczenia']); ?></td>
                                    <td><?php echo formatMoney($policy['SumaUbezpieczenia']); ?></td>
                                    <td><?php echo formatMoney($policy['SkladkaKoncowa']); ?></td>
                                    <td>
                                        <?php 
                                        $status_p = $policy['Status'];
                                        if (strtolower($status_p) == 'aktywna' && strtotime($policy['DataZakonczenia']) < time()) $status_p = 'wygasła';
                                        $statusClass_p = 'status-' . str_replace([' ', 'ł'], ['-', 'l'], mb_strtolower($status_p));
                                        ?>
                                        <span class="status-badge <?php echo htmlspecialchars($statusClass_p); ?>"><?php echo htmlspecialchars($status_p); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data"><i class="far fa-folder-open"></i>Nie znaleziono żadnych polis.</p>
            <?php endif; ?>
        </section>

        <section class="card damages-section">
            <div class="card-header"><i class="fas fa-history"></i>Historia Szkód</div>
            <?php if (count($damages) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Data Zdarzenia</th><th>Pojazd</th><th>Polisa (Typ)</th><th>Opis</th><th>Status</th><th>Szac. Wartość</th><th>Odszkodowanie</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($damages as $damage): ?>
                                <tr>
                                    <td><?php echo formatDate($damage['DataZdarzenia']); ?></td>
                                    <td><?php echo htmlspecialchars(($damage['Marka'] ?? 'N/A') . ' ' . ($damage['Model'] ?? '') . ' (' . ($damage['NrRejestracyjny'] ?? 'Brak') . ')'); ?></td>
                                    <td><?php echo htmlspecialchars($damage['NrPolisy'] ?? 'Brak'); ?> (<?php echo htmlspecialchars($damage['TypPolisyUbezp'] ?? '-'); ?>)</td>
                                    <td title="<?php echo htmlspecialchars($damage['OpisZdarzenia']); ?>"><?php echo htmlspecialchars(mb_substr($damage['OpisZdarzenia'], 0, 35)) . (mb_strlen($damage['OpisZdarzenia']) > 35 ? '...' : ''); ?></td>
                                    <td>
                                        <?php
                                        $status_s = $damage['Status'] ?? 'nieznany';
                                        $statusClass_s = 'status-' . str_replace([' ', 'ł', 'ę', 'ą', 'ś', 'ć', 'ń', 'ó', 'ż', 'ź'], ['-', 'l', 'e', 'a', 's', 'c', 'n', 'o', 'z', 'z'], mb_strtolower($status_s));
                                        ?>
                                        <span class="status-badge <?php echo htmlspecialchars($statusClass_s); ?>"><?php echo htmlspecialchars($status_s); ?></span>
                                    </td>
                                    <td><?php echo formatMoney($damage['SzacowanaWartosc']); ?></td>
                                    <td><?php echo $damage['WartoscOdszkodowania'] ? formatMoney($damage['WartoscOdszkodowania']) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data"><i class="far fa-thumbs-up"></i>Gratulacje! Brak zgłoszonych szkód.</p>
            <?php endif; ?>
        </section>
    </div>

    <footer class="footer">
        <p>© <?php echo date('Y'); ?> Ubezpieczenia Pro. Kontakt: <a href="mailto:pomoc@ubezpieczenia.pro">pomoc@ubezpieczenia.pro</a></p>
    </footer>
</body>
</html>
