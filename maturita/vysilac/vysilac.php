<?php
session_start();
if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni/prihlaseni.php");
    exit;
}

include '../db.php';

$vypis_error = '';
$vypis_ok = '';

$stmt = $db->query("SELECT id, nazev_vysilace FROM vysilace ORDER BY nazev_vysilace");
$vysilace = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pocet_vysilacu = count($vysilace);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nazev = $_POST['nazev_vysilace'] ?? '';
    $predchozi = $_POST['predchozi_vysilac'] ?? null;

    if ($nazev === '') {
        $vypis_error = "Vyplň název.";
    }
    elseif ($pocet_vysilacu > 0 && empty($predchozi)) {
        $vypis_error = "Vyber předchozí vysílač.";
    }
    else {

        if ($pocet_vysilacu == 0) {
            $predchozi = null;
        }

        $stmt = $db->query("SELECT MAX(id) as max_id FROM vysilace");
        $max = $stmt->fetch(PDO::FETCH_ASSOC);
        $max_id = (int)$max['max_id'];

        $dalsi_id = $max_id + 1;

        if ($dalsi_id > 54) {

            $vypis_error = "Dosáhli jste maximálního počtu vysílačů.";

        } else {

            try {

                $db->beginTransaction();

                $insert = $db->prepare("
                    INSERT INTO vysilace (nazev_vysilace, predchozi_vysilac)
                    VALUES (:nazev, :predchozi)
                ");

                $insert->execute([
                    'nazev' => $nazev,
                    'predchozi' => $predchozi
                ]);

                $nove_id = (int)$db->lastInsertId();
                $treti_oktet = 100 + $nove_id;
                $ctvrty_oktet = 1;

                $ip_adresa = "10.0.$treti_oktet.$ctvrty_oktet";

                $update = $db->prepare("
                    UPDATE vysilace
                    SET ip_rozsah = :ip
                    WHERE id = :id
                ");

                $update->execute([
                    'ip' => $ip_adresa,
                    'id' => $nove_id
                ]);

                $db->commit();

                $vypis_ok = "Vysílač byl vytvořen: $nazev ($ip_adresa)";

            } catch (Exception $e) {

                $db->rollBack();
                $vypis_error = "Chyba při ukládání vysílače.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přidat vysílač</title>
    <link rel="stylesheet" href="vysilac.css">
</head>
<body>

<h1>Přidat vysílač</h1>

<?php if ($vypis_error): ?>
    <p style="color:red;"><?= htmlspecialchars($vypis_error) ?></p>
<?php endif; ?>

<?php if ($vypis_ok): ?>
    <p style="color:green;"><?= htmlspecialchars($vypis_ok) ?></p>
<?php endif; ?>

<form method="post">

    <label>Název vysílače</label>
    <input type="text" name="nazev_vysilace" required>

    <label>Předchozí vysílač</label>
    <select name="predchozi_vysilac" <?= ($pocet_vysilacu > 0) ? 'required' : '' ?>>
        <option value="">--</option>
        <?php foreach ($vysilace as $v): ?>
            <option value="<?= $v['id'] ?>">
                <?= htmlspecialchars($v['nazev_vysilace']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Vytvořit vysílač</button>
</form>

<a class="back" href="../index.php">◄ Zpět</a>

</body>
</html>