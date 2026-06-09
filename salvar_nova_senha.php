<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: esqueci_senha.php");
    exit;
}

$token = trim($_POST['token'] ?? '');
$novaSenha = $_POST['nova_senha'] ?? '';

function renderMensagemSenha(string $titulo, string $mensagem, string $cor, string $link, string $textoLink): void {
    echo "
    <div style='font-family: Arial, sans-serif; max-width: 420px; margin: 50px auto; padding: 24px; border: 1px solid {$cor}; border-radius: 12px; background: rgba(47, 179, 153, 0.08); text-align: center;'>
        <h2 style='color: {$cor}; margin-top: 0;'>{$titulo}</h2>
        <p style='color: #333;'>{$mensagem}</p>
        <br>
        <a href='{$link}' style='display: inline-block; padding: 10px 20px; background: {$cor}; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>{$textoLink}</a>
    </div>
    ";
}

if ($token === '' || strlen($token) < 32) {
    renderMensagemSenha('Link invalido', 'Solicite uma nova recuperacao de senha.', '#E94B3C', 'esqueci_senha.php', 'Tentar novamente');
    exit;
}

if (strlen($novaSenha) < 8) {
    renderMensagemSenha('Senha muito curta', 'A nova senha precisa ter pelo menos 8 caracteres.', '#E94B3C', 'javascript:history.back()', 'Voltar');
    exit;
}

try {
    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        UPDATE usuarios
        SET senha = ?, token = NULL
        WHERE token = ?
    ");
    $stmt->execute([$senhaHash, $token]);

    if ($stmt->rowCount() === 0) {
        renderMensagemSenha('Link expirado', 'Este link ja foi usado ou nao existe mais.', '#E94B3C', 'esqueci_senha.php', 'Enviar novo link');
        exit;
    }

    renderMensagemSenha('Sucesso!', 'Senha alterada com sucesso.', '#2FB399', 'TELA_DE_LOGIN.php', 'Fazer login');
} catch (Exception $e) {
    renderMensagemSenha('Erro', 'Nao foi possivel atualizar a senha agora.', '#E94B3C', 'esqueci_senha.php', 'Tentar novamente');
}
