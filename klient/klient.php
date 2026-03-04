<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}

include '../db.php';

$chyba = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jmeno = trim($_POST['jmeno']);
    $prijmeni = trim($_POST['prijmeni']);
    $adresa = trim($_POST['adresa']);
    $datum = $_POST['datum_narozeni'];

    if (strlen($jmeno) < 3 || !ctype_upper($jmeno[0])) {
        $chyba = "Jméno musí mít alespoň 3 znaky a začínat velkým písmenem.";
    }
    elseif (strlen($prijmeni) < 3 || !ctype_upper($prijmeni[0])) {
        $chyba = "Příjmení musí mít alespoň 3 znaky a začínat velkým písmenem.";
    }
    elseif (!preg_match('/^[A-Za-zÁ-Žá-ž\s]+ \d+$/u', $adresa)) {
        $chyba = "Adresa musí být ve tvaru: Ulice číslo (např. Škola 123).";
    }
    else {
        $dnes = new DateTime();
        $datum_narozeni = new DateTime($datum);
        $vek = $dnes->diff($datum_narozeni)->y;

    if ($vek < 18) {
        $chyba = "Klient musí být starší 18 let.";
    }
    elseif ($vek > 100) {
        $chyba = "Klient nemůže být starší než 100 let.";
}

    }

    if ($chyba === '') {

        $stmt = $db->prepare("
            INSERT INTO klienti (jmeno, prijmeni, adresa, datum_narozeni)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$jmeno, $prijmeni, $adresa, $datum]);

        header("Location: ../index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přidat klienta</title>
    <link rel="stylesheet" href="klient.css">
</head>
<body>

<h1>Přidat klienta</h1>

<?php if ($chyba): ?>
    <p style="color:red;"><?= htmlspecialchars($chyba) ?></p>
<?php endif; ?>

<form method="post">

    <label>Jméno</label>
    <input type="text" name="jmeno"
           value="<?= htmlspecialchars($_POST['jmeno'] ?? '') ?>" required>

    <label>Příjmení</label>
    <input type="text" name="prijmeni"
           value="<?= htmlspecialchars($_POST['prijmeni'] ?? '') ?>" required>

    <label>Adresa</label>
    <input type="text" name="adresa"
           value="<?= htmlspecialchars($_POST['adresa'] ?? '') ?>" required>

    <label>Datum narození</label>
    <input type="date" name="datum_narozeni"
           value="<?= htmlspecialchars($_POST['datum_narozeni'] ?? '') ?>" required>

    <button type="submit">Uložit</button>

</form>

<br>
<a href="../index.php">◄ Zpět</a>

</body>
</html>
