<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}

include '../db.php';

$klienti = $db->query("
    SELECT id, jmeno, prijmeni
    FROM klienti
    WHERE klientska_jednotka IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

$vysilaci_jednotky = $db->query("
    SELECT id, nazev, ip_adresa
    FROM vysilac_jednotky
    WHERE ptp = 0
    ORDER BY nazev
")->fetchAll(PDO::FETCH_ASSOC);

$pouzite_ip = $db->query("
    SELECT ip_adresa
    FROM klientske_jednotky
")->fetchAll(PDO::FETCH_COLUMN);

$o1 = 10;
$o2 = 0;
$o3 = 50;
$o4 = 251;

$volne_ip = null;

while ($o3 >= 3) {

    $ip = "$o1.$o2.$o3.$o4";

    if (!in_array($ip, $pouzite_ip)) {
        $volne_ip = $ip;
        break;
    }

    $o4--;

    if ($o4 < 168) {
        $o3--;
        $o4 = 251;
    }
}

$chyba = null;
$ulozena_ip = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$volne_ip) {
        $chyba = "Došel rozsah IP adres (limit 10.0.3.168).";
    } else {

        $klient_id   = (int)$_POST['klient'];
        $jednotka_id = (int)$_POST['jednotka'];
        $rychlost    = $_POST['rychlost'];

        $stmt = $db->prepare("
            SELECT prijmeni
            FROM klienti
            WHERE id = ?
        ");
        $stmt->execute([$klient_id]);
        $prijmeni = $stmt->fetchColumn();

        if (!$prijmeni) {
            $chyba = "Klient neexistuje.";
        } else {

            $bez_diakritiky = iconv('UTF-8', 'ASCII//TRANSLIT', $prijmeni);
            $bez_diakritiky = preg_replace('/[^A-Za-z0-9]/', '', $bez_diakritiky);
            $nazev = $bez_diakritiky . '_KLJ';

            $stmt = $db->prepare("
                INSERT INTO klientske_jednotky
                (nazev, kam_je_pripojena, rychlost, ip_adresa)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $nazev,
                $jednotka_id,
                $rychlost,
                $volne_ip
            ]);

            $klientska_jednotka_id = $db->lastInsertId();

            $stmt = $db->prepare("
                UPDATE klienti
                SET 
                    klientsky_vysilac = ?,
                    klientska_jednotka = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $jednotka_id,
                $klientska_jednotka_id,
                $klient_id
            ]);

            $ulozena_ip = $volne_ip;

            header("Location: klientska_jednotka.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přidat klientskou jednotku</title>
    <link rel="stylesheet" href="vzhled.css">
</head>
<body>

<h1>Přidat klientskou jednotku</h1>

<?php if ($chyba): ?>
    <p style="color:red"><?= htmlspecialchars($chyba) ?></p>
<?php endif; ?>

<form method="post">

    <label>Klient</label>
    <select name="klient" required>
        <?php foreach ($klienti as $k): ?>
            <option value="<?= (int)$k['id'] ?>">
                <?= htmlspecialchars($k['jmeno'] . " " . $k['prijmeni']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Vysílací jednotka</label>
    <select name="jednotka" required>
        <?php foreach ($vysilaci_jednotky as $v): ?>
            <option value="<?= (int)$v['id'] ?>">
                <?= htmlspecialchars($v['nazev']) ?>
                (<?= htmlspecialchars($v['ip_adresa']) ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label>Rychlost</label>
    <select name="rychlost">
        <option>50/20</option>
        <option>100/50</option>
        <option>200/100</option>
        <option>500/250</option>
    </select>

    <button type="submit">Přidat klientskou jednotku</button>
</form>

<br>
<a href="../index.php">◄ Zpět</a>

</body>
</html>
