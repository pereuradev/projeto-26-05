<?php
// cadastro.php
require_once 'conexao.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo'];

    try {
        // Verifica se o e-mail já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $_SESSION['cadastro_status'] = 'erro';
            $_SESSION['cadastro_msg'] = 'Este e-mail já está cadastrado!';
            header("Location: TELA_DE_CADASTRO.php");
            exit;
        }

        // Criptografia segura da senha (Hash)
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Insere no banco
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $senhaHash, $tipo]);

        $_SESSION['cadastro_status'] = 'sucesso';
        $_SESSION['cadastro_msg'] = 'Conta criada com sucesso! Faça o login.';
        header("Location: TELA_DE_LOGIN.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['cadastro_status'] = 'erro';
        $_SESSION['cadastro_msg'] = 'Erro no sistema: ' . $e->getMessage();
        header("Location: TELA_DE_CADASTRO.php");
        exit;
    }
}