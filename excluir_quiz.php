<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_quiz'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_tipo'] !== 'professor') {
        header("Location: TELA_DE_LOGIN.php");
        exit;
    }

    $idQuiz = $_POST['id_quiz'];
    $idProfessor = $_SESSION['user_id'];

    try {
        // Garante que o professor só apaga o SEU PRÓPRIO quiz
        $sql = "DELETE FROM quizzes WHERE id = ? AND id_professor = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idQuiz, $idProfessor]);

        header("Location: QUIZZES_CRIADOS.php");
        exit;
    } catch (Exception $e) {
        die("Erro ao excluir quiz: " . $e->getMessage());
    }
} else {
    header("Location: QUIZZES_CRIADOS.php");
    exit;
}
?>