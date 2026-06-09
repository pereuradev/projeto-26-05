<?php
// login.php
require_once 'conexao.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verifica a senha hash criptografada
        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_tipo'] = $user['tipo']; // 'professor' ou 'aluno'

            // Redirecionamento com base no nível de acesso
            if ($user['tipo'] === 'professor') {
                header("Location: TELA_INICIO_PROF.php");
            } else {
                header("Location: PAGINA_ALUNO.php");
            }
            exit;
        } else {
            $_SESSION['login_erro'] = 'E-mail ou senha incorretos!';
            header("Location: TELA_DE_LOGIN.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['login_erro'] = 'Erro no sistema: ' . $e->getMessage();
        header("Location: TELA_DE_LOGIN.php");
        exit;
    }
}