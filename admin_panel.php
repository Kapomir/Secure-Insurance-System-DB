<?php
require_once 'config.php';

// Sprawdź czy użytkownik jest zalogowany jako admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

$pdo = getConnection();
$success = null;
$error = null;
$edit_mode = null;
$edit_data = null;

// Odczytaj komunikaty z sesji, jeśli istnieją
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// --- LOGIKA OBSŁUGI FORMULARZY (POST requests) ---

$target_tab_hash = ''; // Domyślnie brak skoku

// KLIENT - Dodaj / Aktualizuj
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_client']) || isset($_POST['update_client']))) {
    $target_tab_hash = '#clients';
    $imieNazwisko = sanitize($_POST['imie_nazwisko']);
    $email = sanitize($_POST['email']);
    $pesel = sanitize($_POST['pesel']);
    $dataUrodzenia = $_POST['data_urodzenia'];
    $telefon = sanitize($_POST['telefon']);
    $haslo = $_POST['haslo_klient']; // Zmieniona nazwa pola
    $hashed_password = !empty($haslo) ? password_hash($haslo, PASSWORD_DEFAULT) : null;
    $ulica = sanitize($_POST['ulica']) ?: null;
    $numerDomu = sanitize($_POST['numer_domu']) ?: null;
    $kodPocztowy = sanitize($_POST['kod_pocztowy']) ?: null;
    $miasto = sanitize($_POST['miasto']) ?: null;
    $dataPrawkoJazdy = !empty($_POST['data_prawko_jazdy']) ? $_POST['data_prawko_jazdy'] : null;
    $dataPierwszejPolisy = !empty($_POST['data_pierwszej_polisy']) ? $_POST['data_pierwszej_polisy'] : null;
    $lataBezSzkody = filter_input(INPUT_POST, 'lata_bez_szkody', FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]) ?: 0;

    try {
        if (isset($_POST['add_client'])) {
            if(empty($haslo)) {
                $haslo = 'start123'; // Domyślne hasło, jeśli nie podano
                $hashed_password = password_hash($haslo, PASSWORD_DEFAULT);
            }
            $stmt = $pdo->prepare("INSERT INTO Klienci (ImieNazwisko, Email, PESEL, DataUrodzenia, Telefon, Haslo, Ulica, NumerDomu, KodPocztowy, Miasto, DataPrawkoJazdy, DataPierwszejPolisy, LataBezSzkody) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$imieNazwisko, $email, $pesel, $dataUrodzenia, $telefon, $hashed_password, $ulica, $numerDomu, $kodPocztowy, $miasto, $dataPrawkoJazdy, $dataPierwszejPolisy, $lataBezSzkody]);
            $_SESSION['success_message'] = "Klient ".htmlspecialchars($imieNazwisko)." został dodany. Hasło (plaintext): ".htmlspecialchars($haslo);
        } elseif (isset($_POST['update_client'])) {
            $idKlienta = $_POST['id_klienta_update'];
            if (!empty($haslo)) {
                 $stmt = $pdo->prepare("UPDATE Klienci SET ImieNazwisko=?, Email=?, PESEL=?, DataUrodzenia=?, Telefon=?, Haslo=?, Ulica=?, NumerDomu=?, KodPocztowy=?, Miasto=?, DataPrawkoJazdy=?, DataPierwszejPolisy=?, LataBezSzkody=? WHERE IDKlienta=?");
                 $stmt->execute([$imieNazwisko, $email, $pesel, $dataUrodzenia, $telefon, $hashed_password, $ulica, $numerDomu, $kodPocztowy, $miasto, $dataPrawkoJazdy, $dataPierwszejPolisy, $lataBezSzkody, $idKlienta]);
            } else {
                 $stmt = $pdo->prepare("UPDATE Klienci SET ImieNazwisko=?, Email=?, PESEL=?, DataUrodzenia=?, Telefon=?, Ulica=?, NumerDomu=?, KodPocztowy=?, Miasto=?, DataPrawkoJazdy=?, DataPierwszejPolisy=?, LataBezSzkody=? WHERE IDKlienta=?");
                 $stmt->execute([$imieNazwisko, $email, $pesel, $dataUrodzenia, $telefon, $ulica, $numerDomu, $kodPocztowy, $miasto, $dataPrawkoJazdy, $dataPierwszejPolisy, $lataBezSzkody, $idKlienta]);
            }
            $_SESSION['success_message'] = "Dane klienta ".htmlspecialchars($imieNazwisko)." zostały zaktualizowane.";
        }
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) $_SESSION['error_message'] = "Błąd: Email ('".htmlspecialchars($email)."') lub PESEL ('".htmlspecialchars($pesel)."') już istnieje w bazie.";
        else $_SESSION['error_message'] = "Błąd operacji na kliencie: " . $e->getMessage();
    }
    header("Location: admin_panel.php" . $target_tab_hash); exit();
}

// POJAZD - Dodaj / Aktualizuj
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_vehicle']) || isset($_POST['update_vehicle']))) {
    $target_tab_hash = '#vehicles';
    $idKlienta_v = $_POST['id_klienta_vehicle'];
    $nrRejestracyjny_v = sanitize($_POST['nr_rejestracyjny_vehicle']);
    $marka_v = sanitize($_POST['marka_vehicle']);
    $model_v = sanitize($_POST['model_vehicle']);
    $typPojazdu_v = sanitize($_POST['typ_pojazdu_vehicle']);
    $rokProdukcji_v = filter_input(INPUT_POST, 'rok_produkcji_vehicle', FILTER_VALIDATE_INT);
    $pojemnoscSilnika_v = filter_input(INPUT_POST, 'pojemnosc_silnika_vehicle', FILTER_VALIDATE_INT);
    $mocSilnika_v = filter_input(INPUT_POST, 'moc_silnika_vehicle', FILTER_VALIDATE_INT);
    $rodzajPaliwa_v = sanitize($_POST['rodzaj_paliwa_vehicle']);
    $przebieg_v = filter_input(INPUT_POST, 'przebieg_vehicle', FILTER_VALIDATE_INT);
    $wartoscRynkowa_v = filter_input(INPUT_POST, 'wartosc_rynkowa_vehicle', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $vin_v = sanitize(strtoupper($_POST['vin_vehicle']));
    $dataPierwszejRejestracji_v = !empty($_POST['data_pierwszej_rejestracji_vehicle']) ? $_POST['data_pierwszej_rejestracji_vehicle'] : null;

    try {
        if (isset($_POST['add_vehicle'])) {
            $stmt = $pdo->prepare("INSERT INTO Pojazdy (IDKlienta, NrRejestracyjny, Marka, Model, TypPojazdu, RokProdukcji, PojemnoscSilnika, MocSilnika, RodzajPaliwa, Przebieg, WartoscRynkowa, VIN, DataPierwszejRejestracji) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$idKlienta_v, $nrRejestracyjny_v, $marka_v, $model_v, $typPojazdu_v, $rokProdukcji_v, $pojemnoscSilnika_v, $mocSilnika_v, $rodzajPaliwa_v, $przebieg_v, $wartoscRynkowa_v, $vin_v, $dataPierwszejRejestracji_v]);
            $_SESSION['success_message'] = "Pojazd ".htmlspecialchars($marka_v)." ".htmlspecialchars($model_v)." został dodany.";
        } elseif (isset($_POST['update_vehicle'])) {
            $idPojazdu = $_POST['id_pojazdu_update'];
            $stmt = $pdo->prepare("UPDATE Pojazdy SET IDKlienta=?, NrRejestracyjny=?, Marka=?, Model=?, TypPojazdu=?, RokProdukcji=?, PojemnoscSilnika=?, MocSilnika=?, RodzajPaliwa=?, Przebieg=?, WartoscRynkowa=?, VIN=?, DataPierwszejRejestracji=? WHERE IDPojazdu=?");
            $stmt->execute([$idKlienta_v, $nrRejestracyjny_v, $marka_v, $model_v, $typPojazdu_v, $rokProdukcji_v, $pojemnoscSilnika_v, $mocSilnika_v, $rodzajPaliwa_v, $przebieg_v, $wartoscRynkowa_v, $vin_v, $dataPierwszejRejestracji_v, $idPojazdu]);
            $_SESSION['success_message'] = "Dane pojazdu ".htmlspecialchars($marka_v)." ".htmlspecialchars($model_v)." zostały zaktualizowane.";
        }
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) $_SESSION['error_message'] = "Błąd: Nr rejestracyjny ('".htmlspecialchars($nrRejestracyjny_v)."') lub VIN ('".htmlspecialchars($vin_v)."') już istnieje.";
        else $_SESSION['error_message'] = "Błąd operacji na pojeździe: " . $e->getMessage();
    }
    header("Location: admin_panel.php" . $target_tab_hash); exit();
}

// POLISA - Dodaj / Aktualizuj
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_policy']) || isset($_POST['update_policy']))) {
    $target_tab_hash = '#policies';
    $idKlienta_p = $_POST['id_klienta_policy'];
    $idPojazdu_p = $_POST['id_pojazdu_policy'];
    $idAgenta_p = $_POST['id_agenta_policy'];
    $typUbezpieczenia_p = sanitize($_POST['typ_ubezpieczenia_policy']);
    $dataRozpoczecia_p = $_POST['data_rozpoczecia_policy'];
    $dataZakonczenia_p = $_POST['data_zakonczenia_policy'];
    $sumaUbezpieczenia_p = filter_input(INPUT_POST, 'suma_ubezpieczenia_policy', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $skladkaKoncowa_p = filter_input(INPUT_POST, 'skladka_koncowa_policy', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $status_p = sanitize($_POST['status_policy']);

    $stmt_check_policy_nr = $pdo->prepare("SELECT 1 FROM Polisy WHERE NrPolisy = ?");
    $nrPolisy_p = isset($_POST['nr_polisy_policy']) && !empty($_POST['nr_polisy_policy']) ? sanitize($_POST['nr_polisy_policy']) : generatePolicyNumber($stmt_check_policy_nr);


    $skladkaBazowa_k = $skladkaKoncowa_p * (float)($_POST['skladka_bazowa_multiplier_hidden'] ?? 1.25); // Mnożnik z kalkulatora lub domyślny
    $wspRyzyka_k = (float)($_POST['wsp_ryzyka_hidden'] ?? 1.0);
    $znizki_k = ($skladkaBazowa_k * $wspRyzyka_k) - $skladkaKoncowa_p;


    try {
        $pdo->beginTransaction();
        if (isset($_POST['add_policy'])) {
            $stmt_kalk = $pdo->prepare("INSERT INTO Kalkulacje (IDKlienta, IDPojazdu, RodzajUbezpieczenia, SkladkaBazowa, WspolczynnikiRyzyka, ZnizkiZwyzki, SkladkaKoncowa) VALUES (?,?,?,?,?,?,?)");
            $stmt_kalk->execute([$idKlienta_p, $idPojazdu_p, $typUbezpieczenia_p, $skladkaBazowa_k, $wspRyzyka_k, $znizki_k, $skladkaKoncowa_p]);
            $idKalkulacji = $pdo->lastInsertId();

            $stmt_pol = $pdo->prepare("INSERT INTO Polisy (IDKlienta, IDPojazdu, IDKalkulacji, NrPolisy, TypUbezpieczenia, DataRozpoczecia, DataZakonczenia, SumaUbezpieczenia, SkladkaKoncowa, IDAgenta, Status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt_pol->execute([$idKlienta_p, $idPojazdu_p, $idKalkulacji, $nrPolisy_p, $typUbezpieczenia_p, $dataRozpoczecia_p, $dataZakonczenia_p, $sumaUbezpieczenia_p, $skladkaKoncowa_p, $idAgenta_p, $status_p]);
            $_SESSION['success_message'] = "Polisa ".htmlspecialchars($nrPolisy_p)." została dodana.";

        } elseif (isset($_POST['update_policy'])) {
            $idPolisy = $_POST['id_polisy_update'];
            $idKalkulacji_curr = $_POST['id_kalkulacji_current'];

            if($idKalkulacji_curr) {
                $stmt_kalk = $pdo->prepare("UPDATE Kalkulacje SET IDKlienta=?, IDPojazdu=?, RodzajUbezpieczenia=?, SkladkaBazowa=?, WspolczynnikiRyzyka=?, ZnizkiZwyzki=?, SkladkaKoncowa=? WHERE IDKalkulacji=?");
                $stmt_kalk->execute([$idKlienta_p, $idPojazdu_p, $typUbezpieczenia_p, $skladkaBazowa_k, $wspRyzyka_k, $znizki_k, $skladkaKoncowa_p, $idKalkulacji_curr]);
            } else {
                $stmt_kalk_new = $pdo->prepare("INSERT INTO Kalkulacje (IDKlienta, IDPojazdu, RodzajUbezpieczenia, SkladkaBazowa, WspolczynnikiRyzyka, ZnizkiZwyzki, SkladkaKoncowa) VALUES (?,?,?,?,?,?,?)");
                $stmt_kalk_new->execute([$idKlienta_p, $idPojazdu_p, $typUbezpieczenia_p, $skladkaBazowa_k, $wspRyzyka_k, $znizki_k, $skladkaKoncowa_p]);
                $idKalkulacji_curr = $pdo->lastInsertId();
            }

            $stmt_pol = $pdo->prepare("UPDATE Polisy SET IDKlienta=?, IDPojazdu=?, IDKalkulacji=?, NrPolisy=?, TypUbezpieczenia=?, DataRozpoczecia=?, DataZakonczenia=?, SumaUbezpieczenia=?, SkladkaKoncowa=?, IDAgenta=?, Status=? WHERE IDPolisy=?");
            $stmt_pol->execute([$idKlienta_p, $idPojazdu_p, $idKalkulacji_curr, $nrPolisy_p, $typUbezpieczenia_p, $dataRozpoczecia_p, $dataZakonczenia_p, $sumaUbezpieczenia_p, $skladkaKoncowa_p, $idAgenta_p, $status_p, $idPolisy]);
            $_SESSION['success_message'] = "Polisa ".htmlspecialchars($nrPolisy_p)." została zaktualizowana.";
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->errorInfo[1] == 1062 && strpos($e->getMessage(), 'NrPolisy') !== false) $_SESSION['error_message'] = "Błąd: Numer polisy '".htmlspecialchars($nrPolisy_p)."' musi być unikalny.";
        elseif ($e->errorInfo[1] == 1062 && strpos($e->getMessage(), 'IDKalkulacji') !== false) $_SESSION['error_message'] = "Błąd: ID Kalkulacji musi być unikalne dla polisy.";
        else $_SESSION['error_message'] = "Błąd operacji na polisie: " . $e->getMessage();
    }
    header("Location: admin_panel.php" . $target_tab_hash); exit();
}

// SZKODA - Dodaj / Aktualizuj
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_damage']) || isset($_POST['update_damage']))) {
    $target_tab_hash = '#damages';
    $idPolisy_d = $_POST['id_polisy_damage'] ?? $_POST['id_polisy_edit']; // Używamy odpowiedniego pola z formularza dodawania lub edycji
    $dataZdarzenia_d = $_POST['data_zdarzenia_damage'] ?? $_POST['data_zdarzenia_edit'];
    $opis_d = sanitize($_POST['opis_damage'] ?? $_POST['opis_edit']);
    $miejsce_d = sanitize($_POST['miejsce_damage'] ?? $_POST['miejsce_edit']);
    $przyczyna_d = sanitize($_POST['przyczyna_damage'] ?? $_POST['przyczyna_edit']);
    $szacowanaWartosc_d = $_POST['szacowana_wartosc_damage'] ?? $_POST['szacowana_wartosc_edit'];
    $status_d = $_POST['status_damage'] ?? $_POST['status_edit'];
    $odszkodowanie_d = !empty($_POST['odszkodowanie_damage'] ?? $_POST['odszkodowanie_edit']) ? ($_POST['odszkodowanie_damage'] ?? $_POST['odszkodowanie_edit']) : null;

    $stmt_policy_info_d = $pdo->prepare("SELECT IDKlienta, IDPojazdu FROM Polisy WHERE IDPolisy = ?");
    $stmt_policy_info_d->execute([$idPolisy_d]);
    $policy_info_d = $stmt_policy_info_d->fetch();

    if (!$policy_info_d) {
        $_SESSION['error_message'] = "Nie znaleziono polisy (ID: ".htmlspecialchars($idPolisy_d).") powiązanej ze szkodą.";
        header("Location: admin_panel.php" . $target_tab_hash); exit();
    }

    try {
        if (isset($_POST['add_damage'])) {
            $stmt_d = $pdo->prepare("INSERT INTO Szkody (IDPolisy, IDKlienta, IDPojazdu, DataZdarzenia, OpisZdarzenia, MiejsceZdarzenia, PrzyczynaSzkody, SzacowanaWartosc, WartoscOdszkodowania, Status) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt_d->execute([$idPolisy_d, $policy_info_d['IDKlienta'], $policy_info_d['IDPojazdu'], $dataZdarzenia_d, $opis_d, $miejsce_d, $przyczyna_d, $szacowanaWartosc_d, $odszkodowanie_d, $status_d]);
            $_SESSION['success_message'] = "Szkoda została dodana.";
        } elseif (isset($_POST['update_damage'])) {
            $idszkody_update = $_POST['idszkody_update'];
            $stmt_d = $pdo->prepare("UPDATE Szkody SET IDPolisy=?, IDKlienta=?, IDPojazdu=?, DataZdarzenia=?, OpisZdarzenia=?, MiejsceZdarzenia=?, PrzyczynaSzkody=?, SzacowanaWartosc=?, WartoscOdszkodowania=?, Status=? WHERE IDSzkody=?");
            $stmt_d->execute([$idPolisy_d, $policy_info_d['IDKlienta'], $policy_info_d['IDPojazdu'], $dataZdarzenia_d, $opis_d, $miejsce_d, $przyczyna_d, $szacowanaWartosc_d, $odszkodowanie_d, $status_d, $idszkody_update]);
            $_SESSION['success_message'] = "Szkoda (ID: ".htmlspecialchars($idszkody_update).") została zaktualizowana.";
        }
    } catch (PDOException $e) {
         $_SESSION['error_message'] = "Błąd operacji na szkodzie: " . $e->getMessage();
    }
    header("Location: admin_panel.php" . $target_tab_hash); exit();
}

// --- OBSŁUGA USUWANIA (GET requests) ---
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $type_to_delete = $_GET['delete'];
    $id_to_delete = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $target_tab_hash = '#'; // Domyślnie skaczemy do góry

    if ($id_to_delete) {
        $table_name = '';
        $id_column_name = '';
        $name_for_message = 'Rekord';

        switch ($type_to_delete) {
            // Nie dodajemy usuwania klientów, pojazdów, polis z tego poziomu dla bezpieczeństwa
            // case 'client': $table_name = 'Klienci'; $id_column_name = 'IDKlienta'; $target_tab_hash .= 'clients'; $name_for_message = 'Klient'; break;
            // case 'vehicle': $table_name = 'Pojazdy'; $id_column_name = 'IDPojazdu'; $target_tab_hash .= 'vehicles'; $name_for_message = 'Pojazd'; break;
            // case 'policy': $table_name = 'Polisy'; $id_column_name = 'IDPolisy'; $target_tab_hash .= 'policies'; $name_for_message = 'Polisa'; break;
            case 'damage': $table_name = 'Szkody'; $id_column_name = 'IDSzkody'; $target_tab_hash .= 'damages'; $name_for_message = 'Szkoda'; break;
        }

        if (!empty($table_name) && !empty($id_column_name)) {
            // Sprawdzenie, czy rekord istnieje (opcjonalne, ale dobre dla komunikatu)
            // $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM $table_name WHERE $id_column_name = ?");
            // $stmt_check->execute([$id_to_delete]);
            // if ($stmt_check->fetchColumn() > 0) {
                try {
                    $stmt_delete = $pdo->prepare("DELETE FROM $table_name WHERE $id_column_name = ?");
                    $stmt_delete->execute([$id_to_delete]);
                    $_SESSION['success_message'] = "$name_for_message (ID: ".htmlspecialchars($id_to_delete).") został usunięty.";
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Błąd usuwania ($name_for_message ID: ".htmlspecialchars($id_to_delete)."): " . $e->getMessage() . ". Możliwe, że istnieją powiązane rekordy.";
                }
            // } else {
            //     $_SESSION['error_message'] = "$name_for_message (ID: ".htmlspecialchars($id_to_delete).") nie został znaleziony.";
            // }
        } else {
            $_SESSION['error_message'] = "Nieprawidłowy typ rekordu do usunięcia.";
        }
    } else {
        $_SESSION['error_message'] = "Nieprawidłowe ID do usunięcia.";
    }
    header("Location: admin_panel.php" . ($target_tab_hash == '#' ? '' : $target_tab_hash)); exit();
}


// --- OBSŁUGA EDYCJI (GET requests - ustawienie $edit_mode i $edit_data) ---
if (isset($_GET['edit'])) {
    $edit_mode = $_GET['edit'];
    $id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $target_tab_hash_on_fail = '#dashboard';

    if ($id_to_edit) {
        switch ($edit_mode) {
            case 'client':
                $stmt = $pdo->prepare("SELECT * FROM Klienci WHERE IDKlienta = ?");
                $target_tab_hash_on_fail = '#clients';
                break;
            case 'vehicle':
                $stmt = $pdo->prepare("SELECT * FROM Pojazdy WHERE IDPojazdu = ?");
                $target_tab_hash_on_fail = '#vehicles';
                break;
            case 'policy':
                $stmt = $pdo->prepare("SELECT * FROM Polisy WHERE IDPolisy = ?");
                $target_tab_hash_on_fail = '#policies';
                break;
            case 'damage':
                $stmt = $pdo->prepare("SELECT * FROM Szkody WHERE IDSzkody = ?");
                $target_tab_hash_on_fail = '#damages';
                break;
            default:
                $_SESSION['error_message'] = "Nieznany typ obiektu do edycji.";
                header("Location: admin_panel.php" . $target_tab_hash_on_fail); exit();
        }
        $stmt->execute([$id_to_edit]);
        $edit_data = $stmt->fetch();

        if (!$edit_data) {
            $_SESSION['error_message'] = "Nie znaleziono rekordu do edycji (Typ: ".htmlspecialchars($edit_mode).", ID: ".htmlspecialchars($id_to_edit).").";
            header("Location: admin_panel.php" . $target_tab_hash_on_fail); exit();
        }
    } else {
        $_SESSION['error_message'] = "Nieprawidłowe ID do edycji.";
        header("Location: admin_panel.php" . $target_tab_hash_on_fail); exit();
    }
}

// --- Pobieranie danych do list i selectów ---
$clients = $pdo->query("SELECT IDKlienta, ImieNazwisko, PESEL, Email, Telefon FROM Klienci ORDER BY ImieNazwisko")->fetchAll();
$vehicles = $pdo->query("SELECT p.IDPojazdu, p.Marka, p.Model, p.NrRejestracyjny, p.VIN, p.WartoscRynkowa, p.IDKlienta, k.ImieNazwisko AS WlascicielImieNazwisko FROM Pojazdy p LEFT JOIN Klienci k ON p.IDKlienta = k.IDKlienta ORDER BY k.ImieNazwisko, p.Marka")->fetchAll();
$policies_list = $pdo->query("SELECT pol.*, k.ImieNazwisko AS KlientImieNazwisko, poj.Marka AS PojazdMarka, poj.Model AS PojazngModel, poj.NrRejestracyjny AS PojazdnrRej, ag.ImieNazwisko AS AgentImieNazwisko FROM Polisy pol LEFT JOIN Klienci k ON pol.IDKlienta = k.IDKlienta LEFT JOIN Pojazdy poj ON pol.IDPojazdu = poj.IDPojazdu LEFT JOIN Agenci ag ON pol.IDAgenta = ag.IDPracownika ORDER BY pol.DataRozpoczecia DESC")->fetchAll();
$damages_list = $pdo->query("SELECT s.*, k.ImieNazwisko AS KlientSzkody, p.NrPolisy, poj.Marka AS PojazdSzkodyMarka, poj.Model AS PojazdSzkodyModel, poj.NrRejestracyjny AS PojazdnrRejSzkody FROM Szkody s LEFT JOIN Klienci k ON s.IDKlienta = k.IDKlienta LEFT JOIN Polisy p ON s.IDPolisy = p.IDPolisy LEFT JOIN Pojazdy poj ON s.IDPojazdu = poj.IDPojazdu ORDER BY s.DataZgloszenia DESC")->fetchAll();
$agents = $pdo->query("SELECT IDPracownika, ImieNazwisko FROM Agenci ORDER BY ImieNazwisko")->fetchAll();
$activePoliciesForDamageForm = $pdo->query("SELECT p.IDPolisy, p.NrPolisy, k.ImieNazwisko, poj.Marka, poj.Model, poj.NrRejestracyjny FROM Polisy p JOIN Klienci k ON p.IDKlienta = k.IDKlienta JOIN Pojazdy poj ON p.IDPojazdu = poj.IDPojazdu WHERE p.Status = 'aktywna' ORDER BY p.NrPolisy")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administracyjny - Ubezpieczenia Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        /* Tutaj wklej cały blok CSS z odpowiedzi dla admin_panel.php z Etapu 1 */
        /* Upewnij się, że ten CSS jest zaktualizowany i spójny z nowym designem */
        :root {
            --primary-color: #3a7bd5; --primary-dark: #2a5a9e; --secondary-color: #2ecc71; /* Zielony */
            --text-color: #4A5568; --text-light: #718096; --bg-color: #F9FAFB; --white: #ffffff;
            --border-color: #E2E8F0; --border-radius-lg: 0.75rem; --border-radius-md: 0.5rem;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            --danger-color: #e53e3e; --warning-color: #f59e0b; --info-color: #3b82f6; --success-color: #10b981;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-color); color: var(--text-color); line-height: 1.65; font-size:15px; }
        .header {
            background: linear-gradient(to right, var(--primary-dark), var(--primary-color));
            color: var(--white); padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 1000;
        }
        .header-content {
            max-width: 1800px; margin: 0 auto; padding: 0 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header-logo { font-size: 1.8rem; font-weight: 600; color: var(--white); text-decoration: none; }
        .header-logo i { margin-right: 8px; }
        .admin-info span { margin-right: 1.5rem; font-size: 0.9rem; }
        .logout-btn {
            background-color: rgba(255,255,255,0.2); color: var(--white); padding: 0.6rem 1.2rem;
            text-decoration: none; border-radius: var(--border-radius-md); font-weight: 500; border: 1px solid rgba(255,255,255,0.3);
            transition: background-color 0.3s, color 0.3s;
        }
        .logout-btn:hover { background-color: var(--white); color: var(--primary-dark); }
        .logout-btn i { margin-right: 0.5rem; }

        .container { max-width: 1800px; margin: 2rem auto; padding: 0 2rem; }

        .tabs { display: flex; margin-bottom: 2rem; background-color: var(--white); border-radius:var(--border-radius-lg); box-shadow: var(--shadow-md); padding:0.5rem; flex-wrap: wrap; }
        .tab-button {
            flex-grow: 1; text-align: center; padding: 0.9rem 1rem; margin: 0.25rem;
            font-weight: 500; font-size: 0.95rem; color: var(--text-light);
            background-color: transparent; border:none; border-radius: var(--border-radius-md);
            cursor: pointer; transition: all 0.3s ease; outline:none;
        }
        .tab-button i { margin-right: 0.6rem; }
        .tab-button:hover { color: var(--primary-dark); background-color: rgba(58, 123, 213, 0.07); }
        .tab-button.active { color: var(--white); background-color: var(--primary-color); box-shadow: 0 2px 8px rgba(58, 123, 213,0.4); }

        .tab-content { animation: fadeIn 0.5s ease-out; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .card {
            background-color: var(--white); border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg); padding: 2rem 2.5rem; margin-bottom: 2.5rem;
        }
        .card-header, .card h2, .card h3 { /* Ujednolicone nagłówki w kartach */
            font-size: 1.6rem; font-weight: 600; color: var(--primary-dark);
            margin-bottom: 1.8rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);
            display: flex; align-items: center;
        }
        .card-header i, .card h2 i, .card h3 i { margin-right: 0.9rem; color: var(--primary-color); font-size: 1.7rem; }
        .card h3 { font-size: 1.4rem; margin-top: 2rem; margin-bottom: 1.2rem; }
        .card h3 i {font-size:1.5rem;}


        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-size: 0.9rem; font-weight: 500; color: var(--text-color); margin-bottom: 0.5rem; }
        input[type="text"], input[type="email"], input[type="password"], input[type="date"], input[type="number"], select, textarea {
            width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color);
            border-radius: var(--border-radius-md); font-size: 0.95rem; background-color: var(--white);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(58, 123, 213, 0.2); }
        textarea { resize: vertical; min-height: 100px; }
        select { appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 12px 10px; padding-right: 2.5rem;}
        input[readonly] { background-color: #e9ecef80; cursor: not-allowed; }

        .form-actions { margin-top: 2rem; display:flex; justify-content:flex-end; gap:1rem; }
        .btn {
            padding: 0.75rem 1.5rem; border: none; border-radius: var(--border-radius-md);
            font-size: 0.95rem; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center;
            cursor: pointer; transition: all 0.2s ease;
        }
        .btn i { margin-right: 0.5rem; }
        .btn:hover { opacity:0.85; transform: translateY(-1px); }
        .btn-primary { background-color: var(--primary-color); color: var(--white); box-shadow: 0 2px 4px rgba(58,123,213,0.2); }
        .btn-primary:hover { background-color: var(--primary-dark); }
        .btn-secondary { background-color: var(--text-light); color: var(--white); }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-success { background-color: var(--success-color); color: var(--white); }
        .btn-danger { background-color: var(--danger-color); color: var(--white); padding: 0.4rem 0.8rem; font-size: 0.85rem;}
        .btn-edit { background-color: var(--warning-color); color: #fff; padding: 0.4rem 0.8rem; font-size: 0.85rem;}

        .table-wrapper { overflow-x: auto; margin-top: 1.5rem; background-color: var(--white); border-radius:var(--border-radius-lg); box-shadow:var(--shadow-md); padding: 0.5rem;}
        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.9rem; }
        th, td { padding: 0.9rem 1.1rem; text-align: left; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
        th { background-color: var(--bg-color); font-weight: 600; color: var(--primary-dark); text-transform: uppercase; font-size:0.75rem; letter-spacing: 0.5px;}
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #f0f5fa; }
        td .btn { margin-right: 0.3rem; margin-bottom: 0.3rem; }

        .alert { padding: 1rem 1.25rem; margin-bottom: 1.5rem; border-radius: var(--border-radius-md); border: 1px solid transparent; display: flex; align-items:center; }
        .alert i { font-size: 1.3rem; margin-right: 1rem; }
        .alert-success { background-color: #d1e7dd; color: #0a3622; border-color: #a3cfbb;}
        .alert-error { background-color: #f8d7da; color: #58151c; border-color: #f1aeb5;}

        .status-badge { /* Updated status styles from client panel for consistency */
            padding: 0.3rem 0.75rem; border-radius: 50px; font-size: 0.8rem;
            font-weight: 500; display: inline-block; text-transform: capitalize; letter-spacing: 0.5px;
        }
        .status-aktywna { background-color: rgba(var(--rgb-success-color, 16, 185, 129), 0.1); color: var(--success-color, #057a55); } /* Poprawione dla zmiennych CSS */
        .status-wygasla, .status-zakonczona, .status-wygasła, .status-anulowana { background-color: rgba(var(--rgb-text-light, 113, 128, 150), 0.1); color: var(--text-light, #5a6268); }
        .status-zgłoszona, .status-zgloszona { background-color: rgba(var(--rgb-warning-color, 245, 159, 11), 0.1); color: var(--warning-color, #b95000); }
        .status-w-trakcie-likwidacji { background-color: rgba(var(--rgb-info-color, 59, 130, 246), 0.1); color: var(--info-color, #1c64f2); }
        .status-zlikwidowana { background-color: rgba(var(--rgb-success-color, 16, 185, 129), 0.15); color: var(--success-color, #057a55); }
        .status-odmowa { background-color: rgba(var(--rgb-danger-color, 229, 62, 62), 0.1); color: var(--danger-color, #9b2c2c); }

        .form-section { margin-bottom: 2.5rem; padding-bottom: 2rem; border-bottom: 1px dashed var(--border-color); }
        .form-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

        input[name="current_tab_hidden"] { display: none; } /* Jeśli używane dla przekierowań POST */

        .result-box { background-color: var(--bg-color); border: 1px solid var(--border-color); } /* Dopasowanie kalkulatora */

         /* Poprawka dla statusów, aby zmienne CSS dla kolorów tła i tekstu działały */
        :root {
            --rgb-success-color: 16, 185, 129; /* rgb dla #10b981 */
            --rgb-text-light: 113, 128, 150; /* rgb dla #718096 */
            --rgb-warning-color: 245, 159, 11; /* rgb dla #f59e0b */
            --rgb-info-color: 59, 130, 246; /* rgb dla #3b82f6 */
            --rgb-danger-color: 229, 62, 62; /* rgb dla #e53e3e */
            --success-color-text: #057a55;
            --text-light-color-text: #5a6268;
            --warning-color-text: #b95000;
            --info-color-text: #1c64f2;
            --danger-color-text: #9b2c2c;
        }
        .status-aktywna { background-color: rgba(var(--rgb-success-color), 0.1); color: var(--success-color-text); }
        .status-wygasla, .status-zakonczona, .status-wygasła, .status-anulowana { background-color: rgba(var(--rgb-text-light), 0.1); color: var(--text-light-color-text); }
        .status-zgłoszona, .status-zgloszona { background-color: rgba(var(--rgb-warning-color), 0.1); color: var(--warning-color-text); }
        .status-w-trakcie-likwidacji { background-color: rgba(var(--rgb-info-color), 0.1); color: var(--info-color-text); }
        .status-zlikwidowana { background-color: rgba(var(--rgb-success-color), 0.15); color: var(--success-color-text); }
        .status-odmowa { background-color: rgba(var(--rgb-danger-color), 0.1); color: var(--danger-color-text); }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="admin_panel.php" class="header-logo"><i class="fas fa-tachometer-alt"></i>Admin Panel Pro</a>
            <div class="admin-info">
                <span><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo htmlspecialchars($_SESSION['position']); ?>)</span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i>Wyloguj</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-button" data-tab="dashboard"><i class="fas fa-home"></i>Dashboard</button>
            <button class="tab-button" data-tab="clients"><i class="fas fa-users"></i>Klienci</button>
            <button class="tab-button" data-tab="vehicles"><i class="fas fa-car"></i>Pojazdy</button>
            <button class="tab-button" data-tab="policies"><i class="fas fa-file-contract"></i>Polisy</button>
            <button class="tab-button" data-tab="damages"><i class="fas fa-car-crash"></i>Szkody</button>
            <button class="tab-button" data-tab="calculator"><i class="fas fa-calculator"></i>Kalkulator</button>
        </div>

        <div class="tab-content">
            <div id="dashboard" class="tab-pane card">
                <div class="card-header"><i class="fas fa-chart-pie"></i>Przegląd Systemu</div>
                <p>Witaj w zaawansowanym panelu administracyjnym. Poniżej znajdują się kluczowe statystyki systemu.</p>
                <div class="form-grid" style="margin-top:2.5rem; gap:2rem;">
                    <div class="info-item" style="border-left-color: var(--primary-color);">
                        <span class="info-label">Zarejestrowani Klienci</span>
                        <span class="info-value" style="font-size:1.8rem; font-weight:600;"><?php echo count($clients); ?></span>
                    </div>
                    <div class="info-item" style="border-left-color: var(--success-color);">
                        <span class="info-label">Pojazdy w Bazie</span>
                        <span class="info-value" style="font-size:1.8rem; font-weight:600;"><?php echo count($vehicles); ?></span>
                    </div>
                     <div class="info-item" style="border-left-color: var(--info-color);">
                        <span class="info-label">Aktywne Polisy</span>
                        <span class="info-value" style="font-size:1.8rem; font-weight:600;"><?php echo count(array_filter($policies_list, fn($p) => strtolower($p['Status']) === 'aktywna')); ?> / <?php echo count($policies_list); ?></span>
                    </div>
                    <div class="info-item" style="border-left-color: var(--danger-color);">
                        <span class="info-label">Zgłoszone Szkody</span>
                        <span class="info-value" style="font-size:1.8rem; font-weight:600;"><?php echo count($damages_list); ?></span>
                    </div>
                     <div class="info-item" style="border-left-color: var(--warning-color);">
                        <span class="info-label">Liczba Agentów</span>
                        <span class="info-value" style="font-size:1.8rem; font-weight:600;"><?php echo count($agents); ?></span>
                    </div>
                </div>
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                    <p>Szybkie akcje:</p>
                    <button class="btn btn-primary" onclick="showTab('clients');document.getElementById('imie_nazwisko_add_client').focus();"><i class="fas fa-user-plus"></i>Dodaj Klienta</button>
                    <button class="btn btn-success" onclick="showTab('policies');document.getElementById('id_klienta_policy_add').focus();"><i class="fas fa-file-medical"></i>Dodaj Polisę</button>
                </div>
            </div>

                        <!-- Zakładka Klienci -->
            <div id="clients" class="tab-pane card">
                <div class="card-header"><i class="fas fa-users"></i>Zarządzanie Klientami</div>

                <?php if ($edit_mode === 'client' && $edit_data): ?>
                    <h3><i class="fas fa-user-edit"></i>Edytuj Klienta: <?php echo htmlspecialchars($edit_data['ImieNazwisko']); ?></h3>
                    <form method="POST" action="admin_panel.php" class="form-section">
                        <input type="hidden" name="update_client" value="1">
                        <input type="hidden" name="id_klienta_update" value="<?php echo $edit_data['IDKlienta']; ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="imie_nazwisko_edit_client">Imię i Nazwisko:</label>
                                <input type="text" name="imie_nazwisko" id="imie_nazwisko_edit_client" value="<?php echo htmlspecialchars($edit_data['ImieNazwisko']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email_edit_client">Email:</label>
                                <input type="email" name="email" id="email_edit_client" value="<?php echo htmlspecialchars($edit_data['Email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="pesel_edit_client">PESEL:</label>
                                <input type="text" name="pesel" id="pesel_edit_client" value="<?php echo htmlspecialchars($edit_data['PESEL'] ?? ''); ?>" pattern="[0-9]{11}" title="11 cyfr" required>
                            </div>
                            <div class="form-group">
                                <label for="data_urodzenia_edit_client">Data Urodzenia:</label>
                                <input type="date" name="data_urodzenia" id="data_urodzenia_edit_client" value="<?php echo htmlspecialchars($edit_data['DataUrodzenia'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="telefon_edit_client">Telefon:</label>
                                <input type="text" name="telefon" id="telefon_edit_client" value="<?php echo htmlspecialchars($edit_data['Telefon'] ?? ''); ?>" pattern="[0-9]{9,15}">
                            </div>
                            <div class="form-group">
                                <label for="haslo_klient_edit">Hasło (plaintext, pozostaw puste by nie zmieniać):</label>
                                <input type="text" name="haslo_klient" id="haslo_klient_edit" placeholder="Nowe hasło">
                            </div>
                             <div class="form-group">
                                <label for="lata_bez_szkody_edit_client">Lata Bezszkodowej Jazdy:</label>
                                <input type="number" name="lata_bez_szkody" id="lata_bez_szkody_edit_client" value="<?php echo htmlspecialchars($edit_data['LataBezSzkody'] ?? '0'); ?>" min="0" required>
                            </div>
                            <div class="form-group">
                                <label for="ulica_edit_client">Ulica:</label>
                                <input type="text" name="ulica" id="ulica_edit_client" value="<?php echo htmlspecialchars($edit_data['Ulica'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="numer_domu_edit_client">Nr Domu:</label>
                                <input type="text" name="numer_domu" id="numer_domu_edit_client" value="<?php echo htmlspecialchars($edit_data['NumerDomu'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="kod_pocztowy_edit_client">Kod Pocztowy:</label>
                                <input type="text" name="kod_pocztowy" id="kod_pocztowy_edit_client" value="<?php echo htmlspecialchars($edit_data['KodPocztowy'] ?? ''); ?>" pattern="[0-9]{2}-[0-9]{3}">
                            </div>
                            <div class="form-group">
                                <label for="miasto_edit_client">Miasto:</label>
                                <input type="text" name="miasto" id="miasto_edit_client" value="<?php echo htmlspecialchars($edit_data['Miasto'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="data_prawko_jazdy_edit_client">Data Prawa Jazdy:</label>
                                <input type="date" name="data_prawko_jazdy" id="data_prawko_jazdy_edit_client" value="<?php echo htmlspecialchars($edit_data['DataPrawkoJazdy'] ?? ''); ?>">
                            </div>
                             <div class="form-group">
                                <label for="data_pierwszej_polisy_edit_client">Data Pierwszej Polisy:</label>
                                <input type="date" name="data_pierwszej_polisy" id="data_pierwszej_polisy_edit_client" value="<?php echo htmlspecialchars($edit_data['DataPierwszejPolisy'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Zapisz Zmiany</button>
                            <a href="admin_panel.php#clients" class="btn btn-secondary"><i class="fas fa-times"></i>Anuluj</a>
                        </div>
                    </form>
                <?php else: ?>
                    <h3><i class="fas fa-user-plus"></i>Dodaj Nowego Klienta</h3>
                    <form method="POST" action="admin_panel.php" class="form-section">
                        <input type="hidden" name="add_client" value="1">
                        <div class="form-grid">
                            <div class="form-group"><label for="imie_nazwisko_add_client">Imię i Nazwisko:</label><input type="text" name="imie_nazwisko" id="imie_nazwisko_add_client" required></div>
                            <div class="form-group"><label for="email_add_client">Email:</label><input type="email" name="email" id="email_add_client" required></div>
                            <div class="form-group"><label for="pesel_add_client">PESEL:</label><input type="text" name="pesel" id="pesel_add_client" pattern="[0-9]{11}" title="11 cyfr" required></div>
                            <div class="form-group"><label for="data_urodzenia_add_client">Data Urodzenia:</label><input type="date" name="data_urodzenia" id="data_urodzenia_add_client" value="<?php echo date('Y-m-d', strtotime('-28 years')); ?>" required></div>
                            <div class="form-group"><label for="telefon_add_client">Telefon:</label><input type="text" name="telefon" id="telefon_add_client" pattern="[0-9]{9,15}"></div>
                            <div class="form-group"><label for="haslo_klient_add">Hasło (plaintext):</label><input type="text" name="haslo_klient" id="haslo_klient_add" value="klientpass" required></div>
                            <div class="form-group"><label for="lata_bez_szkody_add_client">Lata Bezszkodowej Jazdy:</label><input type="number" name="lata_bez_szkody" id="lata_bez_szkody_add_client" value="0" min="0" required></div>
                            <div class="form-group"><label for="ulica_add_client">Ulica:</label><input type="text" name="ulica" id="ulica_add_client"></div>
                            <div class="form-group"><label for="numer_domu_add_client">Nr Domu:</label><input type="text" name="numer_domu" id="numer_domu_add_client"></div>
                            <div class="form-group"><label for="kod_pocztowy_add_client">Kod Pocztowy:</label><input type="text" name="kod_pocztowy" id="kod_pocztowy_add_client" pattern="[0-9]{2}-[0-9]{3}"></div>
                            <div class="form-group"><label for="miasto_add_client">Miasto:</label><input type="text" name="miasto" id="miasto_add_client"></div>
                            <div class="form-group"><label for="data_prawko_jazdy_add_client">Data Prawa Jazdy:</label><input type="date" name="data_prawko_jazdy" id="data_prawko_jazdy_add_client"></div>
                            <div class="form-group"><label for="data_pierwszej_polisy_add_client">Data Pierwszej Polisy:</label><input type="date" name="data_pierwszej_polisy" id="data_pierwszej_polisy_add_client"></div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i>Dodaj Klienta</button>
                        </div>
                    </form>
                <?php endif; ?>

                <h3 style="margin-top:2.5rem;"><i class="fas fa-list-ul"></i>Lista Klientów</h3>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>ID</th><th>Imię i Nazwisko</th><th>Email</th><th>PESEL</th><th>Telefon</th><th>Akcje</th></tr></thead>
                        <tbody>
                            <?php foreach($clients as $client_item): ?>
                            <tr>
                                <td><?php echo $client_item['IDKlienta']; ?></td>
                                <td><?php echo htmlspecialchars($client_item['ImieNazwisko']); ?></td>
                                <td><?php echo htmlspecialchars($client_item['Email']); ?></td>
                                <td><?php echo htmlspecialchars($client_item['PESEL'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($client_item['Telefon'] ?? '-'); ?></td>
                                <td><a href="admin_panel.php?edit=client&id=<?php echo $client_item['IDKlienta']; ?>#clients" class="btn btn-edit"><i class="fas fa-user-edit"></i>Edytuj</a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($clients)): ?><tr><td colspan="6" style="text-align:center;">Brak klientów w bazie.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- koniec #clients -->

            <!-- Zakładka Pojazdy -->
            <div id="vehicles" class="tab-pane card">
                 <div class="card-header"><i class="fas fa-car-side"></i>Zarządzanie Pojazdami</div>
                <?php if ($edit_mode === 'vehicle' && $edit_data): ?>
                     <h3><i class="fas fa-edit"></i>Edytuj Pojazd: <?php echo htmlspecialchars($edit_data['Marka'] . ' ' . $edit_data['Model'] . ' (' . $edit_data['NrRejestracyjny'].')'); ?></h3>
                     <form method="POST" action="admin_panel.php" class="form-section">
                        <input type="hidden" name="update_vehicle" value="1">
                        <input type="hidden" name="id_pojazdu_update" value="<?php echo $edit_data['IDPojazdu']; ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="id_klienta_vehicle_edit">Właściciel (Klient):</label>
                                <select name="id_klienta_vehicle" id="id_klienta_vehicle_edit" required>
                                    <?php foreach($clients as $c_v_e): ?>
                                    <option value="<?php echo $c_v_e['IDKlienta']; ?>" <?php if($edit_data['IDKlienta'] == $c_v_e['IDKlienta']) echo 'selected'; ?>><?php echo htmlspecialchars($c_v_e['ImieNazwisko'] . " (ID: ".$c_v_e['IDKlienta'].")"); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="nr_rejestracyjny_vehicle_edit">Nr Rejestracyjny:</label><input type="text" name="nr_rejestracyjny_vehicle" id="nr_rejestracyjny_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['NrRejestracyjny']); ?>" required></div>
                            <div class="form-group"><label for="marka_vehicle_edit">Marka:</label><input type="text" name="marka_vehicle" id="marka_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['Marka']); ?>" required></div>
                            <div class="form-group"><label for="model_vehicle_edit">Model:</label><input type="text" name="model_vehicle" id="model_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['Model']); ?>" required></div>
                            <div class="form-group"><label for="typ_pojazdu_vehicle_edit">Typ Pojazdu:</label><input type="text" name="typ_pojazdu_vehicle" id="typ_pojazdu_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['TypPojazdu'] ?? 'osobowy'); ?>" list="typy_pojazdow_list"></div>
                            <div class="form-group"><label for="rok_produkcji_vehicle_edit">Rok Produkcji:</label><input type="number" name="rok_produkcji_vehicle" id="rok_produkcji_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['RokProdukcji'] ?? ''); ?>" min="1900" max="<?php echo date('Y')+1; ?>" required></div>
                            <div class="form-group"><label for="pojemnosc_silnika_vehicle_edit">Pojemność Silnika (cm³):</label><input type="number" name="pojemnosc_silnika_vehicle" id="pojemnosc_silnika_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['PojemnoscSilnika'] ?? ''); ?>" min="0"></div>
                             <div class="form-group"><label for="moc_silnika_vehicle_edit">Moc Silnika (KM):</label><input type="number" name="moc_silnika_vehicle" id="moc_silnika_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['MocSilnika'] ?? ''); ?>" min="0"></div>
                            <div class="form-group"><label for="rodzaj_paliwa_vehicle_edit">Rodzaj Paliwa:</label><input type="text" name="rodzaj_paliwa_vehicle" id="rodzaj_paliwa_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['RodzajPaliwa'] ?? ''); ?>" list="rodzaje_paliwa_list"></div>
                            <div class="form-group"><label for="przebieg_vehicle_edit">Przebieg (km):</label><input type="number" name="przebieg_vehicle" id="przebieg_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['Przebieg'] ?? ''); ?>" min="0"></div>
                            <div class="form-group"><label for="wartosc_rynkowa_vehicle_edit">Wartość Rynkowa (zł):</label><input type="number" name="wartosc_rynkowa_vehicle" id="wartosc_rynkowa_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['WartoscRynkowa'] ?? ''); ?>" step="0.01" min="0"></div>
                            <div class="form-group"><label for="vin_vehicle_edit">VIN:</label><input type="text" name="vin_vehicle" id="vin_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['VIN']); ?>" pattern="[A-HJ-NPR-Z0-9]{17}" title="17-znakowy numer VIN" required></div>
                            <div class="form-group"><label for="data_pierwszej_rejestracji_vehicle_edit">Data Pierwszej Rejestracji:</label><input type="date" name="data_pierwszej_rejestracji_vehicle" id="data_pierwszej_rejestracji_vehicle_edit" value="<?php echo htmlspecialchars($edit_data['DataPierwszejRejestracji'] ?? ''); ?>"></div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Zapisz Zmiany</button>
                            <a href="admin_panel.php#vehicles" class="btn btn-secondary"><i class="fas fa-times"></i>Anuluj</a>
                        </div>
                    </form>
                <?php else: ?>
                    <h3><i class="fas fa-car-alt"></i>Dodaj Nowy Pojazd</h3>
                     <form method="POST" action="admin_panel.php" class="form-section">
                        <input type="hidden" name="add_vehicle" value="1">
                        <div class="form-grid">
                             <div class="form-group">
                                <label for="id_klienta_vehicle_add">Właściciel (Klient):</label>
                                <select name="id_klienta_vehicle" id="id_klienta_vehicle_add" required>
                                    <option value="">-- Wybierz klienta --</option>
                                    <?php foreach($clients as $c_v_a): ?> <option value="<?php echo $c_v_a['IDKlienta']; ?>"><?php echo htmlspecialchars($c_v_a['ImieNazwisko'] . " (ID: ".$c_v_a['IDKlienta'].")"); ?></option> <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="nr_rejestracyjny_vehicle_add">Nr Rejestracyjny:</label><input type="text" name="nr_rejestracyjny_vehicle" id="nr_rejestracyjny_vehicle_add" required></div>
                            <div class="form-group"><label for="marka_vehicle_add">Marka:</label><input type="text" name="marka_vehicle" id="marka_vehicle_add" required></div>
                            <div class="form-group"><label for="model_vehicle_add">Model:</label><input type="text" name="model_vehicle" id="model_vehicle_add" required></div>
                            <div class="form-group"><label for="typ_pojazdu_vehicle_add">Typ Pojazdu:</label><input type="text" name="typ_pojazdu_vehicle" id="typ_pojazdu_vehicle_add" value="osobowy" list="typy_pojazdow_list"></div>
                            <div class="form-group"><label for="rok_produkcji_vehicle_add">Rok Produkcji:</label><input type="number" name="rok_produkcji_vehicle" id="rok_produkcji_vehicle_add" min="1900" max="<?php echo date('Y')+1; ?>" value="<?php echo date('Y')-5; ?>" required></div>
                            <div class="form-group"><label for="pojemnosc_silnika_vehicle_add">Pojemność Silnika (cm³):</label><input type="number" name="pojemnosc_silnika_vehicle" id="pojemnosc_silnika_vehicle_add" min="0" placeholder="np. 1598"></div>
                            <div class="form-group"><label for="moc_silnika_vehicle_add">Moc Silnika (KM):</label><input type="number" name="moc_silnika_vehicle" id="moc_silnika_vehicle_add" min="0" placeholder="np. 130"></div>
                            <div class="form-group"><label for="rodzaj_paliwa_vehicle_add">Rodzaj Paliwa:</label><input type="text" name="rodzaj_paliwa_vehicle" id="rodzaj_paliwa_vehicle_add" list="rodzaje_paliwa_list" placeholder="np. Benzyna"></div>
                            <div class="form-group"><label for="przebieg_vehicle_add">Przebieg (km):</label><input type="number" name="przebieg_vehicle" id="przebieg_vehicle_add" min="0" placeholder="np. 120000"></div>
                            <div class="form-group"><label for="wartosc_rynkowa_vehicle_add">Wartość Rynkowa (zł):</label><input type="number" name="wartosc_rynkowa_vehicle" id="wartosc_rynkowa_vehicle_add" step="0.01" min="0" placeholder="np. 50000.00"></div>
                            <div class="form-group"><label for="vin_vehicle_add">VIN:</label><input type="text" name="vin_vehicle" id="vin_vehicle_add" pattern="[A-HJ-NPR-Z0-9]{17}" title="17-znakowy numer VIN" required></div>
                            <div class="form-group"><label for="data_pierwszej_rejestracji_vehicle_add">Data Pierwszej Rejestracji:</label><input type="date" name="data_pierwszej_rejestracji_vehicle" id="data_pierwszej_rejestracji_vehicle_add"></div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i>Dodaj Pojazd</button>
                        </div>
                    </form>
                    <datalist id="typy_pojazdow_list"> <option value="osobowy"><option value="ciężarowy"><option value="motocykl"><option value="dostawczy"><option value="autobus"><option value="ciągnik"><option value="inny"> </datalist>
                    <datalist id="rodzaje_paliwa_list"> <option value="Benzyna"><option value="Diesel"><option value="LPG"><option value="Elektryczny"><option value="Hybryda Plugin"><option value="Hybryda"><option value="Wodór"> </datalist>
                <?php endif; ?>

                <h3 style="margin-top:2.5rem;"><i class="fas fa-list-ul"></i>Lista Pojazdów</h3>
                 <div class="table-wrapper">
                    <table>
                        <thead><tr><th>ID</th><th>Właściciel</th><th>Nr Rej.</th><th>Marka/Model</th><th>VIN</th><th>Wartość</th><th>Akcje</th></tr></thead>
                        <tbody>
                            <?php foreach($vehicles as $vehicle_item): ?>
                            <tr>
                                <td><?php echo $vehicle_item['IDPojazdu']; ?></td>
                                <td><?php echo htmlspecialchars($vehicle_item['WlascicielImieNazwisko'] ?? 'Brak właściciela'); ?></td>
                                <td><?php echo htmlspecialchars($vehicle_item['NrRejestracyjny']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle_item['Marka'] . " " . $vehicle_item['Model']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle_item['VIN']); ?></td>
                                <td><?php echo formatMoney($vehicle_item['WartoscRynkowa']); ?></td>
                                <td><a href="admin_panel.php?edit=vehicle&id=<?php echo $vehicle_item['IDPojazdu']; ?>#vehicles" class="btn btn-edit"><i class="fas fa-edit"></i>Edytuj</a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($vehicles)): ?><tr><td colspan="7" style="text-align:center;">Brak pojazdów w bazie.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- koniec #vehicles -->

            <!-- Zakładka Polisy -->
            <div id="policies" class="tab-pane card">
                <div class="card-header"><i class="fas fa-file-signature"></i>Zarządzanie Polisami</div>
                 <?php if ($edit_mode === 'policy' && $edit_data): ?>
                    <h3><i class="fas fa-edit"></i>Edytuj Polisę: <?php echo htmlspecialchars($edit_data['NrPolisy']); ?></h3>
                    <form method="POST" action="admin_panel.php" class="form-section">
                        <input type="hidden" name="update_policy" value="1">
                        <input type="hidden" name="id_polisy_update" value="<?php echo $edit_data['IDPolisy']; ?>">
                        <input type="hidden" name="id_kalkulacji_current" value="<?php echo $edit_data['IDKalkulacji']; ?>">
                         <div class="form-grid">
                            <div class="form-group">
                                <label for="id_klienta_policy_edit">Klient:</label>
                                <select name="id_klienta_policy" id="id_klienta_policy_edit" required onchange="loadClientVehicles('id_klienta_policy_edit', 'id_pojazdu_policy_edit_form')">
                                    <option value="">-- Wybierz --</option>
                                    <?php foreach($clients as $c_p_e): ?>
                                    <option value="<?php echo $c_p_e['IDKlienta']; ?>" <?php if($edit_data['IDKlienta'] == $c_p_e['IDKlienta']) echo 'selected'; ?>><?php echo htmlspecialchars($c_p_e['ImieNazwisko']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="id_pojazdu_policy_edit_form">Pojazd:</label>
                                <select name="id_pojazdu_policy" id="id_pojazdu_policy_edit_form" required>
                                    <option value="">-- Wybierz klienta --</option>
                                    <?php // Ta część załaduje się dynamicznie przez JS lub na podstawie PHP jeśli $edit_data['IDKlienta'] jest znane
                                        if(isset($edit_data['IDKlienta']) && $edit_data['IDKlienta']){
                                            $stmt_v_edit = $pdo->prepare("SELECT IDPojazdu, Marka, Model, NrRejestracyjny FROM Pojazdy WHERE IDKlienta = ? ORDER BY Marka, Model");
                                            $stmt_v_edit->execute([$edit_data['IDKlienta']]);
                                            $client_vehicles_edit = $stmt_v_edit->fetchAll();
                                            if(empty($client_vehicles_edit)) {
                                                echo '<option value="">-- Klient nie ma pojazdów --</option>';
                                            } else {
                                                foreach($client_vehicles_edit as $v_e): ?>
                                                    <option value="<?php echo $v_e['IDPojazdu']; ?>" <?php if(isset($edit_data['IDPojazdu']) && $edit_data['IDPojazdu'] == $v_e['IDPojazdu']) echo 'selected'; ?>>
                                                        <?php echo htmlspecialchars($v_e['Marka'] . ' ' . $v_e['Model'] . ' (' . $v_e['NrRejestracyjny'] . ')'); ?>
                                                    </option>
                                            <?php endforeach;
                                            }
                                        }
                                    ?>
                                </select>
                            </div>
                             <div class="form-group">
                                <label for="nr_polisy_policy_edit">Numer Polisy:</label>
                                <input type="text" name="nr_polisy_policy" id="nr_polisy_policy_edit" value="<?php echo htmlspecialchars($edit_data['NrPolisy']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="typ_ubezpieczenia_policy_edit">Typ Ubezpieczenia:</label>
                                <select name="typ_ubezpieczenia_policy" id="typ_ubezpieczenia_policy_edit" required>
                                    <option value="OC" <?php if($edit_data['TypUbezpieczenia'] == 'OC') echo 'selected'; ?>>OC</option>
                                    <option value="AC" <?php if($edit_data['TypUbezpieczenia'] == 'AC') echo 'selected'; ?>>AC</option>
                                    <option value="OCAC" <?php if($edit_data['TypUbezpieczenia'] == 'OCAC') echo 'selected'; ?>>OC+AC</option>
                                </select>
                            </div>
                            <div class="form-group"><label for="data_rozpoczecia_policy_edit">Data Rozpoczęcia:</label><input type="date" name="data_rozpoczecia_policy" id="data_rozpoczecia_policy_edit" value="<?php echo htmlspecialchars($edit_data['DataRozpoczecia']); ?>" required></div>
                            <div class="form-group"><label for="data_zakonczenia_policy_edit">Data Zakończenia:</label><input type="date" name="data_zakonczenia_policy" id="data_zakonczenia_policy_edit" value="<?php echo htmlspecialchars($edit_data['DataZakonczenia']); ?>" required></div>
                             <div class="form-group"><label for="suma_ubezpieczenia_policy_edit">Suma Ubezpieczenia (zł):</label><input type="number" name="suma_ubezpieczenia_policy" id="suma_ubezpieczenia_policy_edit" value="<?php echo htmlspecialchars($edit_data['SumaUbezpieczenia']); ?>" step="0.01" min="0" required></div>
                            <div class="form-group"><label for="skladka_koncowa_policy_edit">Składka Końcowa (zł):</label><input type="number" name="skladka_koncowa_policy" id="skladka_koncowa_policy_edit" value="<?php echo htmlspecialchars($edit_data['SkladkaKoncowa']); ?>" step="0.01" min="0" required></div>
                            <div class="form-group">
                                <label for="id_agenta_policy_edit">Agent:</label>
                                <select name="id_agenta_policy" id="id_agenta_policy_edit" required>
                                     <?php foreach($agents as $agent_item): ?>
                                    <option value="<?php echo $agent_item['IDPracownika']; ?>" <?php if($edit_data['IDAgenta'] == $agent_item['IDPracownika']) echo 'selected'; ?>><?php echo htmlspecialchars($agent_item['ImieNazwisko']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status_policy_edit">Status Polisy:</label>
                                <select name="status_policy" id="status_policy_edit" required>
                                    <option value="aktywna" <?php if($edit_data['Status'] == 'aktywna') echo 'selected'; ?>>Aktywna</option>
                                    <option value="wygasla" <?php if($edit_data['Status'] == 'wygasla') echo 'selected'; ?>>Wygasła</option>
                                    <option value="anulowana" <?php if($edit_data['Status'] == 'anulowana') echo 'selected'; ?>>Anulowana</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Zapisz Zmiany</button>
                            <a href="admin_panel.php#policies" class="btn btn-secondary"><i class="fas fa-times"></i>Anuluj</a>
                        </div>
                    </form>
                <?php else: ?>
                    <h3><i class="fas fa-file-medical"></i>Dodaj Nową Polisę</h3>
                     <form method="POST" action="admin_panel.php" class="form-section">
                        <input type="hidden" name="add_policy" value="1">
                        <!-- Ukryte pola do przekazania danych z kalkulatora (jeśli zintegrowane) -->
                        <input type="hidden" name="skladka_bazowa_multiplier_hidden" id="skladka_bazowa_multiplier_hidden_add" value="1.25">
                        <input type="hidden" name="wsp_ryzyka_hidden" id="wsp_ryzyka_hidden_add" value="1.0">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="id_klienta_policy_add">Klient:</label>
                                <select name="id_klienta_policy" id="id_klienta_policy_add" required onchange="loadClientVehicles('id_klienta_policy_add', 'id_pojazdu_policy_add_form')">
                                    <option value="">-- Wybierz klienta --</option>
                                    <?php foreach($clients as $c_p_a): ?> <option value="<?php echo $c_p_a['IDKlienta']; ?>"><?php echo htmlspecialchars($c_p_a['ImieNazwisko']); ?></option> <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="id_pojazdu_policy_add_form">Pojazd:</label>
                                <select name="id_pojazdu_policy" id="id_pojazdu_policy_add_form" required disabled><option value="">-- Najpierw wybierz klienta --</option></select>
                            </div>
                            <div class="form-group"><label for="nr_polisy_policy_add">Numer Polisy (auto lub wpisz):</label><input type="text" name="nr_polisy_policy" id="nr_polisy_policy_add" placeholder="Automatyczny, np. <?php echo generatePolicyNumber(); ?>"></div>
                            <div class="form-group">
                                <label for="typ_ubezpieczenia_policy_add">Typ Ubezpieczenia:</label>
                                <select name="typ_ubezpieczenia_policy" id="typ_ubezpieczenia_policy_add" required> <option value="OC" selected>OC</option> <option value="AC">AC</option> <option value="OCAC">OC+AC</option> </select>
                            </div>
                            <div class="form-group"><label for="data_rozpoczecia_policy_add">Data Rozpoczęcia:</label><input type="date" name="data_rozpoczecia_policy" id="data_rozpoczecia_policy_add" value="<?php echo date('Y-m-d'); ?>" required></div>
                            <div class="form-group"><label for="data_zakonczenia_policy_add">Data Zakończenia:</label><input type="date" name="data_zakonczenia_policy" id="data_zakonczenia_policy_add" value="<?php echo date('Y-m-d', strtotime('+1 year -1 day')); ?>" required></div>
                            <div class="form-group"><label for="suma_ubezpieczenia_policy_add">Suma Ubezpieczenia (zł):</label><input type="number" name="suma_ubezpieczenia_policy" id="suma_ubezpieczenia_policy_add" step="1000" min="0" value="1000000" required></div>
                            <div class="form-group"><label for="skladka_koncowa_policy_add">Składka Końcowa (zł):</label><input type="number" name="skladka_koncowa_policy" id="skladka_koncowa_policy_add" step="0.01" min="0" required placeholder="np. 650.50"></div>
                            <div class="form-group">
                                <label for="id_agenta_policy_add">Agent Wystawiający:</label>
                                <select name="id_agenta_policy" id="id_agenta_policy_add" required>
                                    <?php foreach($agents as $agent_item): ?> <option value="<?php echo $agent_item['IDPracownika']; ?>"><?php echo htmlspecialchars($agent_item['ImieNazwisko']); ?></option> <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="status_policy_add">Status Polisy:</label>
                                <select name="status_policy" id="status_policy_add" required><option value="aktywna" selected>Aktywna</option><option value="wygasla">Wygasła</option><option value="anulowana">Anulowana</option></select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i>Dodaj Polisę</button>
                        </div>
                    </form>
                <?php endif; ?>

                <h3 style="margin-top:2.5rem;"><i class="fas fa-list-ul"></i>Lista Polis</h3>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Nr Polisy</th><th>Klient</th><th>Pojazd (Nr rej.)</th><th>Typ</th><th>Okres</th><th>Składka</th><th>Agent</th><th>Status</th><th>Akcje</th></tr></thead>
                        <tbody>
                            <?php foreach($policies_list as $policy_item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($policy_item['NrPolisy']); ?></strong></td>
                                <td><?php echo htmlspecialchars($policy_item['KlientImieNazwisko'] ?? 'Brak'); ?></td>
                                <td><?php echo htmlspecialchars(($policy_item['PojazdMarka'] ?? '') . " " . ($policy_item['PojazngModel'] ?? '') . ' ('. ($policy_item['PojazdnrRej'] ?? 'Brak') .')' ); ?></td>
                                <td><?php echo htmlspecialchars($policy_item['TypUbezpieczenia']); ?></td>
                                <td><?php echo formatDate($policy_item['DataRozpoczecia']); ?><br><?php echo formatDate($policy_item['DataZakonczenia']); ?></td>
                                <td><?php echo formatMoney($policy_item['SkladkaKoncowa']); ?></td>
                                <td><?php echo htmlspecialchars($policy_item['AgentImieNazwisko'] ?? 'Brak'); ?></td>
                                <td>
                                     <?php
                                        $status_pl_item = $policy_item['Status'];
                                        if (strtolower($status_pl_item) == 'aktywna' && strtotime($policy_item['DataZakonczenia']) < time()) $status_pl_item = 'wygasla';
                                        $statusClass_pl_item = 'status-badge status-' . str_replace([' ', 'ł', 'ę', 'ą', 'ś', 'ć', 'ń', 'ó', 'ż', 'ź'], ['-', 'l', 'e', 'a', 's', 'c', 'n', 'o', 'z', 'z'], mb_strtolower($status_pl_item));
                                    ?>
                                    <span class="<?php echo htmlspecialchars($statusClass_pl_item); ?>"><?php echo htmlspecialchars(ucfirst($status_pl_item)); ?></span>
                                </td>
                                <td><a href="admin_panel.php?edit=policy&id=<?php echo $policy_item['IDPolisy']; ?>#policies" class="btn btn-edit"><i class="fas fa-edit"></i>Edytuj</a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($policies_list)): ?><tr><td colspan="9" style="text-align:center;">Brak polis w systemie.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- koniec #policies -->

            <!-- Zakładka Szkody -->
            <div id="damages" class="tab-pane card">
                <div class="card-header"><i class="fas fa-car-burst"></i>Zarządzanie Szkodami</div>
                 <?php if ($edit_mode === 'damage' && $edit_data): ?>
                    <h3><i class="fas fa-wrench"></i>Edytuj Szkodę (ID: <?php echo htmlspecialchars($edit_data['IDSzkody']); ?>)</h3>
                    <form method="POST" action="admin_panel.php" class="form-section">
                        <input type="hidden" name="update_damage" value="1">
                        <input type="hidden" name="idszkody_update" value="<?php echo htmlspecialchars($edit_data['IDSzkody']); ?>">
                        <div class="form-grid">
                             <div class="form-group">
                                <label for="id_polisy_edit_damage_form">Polisa (z którą szkoda jest powiązana):</label>
                                <select name="id_polisy_edit" id="id_polisy_edit_damage_form" required>
                                    <option value="">-- Wybierz polisę --</option>
                                    <?php foreach ($policies_list as $policy_opt): // Użyj pełnej listy polis, bo szkoda może dotyczyć nieaktywnej ?>
                                    <option value="<?php echo $policy_opt['IDPolisy']; ?>" <?php echo ($edit_data['IDPolisy'] == $policy_opt['IDPolisy']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($policy_opt['NrPolisy'] . ' - ' . ($policy_opt['KlientImieNazwisko'] ?? 'Brak klienta') . ' (' . ($policy_opt['PojazdMarka'] ?? 'Brak') . ' ' . ($policy_opt['PojazngModel'] ?? '') . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="data_zdarzenia_edit_damage_form">Data Zdarzenia:</label><input type="date" name="data_zdarzenia_edit" id="data_zdarzenia_edit_damage_form" value="<?php echo htmlspecialchars($edit_data['DataZdarzenia']); ?>" required></div>
                            <div class="form-group"><label for="miejsce_edit_damage_form">Miejsce Zdarzenia:</label><input type="text" name="miejsce_edit" id="miejsce_edit_damage_form" value="<?php echo htmlspecialchars($edit_data['MiejsceZdarzenia']); ?>" required></div>
                            <div class="form-group"><label for="przyczyna_edit_damage_form">Przyczyna Szkody:</label><input type="text" name="przyczyna_edit" id="przyczyna_edit_damage_form" value="<?php echo htmlspecialchars($edit_data['PrzyczynaSzkody']); ?>" required></div>
                            <div class="form-group"><label for="szacowana_wartosc_edit_damage_form">Szacowana Wartość (zł):</label><input type="number" name="szacowana_wartosc_edit" id="szacowana_wartosc_edit_damage_form" value="<?php echo htmlspecialchars($edit_data['SzacowanaWartosc']); ?>" step="0.01" min="0" required></div>
                            <div class="form-group"><label for="odszkodowanie_edit_damage_form">Wypłacone Odszkodowanie (zł):</label><input type="number" name="odszkodowanie_edit" id="odszkodowanie_edit_damage_form" value="<?php echo htmlspecialchars($edit_data['WartoscOdszkodowania'] ?? ''); ?>" step="0.01" min="0"></div>
                            <div class="form-group">
                                <label for="status_edit_damage_form">Status Szkody:</label>
                                <select name="status_edit" id="status_edit_damage_form" required>
                                    <option value="zgłoszona" <?php if($edit_data['Status'] == 'zgłoszona') echo 'selected'; ?>>Zgłoszona</option>
                                    <option value="w trakcie likwidacji" <?php if($edit_data['Status'] == 'w trakcie likwidacji') echo 'selected'; ?>>W trakcie likwidacji</option>
                                    <option value="zlikwidowana" <?php if($edit_data['Status'] == 'zlikwidowana') echo 'selected'; ?>>Zlikwidowana</option>
                                    <option value="odmowa" <?php if($edit_data['Status'] == 'odmowa') echo 'selected'; ?>>Odmowa</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label for="opis_edit_damage_form">Opis Zdarzenia:</label>
                            <textarea name="opis_edit" id="opis_edit_damage_form" required><?php echo htmlspecialchars($edit_data['OpisZdarzenia']); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Zapisz Zmiany</button>
                            <a href="admin_panel.php#damages" class="btn btn-secondary"><i class="fas fa-times"></i>Anuluj</a>
                        </div>
                    </form>
                <?php elseif (!isset($_GET['edit'])) : // Pokaż formularz dodawania tylko jeśli nie edytujemy innego typu ?>
                    <h3><i class="fas fa-plus-circle"></i>Zgłoś Nową Szkodę</h3>
                     <form method="POST" action="admin_panel.php" class="form-section">
                        <input type="hidden" name="add_damage" value="1">
                        <div class="form-grid">
                             <div class="form-group">
                                <label for="id_polisy_damage_add_form">Polisa (aktywna):</label>
                                <select name="id_polisy_damage" id="id_polisy_damage_add_form" required>
                                    <option value="">-- Wybierz polisę --</option>
                                    <?php foreach ($activePoliciesForDamageForm as $policy_for_damage): ?>
                                    <option value="<?php echo $policy_for_damage['IDPolisy']; ?>">
                                        <?php echo htmlspecialchars($policy_for_damage['NrPolisy'] . ' - ' . $policy_for_damage['ImieNazwisko'] . ' (' . $policy_for_damage['Marka'] . ' ' . $policy_for_damage['Model'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label for="data_zdarzenia_damage_add_form">Data Zdarzenia:</label><input type="date" name="data_zdarzenia_damage" id="data_zdarzenia_damage_add_form" value="<?php echo date('Y-m-d'); ?>" required></div>
                            <div class="form-group"><label for="miejsce_damage_add_form">Miejsce Zdarzenia:</label><input type="text" name="miejsce_damage" id="miejsce_damage_add_form" required></div>
                            <div class="form-group"><label for="przyczyna_damage_add_form">Przyczyna Szkody:</label><input type="text" name="przyczyna_damage" id="przyczyna_damage_add_form" required></div>
                            <div class="form-group"><label for="szacowana_wartosc_damage_add_form">Szacowana Wartość (zł):</label><input type="number" name="szacowana_wartosc_damage" id="szacowana_wartosc_damage_add_form" step="0.01" min="0" required></div>
                            <div class="form-group"><label for="odszkodowanie_damage_add_form">Wypłacone Odszkodowanie (zł):</label><input type="number" name="odszkodowanie_damage" id="odszkodowanie_damage_add_form" step="0.01" min="0"></div>
                            <div class="form-group">
                                <label for="status_damage_add_form">Status Szkody:</label>
                                <select name="status_damage" id="status_damage_add_form" required>
                                    <option value="zgłoszona" selected>Zgłoszona</option>
                                    <option value="w trakcie likwidacji">W trakcie likwidacji</option>
                                    <option value="zlikwidowana">Zlikwidowana</option>
                                    <option value="odmowa">Odmowa</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;"><label for="opis_damage_add_form">Opis Zdarzenia:</label><textarea name="opis_damage" id="opis_damage_add_form" required></textarea></div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus-circle"></i>Zgłoś Szkodę</button>
                        </div>
                    </form>
                <?php endif; ?>

                <h3 style="margin-top:2.5rem;"><i class="fas fa-list-ul"></i>Zgłoszone Szkody</h3>
                <div class="table-wrapper">
                <table>
                    <thead><tr><th>ID</th><th>Klient</th><th>Pojazd (Nr rej.)</th><th>Nr Polisy</th><th>Data Zdarzenia</th><th>Status</th><th>Akcje</th></tr></thead>
                    <tbody>
                        <?php foreach($damages_list as $damage_item_list): ?>
                        <tr>
                            <td><?php echo $damage_item_list['IDSzkody']; ?></td>
                            <td><?php echo htmlspecialchars($damage_item_list['KlientSzkody'] ?? 'Brak'); ?></td>
                            <td><?php echo htmlspecialchars(($damage_item_list['PojazdSzkodyMarka'] ?? '') . " " . ($damage_item_list['PojazdSzkodyModel'] ?? '') . ' (' . ($damage_item_list['PojazdnrRejSzkody'] ?? 'Brak') . ')' ); ?></td>
                            <td><?php echo htmlspecialchars($damage_item_list['NrPolisy'] ?? 'Brak'); ?></td>
                            <td><?php echo formatDate($damage_item_list['DataZdarzenia']); ?></td>
                            <td>
                                <?php
                                $status_d_item = $damage_item_list['Status'] ?? 'nieznany';
                                $status_norm_d_item = str_replace([' ', 'ł', 'ę', 'ą', 'ś', 'ć', 'ń', 'ó', 'ż', 'ź'], ['-', 'l', 'e', 'a', 's', 'c', 'n', 'o', 'z', 'z'], mb_strtolower($status_d_item));
                                $status_class_d_item = 'status-badge status-' . $status_norm_d_item;
                                ?>
                                <span class="<?php echo htmlspecialchars($status_class_d_item); ?>"><?php echo htmlspecialchars(ucfirst($status_d_item)); ?></span>
                            </td>
                            <td>
                                <a href="admin_panel.php?edit=damage&id=<?php echo $damage_item_list['IDSzkody']; ?>#damages" class="btn btn-edit"><i class="fas fa-edit"></i>Edytuj</a>
                                <a href="admin_panel.php?delete=damage&id=<?php echo $damage_item_list['IDSzkody']; ?>" class="btn btn-danger" onclick="return confirm('Na pewno usunąć szkodę ID <?php echo $damage_item_list['IDSzkody']; ?>?')"><i class="fas fa-trash"></i>Usuń</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($damages_list)): ?><tr><td colspan="7" style="text-align:center;">Brak zgłoszonych szkód.</td></tr><?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div> <!-- koniec #damages -->

            <!-- Zakładka Kalkulator -->
            <div id="calculator" class="tab-pane card">
                 <div class="card-header"><i class="fas fa-calculator"></i>Kalkulator Składek</div>
                <div class="calculator-form form-section">
                     <form id="calculatorForm"> <!-- Usunięto action i method, obsługa przez JS -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="calc_klient">Klient:</label>
                                <select id="calc_klient" name="calc_klient_select" required onchange="updateVehiclesAndClientDataForCalc()">
                                    <option value="">-- Wybierz klienta --</option>
                                    <?php foreach ($clients as $client_calc_item): ?>
                                        <option value="<?php echo $client_calc_item['IDKlienta']; ?>"
                                                data-lata-bez-szkody="<?php echo $client_calc_item['LataBezSzkody']; ?>"
                                                data-imie-nazwisko="<?php echo htmlspecialchars($client_calc_item['ImieNazwisko']); ?>">
                                            <?php echo htmlspecialchars($client_calc_item['ImieNazwisko']); ?> (PESEL: <?php echo htmlspecialchars($client_calc_item['PESEL'] ?? '-');?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="calc_pojazd">Pojazd:</label>
                                <select id="calc_pojazd" name="calc_pojazd_select" required disabled onchange="updateVehicleDataForCalc()">
                                    <option value="">-- Najpierw wybierz klienta --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="calc_typ">Typ Ubezpieczenia:</label>
                                <select id="calc_typ" name="calc_typ_select" required>
                                    <option value="OC" selected>OC</option><option value="AC">AC</option><option value="OCAC">OC+AC</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="calc_suma">Suma Ubezpieczenia (zł):</label>
                                <input type="number" id="calc_suma" name="calc_suma_input" value="50000" step="1000" min="0" required>
                            </div>
                        </div>
                        <div class="form-actions" style="text-align:left;">
                            <button type="button" class="btn btn-success" onclick="calculatePremium()"><i class="fas fa-cogs"></i>Oblicz Składkę</button>
                        </div>
                    </form>
                    <div id="calculatorResult" style="display: none; margin-top: 1.5rem;">
                        <div class="result-box" style="padding: 1.5rem; background: #eff6ff; border-left: 5px solid var(--primary-color); border-radius:var(--border-radius-md);">
                            <h3><i class="fas fa-receipt"></i>Wyliczona Składka Roczna:</h3>
                            <div class="amount" id="resultAmount" style="font-size: 2.2rem;">0,00 zł</div>
                            <div style="margin-top: 1rem; text-align: left; display: inline-block; font-size:0.9rem;">
                                <p><strong>Klient:</strong> <span id="calcResultClientName">-</span></p>
                                <p><strong>Pojazd:</strong> <span id="calcResultVehicleInfo">-</span></p>
                                <p><strong>Typ ubezpieczenia:</strong> <span id="calcResultInsuranceType">-</span></p><hr style="margin: 0.75rem 0; border-top: 1px dashed #cdd7e1;">
                                <p><strong>Składka bazowa:</strong> <span id="resultBase">0,00 zł</span></p>
                                <p style="font-size:0.85em; color:var(--text-light);"><strong>Szczegóły modyfikatorów:</strong> <span id="resultRiskFactorsDetails"></span></p>
                                <p><strong>Modyfikator łączny:</strong> <span id="resultRiskFactors">1.00</span></p>
                                <p><strong>Zniżka za bezszkodowość (<span id="resultDiscountYears">0</span> lat):</strong> <span id="resultDiscountPercent">0%</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- koniec #calculator -->

    <script>


        const vehiclesDataForCalc = <?php echo json_encode($vehicles); ?>;
        const clientsDataForCalc = <?php echo json_encode($clients); ?>;

        function showTab(tabId) {
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(button => button.classList.remove('active'));

            const selectedPane = document.getElementById(tabId);
            const selectedButton = document.querySelector(`.tab-button[data-tab="${tabId}"]`);

            if (selectedPane) selectedPane.classList.add('active');
            if (selectedButton) selectedButton.classList.add('active');

            if (history.pushState) {
                let newUrl = window.location.pathname;
                const currentHash = '#' + tabId;
                let searchParams = new URLSearchParams(window.location.search);
                let retainEditParams = false;

                if ( (tabId === 'clients' && searchParams.get('edit') === 'client' && searchParams.has('id')) ||
                     (tabId === 'vehicles' && searchParams.get('edit') === 'vehicle' && searchParams.has('id')) ||
                     (tabId === 'policies' && searchParams.get('edit') === 'policy' && searchParams.has('id')) ||
                     (tabId === 'damages' && searchParams.has('edit_damage')) ) {
                   retainEditParams = true;
                }

                if (!retainEditParams) {
                    searchParams.delete('edit');
                    searchParams.delete('id');
                    searchParams.delete('edit_damage');
                }

                const queryString = searchParams.toString();
                if (queryString) newUrl += '?' + queryString;
                newUrl += currentHash;

                history.replaceState({path: newUrl}, '', newUrl); // Używamy replaceState, żeby nie tworzyć nowej historii przy każdej zmianie zakładki
            } else {
                window.location.hash = '#' + tabId;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    showTab(this.dataset.tab);
                });
            });

            let activeTab = 'dashboard';
            const hash = window.location.hash.substring(1);
            const urlParamsOnLoad = new URLSearchParams(window.location.search);

            if (urlParamsOnLoad.has('edit') && urlParamsOnLoad.has('id')) {
                const editType = urlParamsOnLoad.get('edit');
                if (editType === 'client') activeTab = 'clients';
                else if (editType === 'vehicle') activeTab = 'vehicles';
                else if (editType === 'policy') activeTab = 'policies';
            } else if (urlParamsOnLoad.has('edit_damage')) {
                activeTab = 'damages';
            } else if (hash && document.getElementById(hash)) {
                activeTab = hash;
            }
            showTab(activeTab);

            if (document.getElementById('calc_klient')) {
                updateVehiclesAndClientDataForCalc();
                updateVehicleDataForCalc();
            }

            const policyClientAddSelect = document.getElementById('id_klienta_policy_add');
            if (policyClientAddSelect) {
                 loadClientVehicles(policyClientAddSelect.id, 'id_pojazdu_policy_add');
            }
            const policyClientEditSelect = document.getElementById('id_klienta_policy_edit');
            if (policyClientEditSelect && policyClientEditSelect.value) {
                 loadClientVehicles(policyClientEditSelect.id, 'id_pojazdu_policy_edit', '<?php echo $edit_mode === "policy" && isset($edit_data["IDPojazdu"]) ? $edit_data["IDPojazdu"] : ""; ?>');
            }
        });

        function loadClientVehicles(clientSelectId, vehicleSelectId, preSelectedVehicleId = null) {
            const clientSelect = document.getElementById(clientSelectId);
            const vehicleSelect = document.getElementById(vehicleSelectId);
            if (!clientSelect || !vehicleSelect) return;

            const clientId = clientSelect.value;
            const currentVehicleValue = vehicleSelect.value; // Zachowaj aktualnie wybraną wartość, jeśli jest to edycja

            vehicleSelect.innerHTML = '<option value="">-- Ładowanie... --</option>';
            vehicleSelect.disabled = true;

            if (!clientId) {
                vehicleSelect.innerHTML = '<option value="">-- Wybierz klienta --</option>';
                return;
            }

            const clientVehicles = vehiclesDataForCalc.filter(v => v.IDKlienta == clientId);

            if (clientVehicles.length === 0) {
                vehicleSelect.innerHTML = '<option value="">-- Klient nie ma pojazdów --</option>';
            } else {
                vehicleSelect.innerHTML = '<option value="">-- Wybierz pojazd --</option>';
                clientVehicles.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.IDPojazdu;
                    option.textContent = `${vehicle.Marka} ${vehicle.Model} (${vehicle.NrRejestracyjny})`;
                    // Ustaw selected, jeśli preSelectedVehicleId pasuje LUB jeśli jest to aktualnie wybrany pojazd przed przeładowaniem listy
                    if ((preSelectedVehicleId && vehicle.IDPojazdu == preSelectedVehicleId) || vehicle.IDPojazdu == currentVehicleValue) {
                        option.selected = true;
                    }
                    vehicleSelect.appendChild(option);
                });
                vehicleSelect.disabled = false;
            }
        }

        function updateVehiclesAndClientDataForCalc() { /* Ta sama funkcja co w poprzedniej odpowiedzi */
            const clientSelect = document.getElementById('calc_klient');
            if (!clientSelect) return;
            const clientId = clientSelect.value;
            const vehicleSelect = document.getElementById('calc_pojazd');

            vehicleSelect.innerHTML = '<option value="">Wybierz pojazd</option>';
            vehicleSelect.disabled = !clientId;

            if (clientId) {
                const clientVehicles = vehiclesDataForCalc.filter(v => v.IDKlienta == clientId);
                if (clientVehicles.length === 0) {
                     vehicleSelect.innerHTML = '<option value="">Ten klient nie ma pojazdów</option>';
                     vehicleSelect.disabled = true;
                } else {
                    clientVehicles.forEach(vehicle => {
                        const option = document.createElement('option');
                        option.value = vehicle.IDPojazdu;
                        option.textContent = `${vehicle.Marka} ${vehicle.Model} (${vehicle.NrRejestracyjny ?? '-'})`;
                        option.dataset.rok = vehicle.RokProdukcji;
                        option.dataset.moc = vehicle.MocSilnika;
                        option.dataset.wartosc = vehicle.WartoscRynkowa;
                        vehicleSelect.appendChild(option);
                    });
                    if(vehicleSelect.options.length > 1) vehicleSelect.selectedIndex = 1; // Auto-wybierz pierwszy
                }
            }
            updateVehicleDataForCalc(); // Aktualizuj sumę
        }

        function updateVehicleDataForCalc() { /* Ta sama funkcja co w poprzedniej odpowiedzi */
            const vehicleSelect = document.getElementById('calc_pojazd');
            if (!vehicleSelect) return;
            const typeSelect = document.getElementById('calc_typ');
            const sumInput = document.getElementById('calc_suma');
            const selectedVehicleOption = vehicleSelect.options[vehicleSelect.selectedIndex];

            if (typeSelect.value === 'AC' || typeSelect.value === 'OCAC') {
                sumInput.disabled = false;
                if (selectedVehicleOption && selectedVehicleOption.dataset.wartosc) {
                    sumInput.value = selectedVehicleOption.dataset.wartosc;
                } else {
                     sumInput.value = 50000; // Domyślna, gdy brak danych pojazdu
                }
            } else { // OC
                sumInput.value = 1500000; // Typowa wysoka suma dla OC
                sumInput.disabled = true;
            }
        }
        if (document.getElementById('calc_typ')) {
            document.getElementById('calc_typ').addEventListener('change', updateVehicleDataForCalc);
        }


        function calculatePremium() { /* Ta sama funkcja co w poprzedniej odpowiedzi - zaktualizowana nieco */
            const clientSelect = document.getElementById('calc_klient');
            const vehicleSelect = document.getElementById('calc_pojazd');
            const typeSelect = document.getElementById('calc_typ');
            const sumInput = document.getElementById('calc_suma');

            if (!clientSelect.value) { alert('Proszę wybrać klienta.'); return; }

            const selectedClientOption = clientSelect.options[clientSelect.selectedIndex];
            const selectedVehicleOption = vehicleSelect.options[vehicleSelect.selectedIndex];

            const lataBezSzkody = parseInt(selectedClientOption.dataset.lataBezSzkody) || 0;
            const klientImieNazwisko = selectedClientOption.dataset.imieNazwisko || "Nie wybrano";

            let rokProdukcji, moc, wartoscPojazdu, pojazdInfo;
            if (selectedVehicleOption && selectedVehicleOption.value !== "" && selectedVehicleOption.dataset.rok) {
                rokProdukcji = parseInt(selectedVehicleOption.dataset.rok);
                moc = parseInt(selectedVehicleOption.dataset.moc);
                wartoscPojazdu = parseFloat(selectedVehicleOption.dataset.wartosc);
                pojazdInfo = selectedVehicleOption.textContent;
            } else {
                if (vehicleSelect.disabled || (vehicleSelect.options.length > 0 && vehicleSelect.options[0].text === "Ten klient nie ma pojazdów")) {
                    rokProdukcji = new Date().getFullYear() - 7; moc = 90; wartoscPojazdu = 30000; // Domyślne wartości
                    pojazdInfo = "Pojazd domyślny (klient bez pojazdów / nie wybrano)";
                } else {
                     // Jeśli select pojazdów jest aktywny i ma opcje, ale nic nie jest wybrane (oprócz "Wybierz pojazd")
                    if (vehicleSelect.options.length > 1 && vehicleSelect.value === "") {
                        alert('Proszę wybrać pojazd.'); return;
                    }
                    // Jeśli nie ma żadnych opcji oprócz "Ten klient nie ma pojazdów", używamy domyślnych jak wyżej
                    rokProdukcji = new Date().getFullYear() - 7; moc = 90; wartoscPojazdu = 30000;
                    pojazdInfo = "Pojazd domyślny (klient bez pojazdów)";
                }
            }

            const sumaUbezpieczenia = parseFloat(sumInput.value);
            const typUbezpieczenia = typeSelect.value;

            let skladkaBazowaOC = 850;
            let skladkaBazowaAC = Math.max(450, wartoscPojazdu * 0.035);

            let skladkaBazowa;
            if (typUbezpieczenia === 'OC') skladkaBazowa = skladkaBazowaOC;
            else if (typUbezpieczenia === 'AC') skladkaBazowa = skladkaBazowaAC;
            else skladkaBazowa = skladkaBazowaOC + skladkaBazowaAC * 0.82; // Zniżka na pakiet

            const wiekPojazdu = Math.max(0, new Date().getFullYear() - rokProdukcji);
            let wspWieku = 1.0;
            if (wiekPojazdu < 2) wspWieku = 0.90;
            else if (wiekPojazdu > 14) wspWieku = 1.35;
            else if (wiekPojazdu > 8) wspWieku = 1.15;

            let wspMocy = 1.0;
            if (moc < 65) wspMocy = 0.85;
            else if (moc > 170) wspMocy = 1.30;
            else if (moc > 110) wspMocy = 1.10;

            let wspSumaUbezp = 1.0;
            if (typUbezpieczenia === 'AC' || typUbezpieczenia === 'OCAC') {
                if (sumaUbezpieczenia > wartoscPojazdu * 1.25) wspSumaUbezp = 1.10;
                else if (sumaUbezpieczenia < wartoscPojazdu * 0.75) wspSumaUbezp = 0.94;
            }

            const wspolczynnikiTotal = wspWieku * wspMocy * wspSumaUbezp;
            const znizkaProcent = Math.min(lataBezSzkody * 6, 60); // Max 60%

            const skladkaPoWsp = skladkaBazowa * wspolczynnikiTotal;
            const skladkaKoncowa = Math.max(50, skladkaPoWsp * (1 - znizkaProcent / 100)); // Minimalna składka 50 zł

            document.getElementById('calcResultClientName').textContent = klientImieNazwisko;
            document.getElementById('calcResultVehicleInfo').textContent = pojazdInfo;
            document.getElementById('calcResultInsuranceType').textContent = typUbezpieczenia + ((typUbezpieczenia === 'AC' || typUbezpieczenia === 'OCAC') ? ` (Suma Ubezp.: ${formatMoneyLocal(sumaUbezpieczenia)})` : '');
            document.getElementById('resultAmount').textContent = formatMoneyLocal(skladkaKoncowa);
            document.getElementById('resultBase').textContent = formatMoneyLocal(skladkaBazowa);
            document.getElementById('resultRiskFactorsDetails').textContent =
                `Wiek poj. (${wiekPojazdu} lat): ${wspWieku.toFixed(2)}, ` +
                `Moc silnika (${moc}KM): ${wspMocy.toFixed(2)}, ` +
                `Stosunek Sumy Ubezp. do Wartości (dla AC/OCAC): ${wspSumaUbezp.toFixed(2)}`;
            document.getElementById('resultRiskFactors').textContent = wspolczynnikiTotal.toFixed(2);
            document.getElementById('resultDiscountYears').textContent = lataBezSzkody;
            document.getElementById('resultDiscountPercent').textContent = znizkaProcent.toFixed(1) + '%';
            document.getElementById('calculatorResult').style.display = 'block';
        }

        function formatMoneyLocal(amount) {
            if (isNaN(parseFloat(amount)) || !isFinite(amount)) return '0,00 zł';
            return parseFloat(amount).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' zł';
        }
    </script>
</body>
</html>
