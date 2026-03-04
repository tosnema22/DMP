<?php
session_start();

if (!isset($_SESSION['uzivatel_id'])) {
    header("Location: ../prihlaseni.php");
    exit;
}
?>
<!DOCTYPE html>
    <html lang="cs">
    <head>
    <meta charset="UTF-8">
<title>Obrázek</title>
<style>
    html, body {
        margin: 0;
        padding: 0;
        background: #f4f6f8;
        font-family: Arial, sans-serif;
    }
    .tlacitko_zpet {
        position: fixed;
        top: 20px;
        left: 20px;
        text-decoration: none;
        background: white;
        padding: 8px 14px;
        border-radius: 8px;
        font-weight: bold;
        color: #333;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: 0.2s;
    }
    .tlacitko_zpet:hover {
        background: #e3f2fd;
        color: #1976d2;
    }
    .okoli {
        padding: 80px 0 40px 0;
        display: flex;
        justify-content: center;
    }
    img {
        width: 90%;
        max-width: 1600px;
        height: auto;
        border-radius: 12px;
    }
</style>
</head>
    <body>
    <a href="../index.php" class="tlacitko_zpet">◄ Zpět</a>
        <div class="okoli">
            <img src="obrazek.png" alt="Diagram sítě">
        </div>
    </body>
</html>