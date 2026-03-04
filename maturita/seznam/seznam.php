<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}

include '../db.php';

$je_admin = ($_SESSION['role_id'] == 1);

$vysilace = $db->query("
    SELECT Id, nazev_vysilace
    FROM vysilace
")->fetchAll(PDO::FETCH_ASSOC);

$hlavni_vysilac_id = 1;

if (isset($_GET['vysilac'])) {

    $vid = (int) $_GET['vysilac'];

    $stmt = $db->prepare("
        SELECT *
        FROM vysilace
        WHERE Id = ?
    ");
    $stmt->execute([$vid]);
    $vybrany_vysilac = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vybrany_vysilac && $vybrany_vysilac['predchozi_vysilac']) {

        $stmt = $db->prepare("
            SELECT Id, nazev_vysilace
            FROM vysilace
            WHERE Id = ?
        ");
        $stmt->execute([$vybrany_vysilac['predchozi_vysilac']]);
        $predchozi_vysilac = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $stmt = $db->prepare("
        SELECT id, nazev, ip_adresa
        FROM vysilac_jednotky
        WHERE id_vysilace = ? AND ptp = 0
    ");
    $stmt->execute([$vid]);
    $vysilaci_jednotky = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($vid == $hlavni_vysilac_id) {

        array_unshift($vysilaci_jednotky, [
            'id' => 0,
            'nazev' => 'Firma',
            'ip_adresa' => '10.0.1.2'
        ]);

        if (!isset($_GET['jednotka'])) {
            $_GET['jednotka'] = 0;
        }
    }
}

if (isset($_GET['jednotka'])) {

    $jid = (int) $_GET['jednotka'];

    if ($jid === 0) {

        $klienti = [[
            'Id' => 0,
            'jmeno' => 'Firma',
            'prijmeni' => '',
            'adresa' => 'Firma 123',
            'datum_narozeni' => '2026-03-06',
            'rychlost' => 'Neomezeně',
            'ip_adresa' => '10.0.1.2',
            'jid' => 0
        ]];

        $vybrana_jednotka = 0;

    } else {

        $stmt = $db->prepare("
            SELECT
                klienti.Id,
                klienti.jmeno,
                klienti.prijmeni,
                klienti.adresa,
                klienti.datum_narozeni,
                klientske_jednotky.rychlost,
                klientske_jednotky.ip_adresa,
                klientske_jednotky.Id AS jid
            FROM klientske_jednotky
            JOIN klienti
                ON klienti.klientska_jednotka = klientske_jednotky.Id
            WHERE klientske_jednotky.kam_je_pripojena = ?
        ");
        $stmt->execute([$jid]);
        $klienti = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $vybrana_jednotka = $jid;
    }
}

if ($je_admin && isset($_POST['smazat']) && (!isset($vybrana_jednotka) || $vybrana_jednotka != 0)) {

    $id_klienta = (int) $_POST['smazat'];
    $stmt = $db->prepare("
        SELECT klientska_jednotka
        FROM klienti
        WHERE Id = ?
    ");
    $stmt->execute([$id_klienta]);

    $id_jednotky = $stmt->fetchColumn();

    $stmt = $db->prepare("
        DELETE FROM klienti
        WHERE Id = ?
    ");
    $stmt->execute([$id_klienta]);

    if ($id_jednotky) {
        $stmt = $db->prepare("
            DELETE FROM klientske_jednotky
            WHERE Id = ?
        ");
        $stmt->execute([$id_jednotky]);
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Seznam podle vysílače</title>
    <link rel="stylesheet" href="seznam.css">
</head>
<body>
<h1>Seznam podle vysílače</h1>

<form method="get">
    <label>Vysílač</label>
    <select name="vysilac" onchange="this.form.submit()">
        <option value="">--</option>
        <?php foreach ($vysilace as $v): ?>
            <option value="<?= $v['Id'] ?>"
                <?= (isset($vybrany_vysilac) && $vybrany_vysilac['Id'] == $v['Id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['nazev_vysilace']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php if (isset($vybrany_vysilac)): ?><br><br>

        <label>Jednotka</label>
        <select name="jednotka" onchange="this.form.submit()">
            <option value="">--</option>
            <?php foreach ($vysilaci_jednotky as $j): ?>
                <option value="<?= $j['id'] ?>"
                    <?= (isset($vybrana_jednotka) && $vybrana_jednotka == $j['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($j['nazev']) ?> (<?= $j['ip_adresa'] ?>)
                </option>
            <?php endforeach; ?>
        </select>

    <?php endif; ?>
</form>

<?php if (isset($vybrany_vysilac)): ?>
    <div class="box">
        <h2>Vysílač</h2>
        <p><b>ID:</b> <?= $vybrany_vysilac['Id'] ?></p>
        <p><b>Název:</b> <?= htmlspecialchars($vybrany_vysilac['nazev_vysilace']) ?></p>
        <p><b>IP rozsah:</b> <?= $vybrany_vysilac['ip_rozsah'] ?? '—' ?></p>
        <p><b>Předchozí:</b>
            <?= isset($predchozi_vysilac) && $predchozi_vysilac
                ? $predchozi_vysilac['Id'] . ' – ' . htmlspecialchars($predchozi_vysilac['nazev_vysilace'])
                : 'žádný'
            ?>
        </p>
    </div>
<?php endif; ?>

<?php if (!empty($klienti)): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Jméno</th>
            <th>Příjmení</th>
            <th>Adresa</th>
            <th>Datum</th>
            <th>Rychlost</th>
            <th>IP</th>
            <?php if (!isset($vybrana_jednotka) || $vybrana_jednotka != 0): ?>
                <th>Akce</th>
            <?php endif; ?>
        </tr>

        <?php foreach ($klienti as $k): ?>
            <tr>
                <td><?= $k['Id'] ?></td>
                <td><?= htmlspecialchars($k['jmeno']) ?></td>
                <td><?= htmlspecialchars($k['prijmeni']) ?></td>
                <td><?= htmlspecialchars($k['adresa']) ?></td>
                <td><?= $k['datum_narozeni'] ?></td>
                <td><?= htmlspecialchars($k['rychlost']) ?></td>
                <td>
                    <a href="http://<?= htmlspecialchars($k['ip_adresa']) ?>:85"
                       target="_blank"
                       class="ip-link">
                        <?= htmlspecialchars($k['ip_adresa']) ?>
                    </a>
                </td>

                <?php if (!isset($vybrana_jednotka) || $vybrana_jednotka != 0): ?>
                <td>
                    <a href="rychlost/rychlost.php?jednotka=<?= $k['jid'] ?>&vysilac=<?= $_GET['vysilac'] ?? 0 ?>&p_jednotka=<?= $_GET['jednotka'] ?? 0 ?>">
                        Upravit
                    </a>

                    <?php if ($je_admin): ?>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="smazat" value="<?= $k['Id'] ?>">
                            <button type="submit">Smazat</button>
                        </form>
                    <?php endif; ?>
                </td>
                <?php endif; ?>

            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<br>
<a href="../index.php">◄ Zpět</a>

</body>
</html>