<?php
session_start();
include 'db.php';

$email = '';
$chyba = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $heslo = $_POST['heslo'];

    $stmt = $db->prepare("
        SELECT *
        FROM uzivatele
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $uzivatel = $stmt->fetch();

    if ($uzivatel && password_verify($heslo, $uzivatel['heslo'])) {

        $_SESSION['uzivatel_id'] = $uzivatel['Id'];
        $_SESSION['uzivatel_jmeno'] = $uzivatel['jmeno'];
        $_SESSION['uzivatel_prijmeni'] = $uzivatel['prijmeni'];
        $_SESSION['role_id'] = $uzivatel['role_id'];

        header("Location: index.php");
        exit;

    } else {
        $chyba = "Špatný email nebo heslo";
    }
}
?>


<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Přihlášení</title>
    <link rel="stylesheet" href="css/prihlaseni.css">
</head>
<body>

<h1>Přihlášení</h1>

<?php if ($chyba): ?>
    <p class="error"><?= htmlspecialchars($chyba) ?></p>
<?php endif; ?>

<form method="post">
    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label>Heslo</label>
    <input type="password" name="heslo" required>

    <button type="submit">Přihlásit</button>
</form>

</body>
</html>
