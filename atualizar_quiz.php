<?php
session_start();
require_once 'conexao.php';
require_once 'calcular_dificuldade.php';

if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: QUIZZES_CRIADOS.php");
    exit;
}

$idProfessor = $_SESSION['user_id'];
$idQuiz      = (int)$_POST['id_quiz'];
$titulo      = trim($_POST['quiz_title']);
$tempoLimite = (int)$_POST['quiz_time'];

try {
    $pdo->beginTransaction();

    // garante que o quiz é do professor logado
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE id = ? AND id_professor = ?");
    $stmt->execute([$idQuiz, $idProfessor]);
    if (!$stmt->fetch()) {
        throw new Exception("Quiz não encontrado ou acesso negado.");
    }

    $pdo->prepare("UPDATE quizzes SET titulo = ? WHERE id = ?")->execute([$titulo, $idQuiz]);

    // pega os IDs atuais pra comparar com o que vier do formulário
    $stmtGetQs = $pdo->prepare("SELECT id FROM perguntas WHERE id_quiz = ?");
    $stmtGetQs->execute([$idQuiz]);
    $perguntasNoBanco  = $stmtGetQs->fetchAll(PDO::FETCH_COLUMN);
    $perguntasEnviadas = [];

    if (isset($_POST['id_pergunta']) && is_array($_POST['id_pergunta'])) {
        foreach ($_POST['id_pergunta'] as $num => $id_pergunta) {
            $enunciado    = trim($_POST['enunciado'][$num]);
            $dificuldade  = trim($_POST['dificuldade'][$num]);
            $correta      = $_POST['correta'][$num];
            $alternativas = $_POST['alternativas'][$num];
            $letras       = ['A', 'B', 'C', 'D'];

            if ($id_pergunta === 'nova') {
                $pdo->prepare("INSERT INTO perguntas (id_quiz, texto_pergunta, tempo_limite, dificuldade) VALUES (?, ?, ?, ?)")
                    ->execute([$idQuiz, $enunciado, $tempoLimite, $dificuldade]);

                $novaPerguntaId = $pdo->lastInsertId();

                foreach ($letras as $letra) {
                    $pdo->prepare("INSERT INTO alternativas (id_pergunta, texto_alternativa, is_correta) VALUES (?, ?, ?)")
                        ->execute([$novaPerguntaId, trim($alternativas[$letra]), ($correta === $letra) ? 1 : 0]);
                }
            } else {
                $id_pergunta = (int)$id_pergunta;
                $perguntasEnviadas[] = $id_pergunta;

                $pdo->prepare("UPDATE perguntas SET texto_pergunta = ?, tempo_limite = ?, dificuldade = ? WHERE id = ?")
                    ->execute([$enunciado, $tempoLimite, $dificuldade, $id_pergunta]);

                $stmtAlt = $pdo->prepare("SELECT id FROM alternativas WHERE id_pergunta = ? ORDER BY id ASC");
                $stmtAlt->execute([$id_pergunta]);
                $altIds = $stmtAlt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($letras as $index => $letra) {
                    $textoAlt  = trim($alternativas[$letra]);
                    $isCorreta = ($correta === $letra) ? 1 : 0;

                    // atualiza se já existe, insere se faltava
                    if (isset($altIds[$index])) {
                        $pdo->prepare("UPDATE alternativas SET texto_alternativa = ?, is_correta = ? WHERE id = ?")
                            ->execute([$textoAlt, $isCorreta, $altIds[$index]]);
                    } else {
                        $pdo->prepare("INSERT INTO alternativas (id_pergunta, texto_alternativa, is_correta) VALUES (?, ?, ?)")
                            ->execute([$id_pergunta, $textoAlt, $isCorreta]);
                    }
                }
            }
        }
    }

    // remove perguntas que o professor apagou no formulário
    foreach (array_diff($perguntasNoBanco, $perguntasEnviadas) as $idExcluir) {
        $pdo->prepare("DELETE FROM alternativas WHERE id_pergunta = ?")->execute([$idExcluir]);
        $pdo->prepare("DELETE FROM perguntas WHERE id = ?")->execute([$idExcluir]);
    }

    recalcularESalvar($pdo, $idQuiz);

    $pdo->commit();
    header("Location: QUIZZES_CRIADOS.php?msg=atualizado");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Erro ao salvar as alterações do quiz: " . $e->getMessage());
}
