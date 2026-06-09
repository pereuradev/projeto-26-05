<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'aluno') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'erro' => 'Acesso não autorizado.']);
        exit;
    }
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

$idAluno = (int)$_SESSION['user_id'];

function garantirColunaTempoPontuacoes(PDO $pdo): void {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'pontuacoes'
          AND COLUMN_NAME = 'tempo_segundos'
    ");
    $stmt->execute();

    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE pontuacoes ADD tempo_segundos INT NULL AFTER pontuacao");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $dados = json_decode(file_get_contents('php://input'), true);
    
    $idQuiz    = isset($dados['id_quiz'])   ? (int)$dados['id_quiz']   : 0;
    $pontuacao = isset($dados['pontuacao']) ? (int)$dados['pontuacao'] : 0;
    $tempoSegundos = isset($dados['tempo_segundos']) ? max(1, (int)$dados['tempo_segundos']) : null;

    if ($idQuiz === 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'erro' => 'Identificador do quiz inválido.']);
        exit;
    }

    try {
        $stmtVerifica = $pdo->prepare("SELECT id FROM quizzes WHERE id = ?");
        $stmtVerifica->execute([$idQuiz]);
        if (!$stmtVerifica->fetch()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'erro' => 'Quiz não localizado no sistema.']);
            exit;
        }

        garantirColunaTempoPontuacoes($pdo);

        $stmtIns = $pdo->prepare("INSERT INTO pontuacoes (id_aluno, id_quiz, pontuacao, tempo_segundos, data_jogo) VALUES (?, ?, ?, ?, NOW())");
        $stmtIns->execute([$idAluno, $idQuiz, $pontuacao, $tempoSegundos]);
        
        $stmtRank = $pdo->prepare("
            SELECT COALESCE(u.nome, 'Usuário Removido') AS nome, MAX(p.pontuacao) AS max_pontuacao 
                , MIN(CASE WHEN p.pontuacao = melhores.max_pontuacao THEN COALESCE(p.tempo_segundos, 999999) END) AS melhor_tempo
            FROM pontuacoes p
            INNER JOIN (
                SELECT id_aluno, MAX(pontuacao) AS max_pontuacao
                FROM pontuacoes
                WHERE id_quiz = ?
                GROUP BY id_aluno
            ) melhores ON melhores.id_aluno = p.id_aluno
            LEFT JOIN usuarios u ON p.id_aluno = u.id 
            WHERE p.id_quiz = ? 
            GROUP BY p.id_aluno, u.nome 
            ORDER BY max_pontuacao DESC, melhor_tempo ASC, MIN(CASE WHEN p.pontuacao = melhores.max_pontuacao THEN p.data_jogo END) ASC, nome ASC
            LIMIT 10
        ");
        $stmtRank->execute([$idQuiz, $idQuiz]);
        $ranking = $stmtRank->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['ok' => true, 'pontuacao' => $pontuacao, 'tempo_segundos' => $tempoSegundos, 'ranking' => $ranking]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'erro' => 'Erro interno no servidor de banco de dados.']);
    }
    exit;
}

$idQuiz = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idQuiz === 0) {
    header("Location: MEUS_QUIZZES_ALUNO.php");
    exit;
}

try {
    $stmtQuiz = $pdo->prepare("
        SELECT q.*, u.nome AS nome_professor
        FROM quizzes q
        LEFT JOIN usuarios u ON u.id = q.id_professor
        WHERE q.id = ?
    ");
    $stmtQuiz->execute([$idQuiz]);
    $quiz = $stmtQuiz->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        header("Location: MEUS_QUIZZES_ALUNO.php");
        exit;
    }

    $stmtP = $pdo->prepare("SELECT * FROM perguntas WHERE id_quiz = ? ORDER BY id ASC");
    $stmtP->execute([$idQuiz]);
    $perguntas = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    foreach ($perguntas as &$pergunta) {
        $stmtA = $pdo->prepare("SELECT * FROM alternativas WHERE id_pergunta = ? ORDER BY id ASC");
        $stmtA->execute([$pergunta['id']]);
        $pergunta['alternativas'] = $stmtA->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($pergunta);

} catch (Exception $e) {
    die("Falha crítica ao carregar as informações da avaliação.");
}

$totalPerguntas = count($perguntas);
$tempoLimite    = !empty($perguntas[0]['tempo_limite']) ? (int)$perguntas[0]['tempo_limite'] : 30;
$perguntasJson  = json_encode($perguntas, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <script>
        // Puxa o tema salvo no localStorage imediatamente para evitar flash na tela
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') document.documentElement.classList.add('dark-mode');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Córtex – <?php echo htmlspecialchars($quiz['titulo']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css?v=5">
</head>
<body>

    <div class="bg-shapes" aria-hidden="true">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
        <div class="shape shape-6"></div>
        <div class="shape shape-7"></div>
        <div class="shape shape-8"></div>
        <div class="shape shape-9"></div>
        <div class="shape shape-10"></div>
    </div>

    <div class="quiz-header">
        <div class="quiz-info">
            <h1><?php echo htmlspecialchars($quiz['titulo']); ?></h1>
            <p><i class="fa-solid fa-chalkboard-user"></i> <?php echo htmlspecialchars($quiz['nome_professor'] ?? 'Docente'); ?></p>
        </div>
        <a href="MEUS_QUIZZES_ALUNO.php" class="btn-sair"><i class="fa-solid fa-xmark"></i> Sair</a>
    </div>

    <div class="progress-wrap">
        <div class="progress-bar-bg">
            <div class="progress-bar-fill" id="progress-fill" style="width:0%"></div>
        </div>
        <span class="progress-label" id="progress-label">1 / <?php echo $totalPerguntas; ?></span>
    </div>

    <div class="timer-wrap">
        <i class="fa-regular fa-clock" style="color:var(--muted);font-size:0.85rem;"></i>
        <div class="timer-circle" id="timer-display"><?php echo $tempoLimite; ?></div>
    </div>

    <div class="card-pergunta" id="card-pergunta">
        <div class="numero-q" id="numero-q">
            <span id="label-questao">Questão 1</span>
            <span class="badge-dificuldade" id="badge-dificuldade">Nível Padrão</span>
        </div>
        <div class="enunciado" id="enunciado"></div>
        <div class="grid-alt" id="grid-alternativas"></div>
    </div>

    <div class="tela-resultado" id="tela-resultado">
        <div class="resultado-card">
            <h2 class="resultado-titulo" id="res-titulo">Avaliação Concluída</h2>
            <p class="resultado-sub" id="res-sub">Resumo de desempenho consolidado:</p>
            
            <div class="resultado-stats">
                <div class="stat-box destaque"><span class="num" id="res-pontuacao">0</span><span class="label">Pontos</span></div>
                <div class="stat-box verde">   <span class="num" id="res-acertos">0</span>  <span class="label">Acertos</span></div>
                <div class="stat-box vermelho"><span class="num" id="res-erros">0</span>    <span class="label">Erros</span></div>
                <div class="stat-box">         <span class="num" id="res-tempo">0s</span><span class="label">Tempo</span></div>
                <div class="stat-box">         <span class="num"><?php echo $totalPerguntas; ?></span><span class="label">Total</span></div>
            </div>

            <div class="grafico-container" id="area-grafico" style="display:none;">
                <h3 class="grafico-titulo"><i class="fa-solid fa-chart-column"></i> Classificação Geral (Top 10)</h3>
                <p class="resultado-sub">Em caso de empate na pontuacao, vence quem respondeu em menos tempo.</p>
                <canvas id="rankingChart"></canvas>
            </div>

            <a href="MEUS_QUIZZES_ALUNO.php" class="btn-voltar-resultado">
                <i class="fa-solid fa-arrow-left"></i> Retornar à Listagem de Quizzes
            </a>
        </div>
    </div>

    <div class="popup-overlay" id="popup-overlay">
        <div class="popup">
            <div class="popup-icon" id="popup-icon"><i class="fa-solid fa-check" id="popup-icone-fa"></i></div>
            <h2 id="popup-titulo">Feedback</h2>
            <p id="popup-msg">Processando...</p>
            <div class="popup-score">Pontuação Acumulada: <span id="popup-pontos">0</span> pts</div>
            <button class="btn-popup proximo" id="btn-proximo" onclick="proximaPergunta()">
                Avançar <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <script>
        const PERGUNTAS    = <?php echo $perguntasJson; ?>;
        const TOTAL        = <?php echo $totalPerguntas; ?>;
        const TEMPO_PADRAO = <?php echo $tempoLimite; ?>;
        const ID_QUIZ      = <?php echo $idQuiz; ?>;
        const LETRAS       = ['A','B','C','D'];

        let atual = 0, pontos = 0, acertos = 0, erros = 0;
        let timer = null, respondeu = false;

        function extrairDificuldade(p) {
            return p.dificuldade ? String(p.dificuldade).toLowerCase().trim() : 'facil';
        }

        function renderPergunta() {
            respondeu = false;
            const p     = PERGUNTAS[atual];
            const tempo = p.tempo_limite ? parseInt(p.tempo_limite) : TEMPO_PADRAO;
            const nivel = extrairDificuldade(p);

            let labelNivel = 'Nível Fácil';
            if (nivel === 'intermediario' || nivel === 'intermediário' || nivel === 'medio' || nivel === 'médio') {
                labelNivel = 'Nível Intermediário';
            } else if (nivel === 'avancado' || nivel === 'avançado' || nivel === 'dificil' || nivel === 'difícil') {
                labelNivel = 'Nível Avançado';
            }

            document.getElementById('label-questao').textContent     = `Questão ${atual + 1}`;
            document.getElementById('badge-dificuldade').textContent  = labelNivel;
            document.getElementById('enunciado').textContent          = p.texto_pergunta;
            document.getElementById('progress-fill').style.width      = (atual / TOTAL * 100) + '%';
            document.getElementById('progress-label').textContent     = `${atual + 1} / ${TOTAL}`;

            const grid = document.getElementById('grid-alternativas');
            grid.innerHTML = '';
            p.alternativas.forEach((alt, i) => {
                const btn = document.createElement('button');
                btn.className       = 'alt-btn';
                btn.dataset.correta = alt.is_correta;
                btn.innerHTML = `<span class="letra-badge">${LETRAS[i]}</span>${alt.texto_alternativa}`;
                btn.addEventListener('click', () => responder(btn));
                grid.appendChild(btn);
            });

            iniciarTimer(tempo);
            const card = document.getElementById('card-pergunta');
            card.style.animation = 'none'; card.offsetHeight;
            card.style.animation = 'slideIn 0.3s ease';
        }

        function iniciarTimer(segundos) {
            clearInterval(timer);
            let restante = segundos;
            const el = document.getElementById('timer-display');
            el.classList.remove('urgente');
            el.textContent = restante;
            timer = setInterval(() => {
                restante--;
                el.textContent = restante;
                if (restante <= 5) el.classList.add('urgente');
                if (restante <= 0) { clearInterval(timer); if (!respondeu) tempoEsgotado(); }
            }, 1000);
        }

        function calcularPontosDaQuestao() {
            const p = PERGUNTAS[atual];
            const nivel = extrairDificuldade(p);
            if (nivel === 'intermediario' || nivel === 'intermediário' || nivel === 'medio' || nivel === 'médio') return 20;
            if (nivel === 'avancado' || nivel === 'avançado' || nivel === 'dificil' || nivel === 'difícil') return 30;
            return 10;
        }

        function formatarTempo(totalSegundos) {
            const minutos = Math.floor(totalSegundos / 60);
            const segundos = totalSegundos % 60;

            if (minutos <= 0) return `${segundos}s`;
            return `${minutos}min ${String(segundos).padStart(2, '0')}s`;
        }

        function responder(btnClicado) {
            if (respondeu) return;
            respondeu = true;
            clearInterval(timer);

            const pontosDaQuestao = calcularPontosDaQuestao();
            const todos = document.querySelectorAll('.alt-btn');
            let acertou = false, textoCorreta = '';

            todos.forEach(btn => {
                btn.disabled = true;
                if (btn.dataset.correta == 1) {
                    btn.classList.add('correta');
                    textoCorreta = btn.textContent.trim().slice(1).trim();
                }
            });

            if (btnClicado.dataset.correta == 1) {
                acertou = true; acertos++; pontos += pontosDaQuestao;
            } else {
                btnClicado.classList.add('errada'); erros++;
                todos.forEach(btn => { if (btn.dataset.correta != 1 && btn !== btnClicado) btn.classList.add('neutra'); });
            }
            mostrarPopup(acertou, textoCorreta, false, pontosDaQuestao);
        }

        function tempoEsgotado() {
            respondeu = true;
            const todos = document.querySelectorAll('.alt-btn');
            let textoCorreta = '';
            todos.forEach(btn => {
                btn.disabled = true;
                if (btn.dataset.correta == 1) { btn.classList.add('correta'); textoCorreta = btn.textContent.trim().slice(1).trim(); }
                else btn.classList.add('neutra');
            });
            erros++;
            mostrarPopup(false, textoCorreta, true, 0);
        }

        function mostrarPopup(acertou, textoCorreta, semTempo = false, pontosGanhos = 0) {
            const icon    = document.getElementById('popup-icon');
            const iconeFA = document.getElementById('popup-icone-fa');
            const isUltima = (atual === TOTAL - 1);

            document.getElementById('popup-pontos').textContent = pontos;

            if (semTempo) {
                icon.className = 'popup-icon tempo'; iconeFA.className = 'fa-solid fa-clock';
                document.getElementById('popup-titulo').textContent = 'Tempo Limite Atingido';
                document.getElementById('popup-msg').innerHTML = `A alternativa correta era: <strong>${textoCorreta}</strong>`;
            } else if (acertou) {
                icon.className = 'popup-icon acerto'; iconeFA.className = 'fa-solid fa-check';
                document.getElementById('popup-titulo').textContent = 'Resposta Correta';
                document.getElementById('popup-msg').innerHTML = `Pontuação computada: <strong>+${pontosGanhos} pontos</strong>.`;
            } else {
                icon.className = 'popup-icon erro'; iconeFA.className = 'fa-solid fa-xmark';
                document.getElementById('popup-titulo').textContent = 'Resposta Incorreta';
                document.getElementById('popup-msg').innerHTML = `A alternativa correta era: <strong>${textoCorreta}</strong>`;
            }

            const btn = document.getElementById('btn-proximo');
            if (isUltima) {
                btn.className = 'btn-popup finalizar';
                btn.innerHTML = 'Finalizar e Ver Resultado <i class="fa-solid fa-flag-checkered"></i>';
            } else {
                btn.className = 'btn-popup proximo';
                btn.innerHTML = 'Avançar <i class="fa-solid fa-arrow-right"></i>';
            }

            document.getElementById('popup-overlay').classList.add('show');
        }

        function proximaPergunta() {
            document.getElementById('popup-overlay').classList.remove('show');
            atual++;
            if (atual < TOTAL) setTimeout(renderPergunta, 200);
            else finalizarQuiz();
        }

        function finalizarQuiz() {
            clearInterval(timer);
            document.getElementById('card-pergunta').style.display = 'none';
            document.querySelector('.progress-wrap').style.display = 'none';
            document.querySelector('.timer-wrap').style.display    = 'none';

            const pct = Math.round((acertos / TOTAL) * 100);
            let titulo = 'Desempenho Insuficiente', sub = 'Recomenda-se revisar o conteúdo programático para futuras avaliações.';
            if (pct >= 80)      { titulo = 'Desempenho Excelente';     sub = 'Resultado exemplar. Excelente aproveitamento do conteúdo.'; }
            else if (pct >= 50) { titulo = 'Desempenho Satisfatório';  sub = 'Critérios mínimos atingidos com sucesso.'; }

            document.getElementById('res-titulo').textContent    = titulo;
            document.getElementById('res-sub').textContent       = sub;
            document.getElementById('res-pontuacao').textContent = pontos;
            document.getElementById('res-acertos').textContent   = acertos;
            const tempoTotalSegundos = Math.max(1, Math.round((Date.now() - inicioQuizMs) / 1000));

            document.getElementById('res-erros').textContent     = erros;
            document.getElementById('res-tempo').textContent     = formatarTempo(tempoTotalSegundos);
            
            document.getElementById('tela-resultado').style.display = 'block';

            fetch(window.location.href, { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_quiz: ID_QUIZ, pontuacao: pontos, tempo_segundos: tempoTotalSegundos })
            })
            .then(res => res.json())
            .then(data => {
                if (data.ok && data.ranking && data.ranking.length > 0) {
                    renderizarGraficoRanking(data.ranking);
                }
            })
            .catch(error => console.error('Erro na requisição do ranking:', error));
        }

        function renderizarGraficoRanking(rankingData) {
            document.getElementById('area-grafico').style.display = 'block';
            const ctx = document.getElementById('rankingChart').getContext('2d');
            const labels = rankingData.map(r => {
                const nomeValido = r.nome ? String(r.nome).trim() : 'Usuário Removido';
                const partes = nomeValido.split(/\s+/).filter(Boolean);
                if (partes.length === 1) return partes[0].slice(0, 10);
                return `${partes[0]} ${partes[1].charAt(0)}.`;
            });
            const nomesCompletos = rankingData.map(r => r.nome ? String(r.nome).trim() : 'UsuÃ¡rio Removido');
            const dataPts = rankingData.map(r => r.max_pontuacao);
            const tempos = rankingData.map(r => {
                const tempo = Number(r.melhor_tempo || 0);
                return tempo >= 999999 ? 0 : tempo;
            });
            
            // Puxa dinamicamente a cor do texto baseada no tema atual!
            const isDark = document.documentElement.classList.contains('dark-mode');
            const textColor = isDark ? '#ffffff' : '#1e293b'; 
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pontuação Obtida',
                        data: dataPts,
                        backgroundColor: '#2e6ef5',
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#181c27',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return nomesCompletos[context[0].dataIndex] || '';
                                },
                                label: function(context) {
                                    const tempo = tempos[context.dataIndex] ? ` - ${formatarTempo(tempos[context.dataIndex])}` : '';
                                    return context.parsed.y + ' pontos' + tempo;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: textColor },
                            grid: { color: gridColor }
                        },
                        x: {
                            ticks: {
                                color: textColor,
                                maxRotation: 0,
                                autoSkip: false,
                                padding: 10,
                                font: { family: "'DM Sans', sans-serif", size: 11, weight: '600' }
                            },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        const inicioQuizMs = Date.now();
        renderPergunta();
    </script>
</body>
</html>
