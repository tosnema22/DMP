<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}

include '../db.php';

$script = "";
$ip_vypis = null;
$chyba = null;

$jednotky = $db->query("
    SELECT Id, nazev, ip_adresa
    FROM klientske_jednotky
    ORDER BY nazev
")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['generovat'])) {

    $id = (int)($_POST['jednotka'] ?? 0);

    if (!$id) {
        $chyba = "Vyber jednotku.";
        goto konec;
    }

    $stmt = $db->prepare("
        SELECT nazev, ip_adresa, rychlost
        FROM klientske_jednotky
        WHERE Id = ?
    ");
    $stmt->execute([$id]);
    $jednotka = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$jednotka) {
        $chyba = "Jednotka nenalezena.";
        goto konec;
    }

    if (!$jednotka['rychlost']) {
        $chyba = "Tato jednotka nemá nastavenou rychlost.";
        goto konec;
    }

    $nazev = $jednotka['nazev'];
    $ip = $jednotka['ip_adresa'];
    $rychlost = $jednotka['rychlost'];

    $ip_vypis = $ip;

    list($down, $up) = explode('/', $rychlost);
    $max_limit = $down . "K/" . $up . "K";

    $script = "/queue simple add name=\"{$nazev}_omezeni\" ";
    $script .= "target={$ip} max-limit={$max_limit}";
}

konec:
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Omezení rychlosti</title>
    <link rel="stylesheet" href="/maturita/maturita/omezit/vzhled.css">
</head>
<body>

<h1>Vytvoř si skript pro vytvoření omezené rychlosti</h1>

<form method="post">
    <label>Vyber jednotku</label>
    <select name="jednotka" required>
        <option value="">-- vyber --</option>
        <?php foreach ($jednotky as $j): ?>
            <option value="<?= $j['Id'] ?>">
                <?= htmlspecialchars($j['nazev']) ?> (<?= $j['ip_adresa'] ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>
    <button name="generovat">Vytvořit omezení</button>
</form>

<?php if ($chyba): ?>
    <p style="color:red;">
        <?= htmlspecialchars($chyba) ?>
    </p>
<?php endif; ?>

<?php if ($ip_vypis): ?>
    <p><strong>IP adresa:</strong> <?= htmlspecialchars($ip_vypis) ?></p>
<?php endif; ?>

<?php if ($script): ?>
    <h2>MikroTik skript</h2>
    <textarea rows="4" readonly><?= $script ?></textarea>
<?php endif; ?>
<p>
    <a href="http://10.0.2.1:85" target="_blank">
        Přihlásit do jednotky
    </a>
</p>

<br><br>
<a href="/maturita/maturita/index.php">◄ Zpět</a>

</body>
</html>
