<?php

function scoreToDificuldade(int $score): string {
    return match($score) {
        1       => 'Fácil',
        3       => 'Avançado',
        default => 'Intermediário',
    };
}

// quanto mais perguntas, maior o score
function scorePerguntas(int $total): int {
    if ($total <= 3) return 1;
    if ($total <= 7) return 2;
    return 3;
}

// quanto menos tempo por pergunta, maior o score
function scoreTempo(float $mediaSegundos): int {
    if ($mediaSegundos >= 60) return 1;
    if ($mediaSegundos >= 30) return 2;
    return 3;
}

function calcularDificuldade(PDO $pdo, int $idQuiz): string {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(id)          AS total_perguntas,
                AVG(tempo_limite)  AS media_tempo
            FROM perguntas
            WHERE id_quiz = ?
        ");
        $stmt->execute([$idQuiz]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int)($row['total_perguntas'] ?? 0);

        if ($total === 0) {
            return 'Intermediário';
        }

        $mediaTempo = (float)($row['media_tempo'] ?? 30);

        $scoreP = scorePerguntas($total);
        $scoreT = scoreTempo($mediaTempo);

        // o fator mais pesado define a dificuldade final
        $scoreFinal = max($scoreP, $scoreT);

        return scoreToDificuldade($scoreFinal);

    } catch (Exception $e) {
        return 'Intermediário';
    }
}

// calcula e já salva no banco — usar sempre que o quiz for editado
function recalcularESalvar(PDO $pdo, int $idQuiz): string {
    $dif = calcularDificuldade($pdo, $idQuiz);
    try {
        $stmt = $pdo->prepare("UPDATE quizzes SET Dificuldade = ? WHERE id = ?");
        $stmt->execute([$dif, $idQuiz]);
    } catch (Exception $e) {
        // falha silenciosa — não quebra o fluxo principal
    }
    return $dif;
}