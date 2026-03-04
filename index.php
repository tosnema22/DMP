<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: prihlaseni.php");
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nástroje pro firmu</title>
    <link rel="stylesheet" href="css/vzhled.css">
</head>
<body>

<div class="header">
    <h1>Interní správa sítě GNS3</h1>

    <div class="uzivatel_box">
        <span>
            Přihlášen jako: 
            <?= htmlspecialchars($_SESSION['uzivatel_jmeno'] . ' ' . $_SESSION['uzivatel_prijmeni']) ?>
        </span>
        <form action="odhlasit/odhlasit.php" method="post">
            <button type="submit">Odhlásit</button>
        </form>
    </div>
</div>

<ul>
    <li><strong class="nadpisy">Správa infrastruktury</strong></li>
    <li><a href="vysilac/vysilac.php">Přidat vysílač</a></li>
    <li><a href="vysilac_jednotka/pridat.php">Přidat jednotku na vysílač</a></li><br><br>
    <li><a href="klient/klient.php">Přidat klienta</a></li>
    <li><a href="klientska_jednotka/klientska_jednotka.php">Přidat klientskou jednotku</a></li><br><br>

    <li><strong class="nadpisy">Technické nástroje</strong></li>
    <li><a href="seznam/seznam.php">Seznam</a></li>
    <li><a href="omezit/omezit.php">Omezit rychlost</a></li>
    <li><a href="skript/skript.php">Zjisti jakou IP kam nastavit</a></li>
    <li><a href="sit/sit.php">Zobraz si síť</a></li><br><br>

    <?php if ($_SESSION['role_id'] == 1): ?>
        <li><strong class="nadpisy">Uživatelská správa</strong></li>
        <li><a href="pridat_uzivatele/pridat.php">Přidat uživatele</a></li>
        <li><a href="vpn/vpn.php">Přidej uživatele na VPN</a></li><br><br>
    <?php endif; ?>
</ul>


</body>
</html>
