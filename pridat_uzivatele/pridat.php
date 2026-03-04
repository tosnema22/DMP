<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}

if ($_SESSION['role_id'] != 1) {
    header("Location: ../index.php");
    exit;
}

$chyba = null;
$ok = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jmeno = trim($_POST['jmeno']);
    $prijmeni = trim($_POST['prijmeni']);
    $email = trim($_POST['email']);
    $heslo = $_POST['heslo'];
    $role_id = (int)$_POST['role_id'];

    if (strlen($jmeno) < 3 || $jmeno[0] !== strtoupper($jmeno[0])) {
        $chyba = "Jméno musí mít alespoň 3 znaky a začínat velkým písmenem.";
    }

    elseif (strlen($prijmeni) < 3 || $prijmeni[0] !== strtoupper($prijmeni[0])) {
        $chyba = "Příjmení musí mít alespoň 3 znaky a začínat velkým písmenem.";
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $chyba = "Neplatný email.";
    }

    elseif (strlen($heslo) < 6 || !preg_match('/[0-9]/', $heslo)) {
        $chyba = "Heslo musí mít alespoň 6 znaků a obsahovat alespoň jedno číslo.";
    }

    else {
    $stmt = $db->prepare("SELECT Id FROM uzivatele WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $chyba = "Tento email už existuje.";
    } else {

        $heslo_hash = password_hash($heslo, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO uzivatele (jmeno, prijmeni, email, heslo, role_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$jmeno, $prijmeni, $email, $heslo_hash, $role_id]);

        $ok = "Uživatel přidán.";
        }
    } 
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přidat uživatele</title>
    <link rel="stylesheet" href="pridat.css">
</head>
<body>

<h1>Přidat uživatele</h1>

<?php if ($chyba): ?>
    <p class="error"><?= htmlspecialchars($chyba) ?></p>
<?php endif; ?>

<?php if ($ok): ?>
    <p class="ok"><?= htmlspecialchars($ok) ?></p>
<?php endif; ?>

<form method="post">

    <label>Jméno</label>
    <input type="text" name="jmeno"
           value="<?= htmlspecialchars($_POST['jmeno'] ?? '') ?>" required>

    <label>Příjmení</label>
    <input type="text" name="prijmeni"
           value="<?= htmlspecialchars($_POST['prijmeni'] ?? '') ?>" required>

    <label>Email</label>
    <input type="email" name="email"
           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

    <label>Heslo</label>
    <input type="password" name="heslo" required>

    <label>Role</label>
    <select name="role_id">
        <option value="2"
            <?= (($_POST['role_id'] ?? '') == 2) ? 'selected' : '' ?>>
            Běžný uživatel
        </option>
        <option value="1"
            <?= (($_POST['role_id'] ?? '') == 1) ? 'selected' : '' ?>>
            Admin
        </option>
    </select>

    <button type="submit">Vytvořit uživatele</button>

</form>

<br>
<a href="../index.php">◄ Zpět</a>

</body>
</html>
