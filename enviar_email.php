<?php
require 'vendor/autoload.php';
require_once 'conexao.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: esqueci_senha.php");
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo "<script>alert('Informe um e-mail valido.'); window.location.href='esqueci_senha.php';</script>";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo "<script>alert('E-mail nao encontrado!'); window.location.href='esqueci_senha.php';</script>";
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare("UPDATE usuarios SET token = ? WHERE id = ?");
    $stmt->execute([$token, $usuario['id']]);

    $link = "http://localhost:8080/projeto-26-05/redefinir_senha.php?token=" . urlencode($token);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('CORTEX_SMTP_USER') ?: '2000141m@escolas.anchieta.br';
    $mail->Password   = getenv('CORTEX_SMTP_PASS') ?: 'mzum aosm vbbb coko';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($mail->Username, 'Cortex');
    $mail->addAddress($email, $usuario['nome'] ?? '');

    $mail->isHTML(true);
    $mail->Subject = 'Recuperacao de senha - Cortex';
    $mail->Body    = "
        <h2>Recuperacao de senha</h2>
        <p>Recebemos uma solicitacao para redefinir sua senha.</p>
        <p>Clique no link abaixo para criar uma nova senha:</p>
        <p><a href='{$link}'>{$link}</a></p>
        <p>Se voce nao solicitou isso, ignore este e-mail.</p>
    ";

    $mail->send();

    echo "<script>alert('E-mail enviado com sucesso!'); window.location.href='TELA_DE_LOGIN.php';</script>";
} catch (Exception $e) {
    echo "<script>alert('Nao foi possivel enviar o e-mail agora. Tente novamente.'); window.location.href='esqueci_senha.php';</script>";
}
