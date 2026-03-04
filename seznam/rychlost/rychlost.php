<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}

include '../../db.php';

$id = isset($_GET['jednotka']) ? (int)$_GET['jednotka'] : 0;
$vysilac = isset($_GET['vysilac']) ? (int)$_GET['vysilac'] : 0;
$p_jednotka = isset($_GET['p_jednotka']) ? (int)$_GET['p_jednotka'] : 0;

$druha_jednotka = null;
$skript = "";

if ($id > 0) {

    $stmt = $db->prepare("
        SELECT Id, nazev, rychlost
        FROM klientske_jednotky
        WHERE Id = ?
    ");

    $stmt->execute([$id]);

    $jednotka = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($jednotka && isset($_POST['tarif'])) {

    $rychlost = $_POST['tarif'];

    $stmt = $db->prepare("
        UPDATE klientske_jednotky
        SET rychlost = ?
        WHERE Id = ?
    ");

    $stmt->execute([$rychlost, $id]);

    list($down, $up) = explode('/', $rychlost);

    $down_k = $down * 1000;
    $up_k   = $up * 1000;

    $nazev = $jednotka['nazev'];


    $max_limit = $down . "K/" . $up . "K";
    $skript = "/queue simple set [find name=\"{$nazev}_omezeni\"] max-limit={$max_limit}";

    $stmt = $db->prepare("
        SELECT Id, nazev, rychlost
        FROM klientske_jednotky
        WHERE Id = ?
    ");

    $stmt->execute([$id]);

    $jednotka = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Změna rychlosti</title>
    <link rel="stylesheet" href="rychlost.css">
</head>
<body>
<div>
    <h1>Změna rychlosti</h1>

    <?php if ($jednotka): ?>
        <p>
            <strong>Jednotka:</strong>
            <?= htmlspecialchars($jednotka['nazev']) ?>
        </p>
        <p>
            <strong>Aktuální rychlost:</strong>
            <?= htmlspecialchars($jednotka['rychlost'] ?: '—') ?>
        </p>


        <form method="post">
            <label>Vyber rychlost</label><br>
            <select name="tarif" required>
                <option value="">-- vyber --</option>
                <option value="50/20">50 / 20</option>
                <option value="100/50">100 / 50</option>
                <option value="200/100">200 / 100</option>
                <option value="500/250">500 / 250</option>
            </select><br><br>

            <button type="submit">
                Generovat skript
            </button>
        </form>

        <p>
            <a href="http://10.0.2.1:85" target="_blank">
                Přihlásit do jednotky
            </a>
        </p>
        <?php if ($skript): ?>
            <h2>MikroTik skript</h2>
            <textarea readonly><?= $skript ?></textarea>
            <p class="napoveda">
                Zkopíruj a vlož do terminálu routeru.
            </p>

        <?php endif; ?>
    <?php else: ?>

        <p>Jednotka nenalezena.</p>
    <?php endif; ?><br>

    <a href="../seznam.php?vysilac=<?= $vysilac ?>&jednotka=<?= $p_jednotka ?>">
        ◄ Zpět na seznam
    </a>


</div>
</body>
</html>