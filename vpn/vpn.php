<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}

include '../db.php';

$vypis_ok    = null;
$vypis_error = null;

$serverPublicKey = "3nbnQkeYUOHhk64SHgrw4Bkqe+JbEzB/Iw4Lc42FVWo=";
$serverEndpoint  = "10.30.30.198";
$serverPort      = "51820";

$uzivatele = $db->query("
    SELECT Id, jmeno, prijmeni, email
    FROM uzivatele
    ORDER BY prijmeni, jmeno
")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['generuj'])) {

    $uzivatel = (int)($_POST['uzivatel'] ?? 0);
    $pub_klic = trim($_POST['public_key'] ?? '');

    if (!$uzivatel || !$pub_klic) {
        $vypis_error = "Vyplň všechna pole.";
        goto konec;
    }

    $stmt = $db->prepare("
        SELECT *
        FROM vpn
        WHERE id_uzivatele = ?
    ");
    $stmt->execute([$uzivatel]);

    $existujici = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existujici) {
        $vypis_ok = "
TENTO UŽIVATEL VPN UŽ MÁ

Veřejný klíč:
{$existujici['verejny_klic']}

IP adresa:
{$existujici['ip_adresa']}
";
        goto konec;
    }

    $stmt = $db->query("
        SELECT ip_adresa
        FROM vpn
        ORDER BY INET_ATON(ip_adresa) DESC
    ");

    $posledni = $stmt->fetchColumn();

    if ($posledni) {
        $c    = explode('.', $posledni);
        $dalsi = (int)$c[3] + 1;
    } else {
        $dalsi = 2;
    }

    if ($dalsi > 254) {
        $vypis_error = "Došel IP rozsah.";
        goto konec;
    }

    $ip = "10.200.200.$dalsi";

    $stmt = $db->prepare("
        INSERT INTO vpn
        (id_uzivatele, ip_adresa, verejny_klic)
        VALUES (?,?,?)
    ");

    $stmt->execute([
        $uzivatel,
        $ip,
        $pub_klic
    ]);

    $config = "
------------------------------

Pod:
[Interface]
PrivateKey = ***************************
přidej k technikovi na pc následující text:

Address = $ip/24
DNS = 8.8.8.8

[Peer]
PublicKey = $serverPublicKey
Endpoint = $serverEndpoint:$serverPort
AllowedIPs = 10.30.30.0/24
PersistentKeepalive = 25
";

    $vypis_ok = "
VPN VYTVOŘENA

IP: $ip

$config

------------------------------
Zadej na MikroTik server:

/interface wireguard peers add interface=wg-vpn public-key=\"$pub_klic\" allowed-address=$ip/32
";
}

if (isset($_POST['mazat'])) {

    $uzivatel = (int)($_POST['mazat_uzivatel'] ?? 0);

    if (!$uzivatel) {
        $vypis_error = "Vyber uživatele.";
        goto konec;
    }

    $stmt = $db->prepare("
        SELECT *
        FROM vpn
        WHERE id_uzivatele = ?
    ");
    $stmt->execute([$uzivatel]);

    $vpn = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vpn) {
        $vypis_error = "Tento uživatel VPN nemá.";
        goto konec;
    }

    $klic = $vpn['verejny_klic'];
    $ip   = $vpn['ip_adresa'];

    $stmt = $db->prepare("
        DELETE FROM vpn
        WHERE id_uzivatele = ?
    ");
    $stmt->execute([$uzivatel]);

    $vypis_ok = "
VPN SMAZÁNA

Zadej na MikroTik server:

/interface wireguard peers remove [find public-key=\"$klic\"]
";
}

konec:
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>VPN Správa</title>
    <link rel="stylesheet" href="vpn.css">
</head>
<body>
<h1>Správa VPN</h1>

<div class="hlavni">

    <div class="box">
        <h2>Přidat VPN</h2>

        <form method="post">

            <label>Uživatel</label>
            <select name="uzivatel" required>
                <option value="">-- vyber --</option>
                <?php foreach ($uzivatele as $u): ?>
                    <option value="<?= $u['Id'] ?>">
                        <?= htmlspecialchars($u['jmeno'].' '.$u['prijmeni'].' ('.$u['email'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Veřejný klíč</label>
            <input type="text" name="public_key" required>

            <small>
                Otevři aplikaci WireGuard u technika na PC,
                vytvoř prázdný tunel (vlevo dole šipka) a zkopíruj veřejný klíč.
            </small>

            <button name="generuj">
                Vytvořit VPN
            </button>

        </form>
    </div>

    <div class="box">
        <h2>Smazat VPN</h2>

        <form method="post">

            <label>Uživatel</label>
            <select name="mazat_uzivatel" required>
                <option value="">-- vyber --</option>
                <?php foreach ($uzivatele as $u): ?>
                    <option value="<?= $u['Id'] ?>">
                        <?= htmlspecialchars($u['jmeno'].' '.$u['prijmeni'].' ('.$u['email'].')') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button name="mazat" class="red">
                Smazat VPN
            </button>

        </form>
    </div>

    <?php if ($vypis_error): ?>
        <p class="error">
            <?= htmlspecialchars($vypis_error) ?>
        </p>
    <?php endif; ?>

    <?php if ($vypis_ok): ?>
        <div>
            <pre><?= htmlspecialchars(trim($vypis_ok)) ?></pre>
        </div>
    <?php endif; ?>

</div>
<p>
    <a href="http://10.30.30.198:85" target="_blank">
        Přihlásit se na server
    </a>
</p>

<div>
    <a href="../index.php" class="tlacitko">◄ Zpět</a>
</div>

</body>
</html>
