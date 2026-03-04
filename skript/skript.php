<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}

include '../db.php';

$rezim = $_POST['rezim'] ?? null;
$vysledek = null;
$chyba = null;

$jednotky = $db->query("
    SELECT Id, nazev, ip_adresa
    FROM vysilac_jednotky
    WHERE ptp = 0
    ORDER BY nazev
")->fetchAll(PDO::FETCH_ASSOC);

$klienti = $db->query("
    SELECT Id, nazev, ip_adresa
    FROM klientske_jednotky
    ORDER BY nazev
")->fetchAll(PDO::FETCH_ASSOC);

$vysilace = $db->query("
    SELECT Id, nazev_vysilace
    FROM vysilace
    ORDER BY nazev_vysilace
")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['generuj'])) {

    if (!$rezim) {
        $chyba = "Vyber jednu možnost.";
    }

    elseif ($rezim === 'jednotka') {

        $jednotka_id = (int)($_POST['jednotka'] ?? 0);

        if (!$jednotka_id) {
            $chyba = "Nevybral jsi jednotku.";
        } else {

            $stmt = $db->prepare("SELECT * FROM vysilac_jednotky WHERE id = ?");
            $stmt->execute([$jednotka_id]);
            $j = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$j) {
                $chyba = "Jednotka neexistuje.";
            } else {

                $c = explode('.', $j['ip_adresa']);

                if (count($c) === 4) {

                    $switch = "{$c[0]}.{$c[1]}.{$c[2]}." . ($c[3] + 200);
                    $port1  = "{$c[0]}.{$c[1]}.{$c[2]}." . ($c[3] + 100);

                    $vysledek = "
JEDNOTKA NA VYSÍLAČI
------------------
{$j['nazev']}

Switch port směrem k VJ: $switch
Port1 na VJ: $port1
";
                } else {
                    $chyba = "Neplatná IP adresa.";
                }
            }
        }
    }

    elseif ($rezim === 'klient') {

        $klient_id = (int)($_POST['klient'] ?? 0);

        if (!$klient_id) {
            $chyba = "Nevybral jsi klienta.";
        } else {

            $stmt = $db->prepare("SELECT * FROM klientske_jednotky WHERE id = ?");
            $stmt->execute([$klient_id]);
            $k = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$k) {
                $chyba = "Klient neexistuje.";
            } else {

                $c = explode('.', $k['ip_adresa']);

                if (count($c) === 4) {

                    $o1 = (int)$c[0];
                    $o2 = (int)$c[1];
                    $o3 = (int)$c[2];
                    $o4 = (int)$c[3];

                    $pc_o4 = $o4 - 84;
                    $vlan_o4 = $o4 - 168;

                    $pc_ip = "$o1.$o2.$o3.$pc_o4";
                    $vlan_ip = "$o1.$o2.$o3.$vlan_o4";

                    $vysledek = "
KLIENTSKÁ JEDNOTKA (KLJ)
-----------------
na odesílací jednotce přidej bridge na port do ktereho jste zapojil kabel směr KLJ

IP adresa pro správu KLJ: {$k['ip_adresa']}
Vytvoř bridge a přidej do něj port 1 a 2 a na ně dej vlanu s touto IP $vlan_ip

IP routeru u klienta (zde v gns 3 je to IP počítače): $pc_ip
";
                } else {
                    $chyba = "Neplatná IP adresa.";
                }
            }
        }
    }

    elseif ($rezim === 'novy') {

        $vysilac_id = (int)($_POST['novy_vysilac'] ?? 0);

        if (!$vysilac_id) {
            $chyba = "Nevybral jsi vysílač.";
        } else {

            $stmt = $db->prepare("
                SELECT * 
                FROM vysilac_jednotky 
                WHERE id_vysilace = ?
                ORDER BY id
            ");
            $stmt->execute([$vysilac_id]);
            $j = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$j) {
                $chyba = "Vybraný vysílač nemá jednotku.";
            } else {

                $c = explode('.', $j['ip_adresa']);

                if (count($c) === 4) {

                    $base = "{$c[0]}.{$c[1]}.{$c[2]}";

                    $vysledek = "
VYSÍLAČ (PTP)
============

ZÁKLADNÍ SÍŤ: $base.0/24

port na switchi starého vysílače: $base.240
port na odesilaci antene na starem vysilaci smer switch: $base.241
port na odesilaci antene na starem vysilaci smer novou antenu: $base.249

port na prijimaci antene na novem vysilaci smer novou antenu: $base.250
port na switchi noveého vysílače: $base.253

SWITCH
------
IP: $base.254
";
                } else {
                    $chyba = "Neplatná IP adresa.";
                }
            }
        }
    }

    elseif ($rezim === 'firewall') {

        $fw_typ = $_POST['fw_typ'] ?? null;

        if (!$fw_typ) {
            $chyba = "Nevybral jsi typ firewallu.";
        } else {

            if ($fw_typ === "klient") {

                $vysledek = '
/ip firewall filter
add chain=input action=accept connection-state=established,related
add chain=input action=drop connection-state=invalid

add chain=input action=accept protocol=icmp src-address=10.30.30.0/24
add chain=input action=accept protocol=icmp src-address=10.10.10.0/24

add chain=input action=accept protocol=tcp src-address=10.30.30.0/24 dst-port=2222
add chain=input action=accept protocol=tcp src-address=10.30.30.0/24 dst-port=85
add chain=input action=accept protocol=tcp src-address=10.30.30.0/24 dst-port=5491

add chain=input action=accept protocol=tcp src-address=10.10.10.0/24 dst-port=2222
add chain=input action=accept protocol=tcp src-address=10.10.10.0/24 dst-port=85
add chain=input action=accept protocol=tcp src-address=10.10.10.0/24 dst-port=5491

add chain=input action=drop
';

            } else {

                $vysledek = '
/ip firewall filter

add chain=input action=accept connection-state=established,related
add chain=input action=drop connection-state=invalid

add chain=input action=accept protocol=ospf

add chain=input action=accept protocol=icmp src-address=10.30.30.0/24
add chain=input action=accept protocol=icmp src-address=10.10.10.0/24

add chain=input action=accept protocol=tcp src-address=10.30.30.0/24 dst-port=2222
add chain=input action=accept protocol=tcp src-address=10.30.30.0/24 dst-port=85
add chain=input action=accept protocol=tcp src-address=10.30.30.0/24 dst-port=5491

add chain=input action=accept protocol=tcp src-address=10.10.10.0/24 dst-port=2222
add chain=input action=accept protocol=tcp src-address=10.10.10.0/24 dst-port=85
add chain=input action=accept protocol=tcp src-address=10.10.10.0/24 dst-port=5491

add chain=input action=drop
';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="cs">
    <head>
    <meta charset="UTF-8">
    <title>Zjisti jakou IP/ Firewall kde nastavit</title>
    <link rel="stylesheet" href="skript.css">
    </head>
<body>

<h1>Zjisti jakou IP kam nastavit</h1>

<div>
    <form method="post" class="box">
        <h3>Vyber co chceš nastavit</h3>

        <label><input type="radio" name="rezim" value="jednotka"> Jednotku na vysílač</label><br>
        <label><input type="radio" name="rezim" value="klient"> Klientskou jednotku</label><br>
        <label><input type="radio" name="rezim" value="novy"> Nový vysílač (PTP)</label><br>
        <label><input type="radio" name="rezim" value="firewall"> Firewall</label><br><br>
        <button name="vybrat">Vybrat</button>
    </form>

    <?php if ($rezim): ?>
    <div class="box">
        <form method="post">
            <input type="hidden" name="rezim" value="<?= htmlspecialchars($rezim) ?>">

            <?php if ($rezim === "jednotka"): ?>

                <select name="jednotka" required>
                    <option value="">-- vyber --</option>
                    <?php foreach ($jednotky as $j): ?>
                        <option value="<?= $j['Id'] ?>">
                            <?= $j['nazev'] ?> (<?= $j['ip_adresa'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($rezim === "klient"): ?>

                <select name="klient" required>
                    <option value="">-- vyber --</option>
                    <?php foreach ($klienti as $k): ?>
                        <option value="<?= $k['Id'] ?>">
                            <?= htmlspecialchars($k['nazev']) ?> (<?= $k['ip_adresa'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($rezim === "novy"): ?>

                <select name="novy_vysilac" required>
                    <option value="">-- vyber vysílač --</option>
                    <?php foreach ($vysilace as $v): ?>
                        <option value="<?= $v['Id'] ?>">
                            <?= $v['nazev_vysilace'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($rezim === "firewall"): ?>

                <select name="fw_typ" required>
                    <option value="">-- vyber typ --</option>
                    <option value="klient">Klientská jednotka</option>
                    <option value="ostatni">Všechno ostatní</option>
                </select>

            <?php endif; ?>

            <br><br>
            <button name="generuj">Generovat</button>
        </form>
    </div>
<?php endif; ?>


    <?php if ($chyba): ?>
        <div class="box chyba">
            <?= htmlspecialchars($chyba) ?>
        </div>
    <?php endif; ?>

    <?php if ($vysledek): ?>
        <div class="box">
            <textarea rows="20" readonly><?= trim($vysledek) ?></textarea>

        </div>
    <?php endif; ?>
</div>

<div>
    <a href="../index.php" class="tlacitko">◄ Zpět</a>
</div>

</body>
</html>
