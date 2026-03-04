<?php
session_start();
if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}
include '../db.php';

$vypis_error = '';
$vypis_ok = '';

$vysilace = $db->query("
    SELECT id, nazev_vysilace, predchozi_vysilac
    FROM vysilace
    ORDER BY id
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id  = (int)($_POST['id_vysilace'] ?? 0);
    $ptp = isset($_POST['ptp']);

    if ($id <= 0) {
        $vypis_error = "Vyber vysílač.";
    }
    else {

        $stmt = $db->prepare("
            SELECT *
            FROM vysilace
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $vysilac = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vysilac) {
            $vypis_error = "Vysílač neexistuje.";
        }
        else {

            if ($ptp) {
                $stmt = $db->query("
                    SELECT COUNT(*) 
                    FROM vysilac_jednotky 
                    WHERE ptp = 1
                ");
                $existuje_ptp = (int)$stmt->fetchColumn();

                $stmt = $db->prepare("
                    SELECT COUNT(*)
                    FROM vysilac_jednotky
                    WHERE id_vysilace = ?
                      AND ptp = 1 
                ");
                $stmt->execute([$id]);

                if ($stmt->fetchColumn() > 0) {
                    $vypis_error = "Tento vysílač už má PTP.";
                }
                else {

                    $stary_id = $vysilac['predchozi_vysilac'];

                    if ($existuje_ptp == 0) {
                        $stary_id = $id;
                    }
                    elseif (!$stary_id) {
                        $vypis_error = "Chybí předchozí vysílač.";
                    }
                    else {

                        $stmt = $db->prepare("
                            SELECT ip_adresa
                            FROM vysilac_jednotky
                            WHERE id_vysilace = ?
                              AND ptp = 1
                        ");
                        $stmt->execute([$stary_id]);
                        $stara = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$stara) {
                            $vypis_error = "Starý vysílač nemá jednotku.";
                        }
                        else {

                            $casti = explode('.', $stara['ip_adresa']);

                            if (count($casti) !== 4 || $casti[3] != 249) {
                                $vypis_error = "Nelze vytvořit PTP – předchozí vysílač nemá správnou IP.";
                            }
                        }
                    }
                    if (!$vypis_error) {

                        $treti = $id + 200;

                        $ip_odchod  = "10.0.$treti.250";
                        $ip_prichod = "10.0.$treti.249";

                        $nazev_odchod  = $vysilac['nazev_vysilace']."_PTP_odchod";
                        $nazev_prichod = $vysilac['nazev_vysilace']."_PTP_prichod";

                        $stmt = $db->prepare("
                            INSERT INTO vysilac_jednotky
                            (nazev, id_vysilace, ip_adresa, ptp)
                            VALUES (?, ?, ?, 1)
                        ");

                        $stmt->execute([
                            $nazev_odchod,
                            $stary_id,
                            $ip_odchod
                        ]);

                        $stmt->execute([
                            $nazev_prichod,
                            $id,
                            $ip_prichod
                        ]);

                        $vypis_ok = "PTP spoj byl vytvořen.";
                    }
                }
            }
            else {

                $stmt = $db->prepare("
                    SELECT COUNT(*)
                    FROM vysilac_jednotky
                    WHERE id_vysilace = ?
                      AND ptp = 0
                ");
                $stmt->execute([$id]);

                $pocet = (int)$stmt->fetchColumn();

                if ($pocet >= 54) {
                    $vypis_error = "Dosažen maximální počet jednotek (54).";
                }
                else {

                    $poradi = $pocet + 1;

                    $nazev = $vysilac['nazev_vysilace']."_VJ_$poradi";

                    $treti  = 100 + $id;
                    $ctvrty = $poradi;

                    $ip1 = "10.0.$treti.$ctvrty";

                    $stmt = $db->prepare("
                        INSERT INTO vysilac_jednotky
                        (nazev, id_vysilace, ip_adresa, ptp)
                        VALUES (?, ?, ?, 0)
                    ");

                    $stmt->execute([
                        $nazev,
                        $id,
                        $ip1
                    ]);

                    $vypis_ok = "Vytvořeno: $nazev";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přidat jednotku</title>
    <link rel="stylesheet" href="pridat.css">
</head>
<body>

<h1>Přidat jednotku</h1>

<?php if ($vypis_error): ?>
    <p style="color:red;">
        <?= htmlspecialchars($vypis_error) ?>
    </p>
<?php endif; ?>

<?php if ($vypis_ok): ?>
    <p style="color:green;">
        <?= htmlspecialchars($vypis_ok) ?>           
    </p>
<?php endif; ?>

<form method="post">
    <label>Vysílač</label><br>

    <select name="id_vysilace" required>
        <option value="">--</option>
        <?php foreach ($vysilace as $v): ?>
            <option value="<?= $v['id'] ?>">
                <?= $v['id'] ?> – <?= htmlspecialchars($v['nazev_vysilace']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>
        <input type="checkbox" name="ptp">
        PTP spoj
    </label><br><br>
    <button type="submit">Vytvořit</button>
</form><br>
<a href="../index.php">◄ Zpět</a>
</body>
</html>
