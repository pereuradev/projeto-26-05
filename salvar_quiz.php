<?php
session_start();
require_once 'conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['status_quiz'] = 'erro';
        header("Location: TELA_INICIO_PROF.php"); 
        exit;
    }

    $idProfessor  = $_SESSION['user_id']; 
    $tituloQuiz   = trim($_POST['quiz_title'] ?? '');
    $tempoLimite  = $_POST['quiz_time'] ?? 30;
    $enunciados   = $_POST['enunciado'] ?? [];
    $alternativas = $_POST['alternativas'] ?? [];
    $corretas     = $_POST['correta'] ?? [];
    
    // 1. RECEBE O ARRAY DE DIFICULDADES
    $dificuldades = $_POST['dificuldade'] ?? [];

    if (empty($tituloQuiz) || empty($enunciados)) {
        $_SESSION['status_quiz'] = 'erro';
        header("Location: TELA_INICIO_PROF.php");
        exit;
    }

    // ==========================================
    // CÁLCULO DA DIFICULDADE GERAL DO QUIZ
    // ==========================================
    $dificuldadeVencedora = 'Intermediário'; // Valor Padrão
    
    if (!empty($dificuldades)) {
        // Conta quantas vezes cada palavra apareceu. Ex: ['Fácil' => 1, 'Avançado' => 3]
        $contagem = array_count_values($dificuldades);
        
        // Ordena os resultados do maior para o menor
        arsort($contagem);
        
        // Pega a chave do primeiro elemento (a que teve mais votos)
        $dificuldadeVencedora = array_key_first($contagem);
    }

    try {
        $pdo->beginTransaction();

        // 2. INSERE O QUIZ COM A DIFICULDADE CALCULADA
        $descQuiz = "Quiz dinâmico com tempo limite de " . $tempoLimite . " segundos por questão.";
        
        $sqlQuiz  = "INSERT INTO quizzes (id_professor, titulo, descricao, Dificuldade) VALUES (?, ?, ?, ?)";
        $stmtQuiz = $pdo->prepare($sqlQuiz);
        $stmtQuiz->execute([$idProfessor, $tituloQuiz, $descQuiz, $dificuldadeVencedora]);
        
        $idQuizCriado = $pdo->lastInsertId();

       // 3. Inserir na tabela 'perguntas'
        foreach ($enunciados as $index => $textoEnunciado) {
            if (empty(trim($textoEnunciado))) continue;

            // Capta a dificuldade específica DESTA pergunta (se não vier, assume Intermediário)
            $dificuldadeQuestao = $dificuldades[$index] ?? 'Intermediário';

            // Adicionamos a coluna 'dificuldade' no INSERT
            $sqlPergunta  = "INSERT INTO perguntas (id_quiz, texto_pergunta, tempo_limite, dificuldade) VALUES (?, ?, ?, ?)";
            $stmtPergunta = $pdo->prepare($sqlPergunta);
            
            // Passamos a variável $dificuldadeQuestao no execute
            $stmtPergunta->execute([$idQuizCriado, trim($textoEnunciado), $tempoLimite, $dificuldadeQuestao]);
            
            $idPerguntaCriada = $pdo->lastInsertId();

            // 4. Inserir na tabela 'alternativas'
            $letraCorreta = $corretas[$index] ?? null;
            if (isset($alternativas[$index]) && is_array($alternativas[$index])) {
                foreach ($alternativas[$index] as $letra => $textoAlternativa) {
                    $isCorreta = ($letra === $letraCorreta) ? 1 : 0;
                    
                    $sqlAlternativa  = "INSERT INTO alternativas (id_pergunta, texto_alternativa, is_correta) VALUES (?, ?, ?)";
                    $stmtAlternativa = $pdo->prepare($sqlAlternativa);
                    $stmtAlternativa->execute([$idPerguntaCriada, trim($textoAlternativa), $isCorreta]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['status_quiz'] = 'sucesso';
        header("Location: TELA_INICIO_PROF.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['status_quiz'] = 'erro';
        header("Location: TELA_INICIO_PROF.php");
        exit;
    }
} else {
    header("Location: TELA_INICIO_PROF.php");
    exit;
}
?>