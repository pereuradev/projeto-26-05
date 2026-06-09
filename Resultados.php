<?php
// resultados.php
session_start();
if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

require_once 'conexao.php';

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

garantirColunaTempoPontuacoes($pdo);

// 1. Buscar todos os quizzes criados
$stmtQuizzes = $pdo->query("SELECT id, titulo FROM quizzes ORDER BY id DESC");
$quizzes = $stmtQuizzes->fetchAll(PDO::FETCH_ASSOC);

$dadosGraficos = [];

// 2. Buscar o Top 10 pontuações de cada quiz (Garantindo um registro único por aluno)
foreach ($quizzes as $quiz) {
    $id_quiz = $quiz['id'];
    
    $stmtRanking = $pdo->prepare("
        SELECT
            u.nome,
            MAX(p.pontuacao) AS maior_pontuacao,
            MIN(CASE WHEN p.pontuacao = melhores.maior_pontuacao THEN COALESCE(p.tempo_segundos, 999999) END) AS melhor_tempo
        FROM pontuacoes p
        INNER JOIN (
            SELECT id_aluno, MAX(pontuacao) AS maior_pontuacao
            FROM pontuacoes
            WHERE id_quiz = :id_quiz_melhores
            GROUP BY id_aluno
        ) melhores ON melhores.id_aluno = p.id_aluno
        JOIN usuarios u ON p.id_aluno = u.id
        WHERE p.id_quiz = :id_quiz
        GROUP BY p.id_aluno, u.nome
        ORDER BY maior_pontuacao DESC, melhor_tempo ASC, MIN(CASE WHEN p.pontuacao = melhores.maior_pontuacao THEN p.data_jogo END) ASC, u.nome ASC
        LIMIT 10
    ");
    $stmtRanking->execute([
        'id_quiz_melhores' => $id_quiz,
        'id_quiz' => $id_quiz
    ]);
    $ranking = $stmtRanking->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = [];
    $valores = [];
    $tempos = [];
    
    foreach ($ranking as $posicao) {
        $labels[] = $posicao['nome'];
        $valores[] = (int)$posicao['maior_pontuacao'];
        $melhorTempo = (int)$posicao['melhor_tempo'];
        $tempos[] = $melhorTempo >= 999999 ? 0 : $melhorTempo;
    }
    
    $dadosGraficos[$id_quiz] = [
        'titulo' => $quiz['titulo'],
        'labels' => $labels,
        'valores' => $valores,
        'tempos' => $tempos
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <script>
        // Mantém o tema sincronizado antes do carregamento do DOM para evitar piscadas fluidas
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') document.documentElement.classList.add('dark-mode');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Córtex - Resultados</title>
    <link rel="stylesheet" href="TELA_INICIO_PROF.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Alinhamento do layout de colunas laterais da plataforma */
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        /* Estilização dos Gráficos com base nos seus Cards padrão */
        .grid-resultados {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .resultado-chart-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 15px;
            position: relative;
        }

        .resultado-chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 14px;
        }

        .chart-header h3 {
            color: var(--texto);
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.3;
        }

        /* Status customizados seguindo seu padrão de badges */
        .badge-status {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-ativo {
            background: rgba(47, 179, 153, 0.15);
            color: var(--verde);
        }

        .status-vazio {
            background: rgba(243, 156, 18, 0.15);
            color: #f39c12;
        }

        .canvas-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        @media(max-width: 768px) {
            body { flex-direction: column; }
            .grid-resultados { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div>
            <div class="logo-area">
                <img id="logo" src="LOGO-Cortex(maior).png"
                    data-light="LOGO-Cortex(maior).png"
                    data-dark="Logo-Cortex-Branca.png"
                    alt="Logo Córtex">
            </div>
            <nav class="sidebar-menu">
                <button class="btn" onclick="window.location.href='TELA_INICIO_PROF.php'">Menu Inicial</button>
                <button class="btn" onclick="window.location.href='CRIAR_QUIZ.php'">Criar Quiz</button>
                <button class="btn active">Resultados</button>
                <button class="btn" onclick="window.location.href='QUIZZES_CRIADOS.php'">Meus Quizzes</button>
                <button class="btn" onclick="window.location.href='CONFIGURA.php'">Configurações</button>
                <button class="btn" onclick="window.location.href='logout.php'" style="color: #E94B3C;">Sair</button>
            </nav>
        </div>
        <button class="theme-toggle" id="toggle-theme">
            <span id="theme-label">Tema Escuro</span>
        </button>
    </aside>

    <main class="container">
        <header class="header">
            <h1>Resultados dos Quizzes</h1>
            <p class="subtitle">Acompanhe o ranking dos 10 melhores alunos. Em caso de empate na pontuacao, vence quem respondeu em menos tempo.</p>
        </header>

        <div class="grid-resultados">
            <?php foreach ($dadosGraficos as $id_quiz => $info): ?>
                <?php $possuiPartidas = !empty($info['labels']); ?>
                
                <div class="resultado-chart-card">
                    <div class="chart-header">
                        <h3><?php echo htmlspecialchars($info['titulo']); ?></h3>
                        <span class="badge-status <?php echo $possuiPartidas ? 'status-ativo' : 'status-vazio'; ?>">
                            <i class="fa-solid <?php echo $possuiPartidas ? 'fa-chart-simple' : 'fa-chart-bar'; ?>"></i>
                            <?php echo $possuiPartidas ? 'Com dados' : 'Sem dados'; ?>
                        </span>
                    </div>
                    
                    <div class="canvas-container">
                        <canvas id="chart-<?php echo $id_quiz; ?>"></canvas>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Captura dinamicamente as cores do seu tema CSS para aplicar nos gráficos de colunas
        const coreTokens = getComputedStyle(document.documentElement);
        const corVerde = coreTokens.getPropertyValue('--verde').trim() || '#2fb399';
        const corTexto = coreTokens.getPropertyValue('--texto').trim() || '#333333';
        const corBorder = coreTokens.getPropertyValue('--border').trim() || 'rgba(0,0,0,0.1)';

        // Dados convertidos do PHP
        const dadosGraficos = <?php echo json_encode($dadosGraficos); ?>;

        // Renderizador Chart.js
        function formatarTempo(totalSegundos) {
            const minutos = Math.floor(totalSegundos / 60);
            const segundos = totalSegundos % 60;

            if (minutos <= 0) return `${segundos}s`;
            return `${minutos}min ${String(segundos).padStart(2, '0')}s`;
        }

        Object.keys(dadosGraficos).forEach(id => {
            const ctx = document.getElementById(`chart-${id}`).getContext('2d');
            const quizInfo = dadosGraficos[id];
            const temDados = quizInfo.labels.length > 0;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: temDados ? quizInfo.labels : ['Ninguém jogou ainda'],
                    datasets: [{
                        label: 'Maior Pontuação',
                        data: temDados ? quizInfo.valores : [0],
                        backgroundColor: temDados ? corVerde : 'rgba(148, 163, 184, 0.08)',
                        borderColor: temDados ? corVerde : 'rgba(148, 163, 184, 0.2)',
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: temDados ? 26 : 45
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: temDados,
                            callbacks: {
                                label: function(context) {
                                    const tempo = quizInfo.tempos?.[context.dataIndex]
                                        ? ` - ${formatarTempo(Number(quizInfo.tempos[context.dataIndex]))}`
                                        : '';
                                    return context.parsed.y + ' pontos' + tempo;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: temDados ? Math.max(...quizInfo.valores) + 20 : 100,
                            grid: { color: corBorder },
                            ticks: { color: '#888', font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#888', font: { weight: '600', size: 12 } }
                        }
                    }
                }
            });
        });

        // Evento extra: se o botão de tema disparar um reload, os eixos recalculam a cor de contraste
        document.getElementById('toggle-theme')?.addEventListener('click', () => {
            setTimeout(() => window.location.reload(), 150);
        });
    </script>

    <script src="mudar-tema.js"></script>
</body>

</html>
