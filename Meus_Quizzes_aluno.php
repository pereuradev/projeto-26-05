<?php
session_start();
require_once 'conexao.php';
require_once 'calcular_dificuldade.php'; // ← função de cálculo automático

// Proteção: Apenas alunos logados
if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

try {
    $sql = "
        SELECT 
            q.id,
            q.titulo,
            q.criado_em,
            u.nome                  AS nome_professor,
            COUNT(DISTINCT p.id)   AS total_perguntas,
            AVG(p.tempo_limite)    AS media_tempo,
            COUNT(DISTINCT pt.id)  AS total_jogadas
        FROM quizzes q
        LEFT JOIN usuarios   u  ON u.id      = q.id_professor
        LEFT JOIN perguntas  p  ON p.id_quiz = q.id
        LEFT JOIN pontuacoes pt ON pt.id_quiz = q.id
    ";

    $params = [];

    if ($busca !== '') {
        $sql .= " WHERE q.titulo LIKE ?";
        $params[] = '%' . $busca . '%';
    }

    $sql .= " GROUP BY q.id ORDER BY q.criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcula a dificuldade dinamicamente para cada quiz
    foreach ($quizzes as &$quiz) {
        $total      = (int)$quiz['total_perguntas'];
        $mediaTempo = (float)($quiz['media_tempo'] ?? 30);
        $scoreP = scorePerguntas($total);
        $scoreT = scoreTempo($mediaTempo);
        $quiz['Dificuldade'] = scoreToDificuldade(max($scoreP, $scoreT));
    }
    unset($quiz);

} catch (Exception $e) {
    $quizzes = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') document.documentElement.classList.add('dark-mode');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Córtex - Meus Quizzes</title>
    <link rel="stylesheet" href="TELA_INICIO_PROF.css">
    <link rel="stylesheet" href="Meus_Quizzes_aluno.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <button class="btn" onclick="window.location.href='PAGINA_ALUNO.php'">Menu Inicial</button>
                <button class="btn active">Meus Quizzes</button>
                <button class="btn" onclick="window.location.href='HISTORICO_ALUNO.php'">Histórico</button>
                <button class="btn" onclick="window.location.href='CONFIGURA_ALUNO.php'">Configurações</button>
                <button class="btn btn-sair" onclick="window.location.href='logout.php'">Sair</button>
            </nav>
        </div>
        <button class="theme-toggle" id="toggle-theme">
            <span id="theme-label">Tema Escuro</span>
        </button>
    </aside>

    <main class="container">
        <header class="header">
            <h1>Meus Quizzes</h1>
            <p class="subtitle">Encontre e responda os quizzes disponibilizados pelos seus professores.</p>
        </header>

        <!-- Barra de Pesquisa -->
        <form class="search-bar" method="GET" action="MEUS_QUIZZES_ALUNO.php">
            <div class="search-input-wrap">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="busca" class="search-input"
                       placeholder="Pesquisar quiz pelo título..."
                       value="<?php echo htmlspecialchars($busca); ?>"
                       autocomplete="off">
            </div>
            <button type="submit" class="search-btn">
                <i class="fa-solid fa-magnifying-glass"></i> Buscar
            </button>
            <?php if ($busca !== ''): ?>
                <a href="MEUS_QUIZZES_ALUNO.php" class="search-clear" title="Limpar pesquisa">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>

        <!-- Resultado da busca -->
        <?php if ($busca !== ''): ?>
            <p class="search-result-info">
                <?php echo count($quizzes); ?> resultado(s) para
                "<strong><?php echo htmlspecialchars($busca); ?></strong>"
            </p>
        <?php endif; ?>

        <!-- Grid de Quizzes -->
        <?php if (empty($quizzes)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-folder-open"></i></div>
                <?php if ($busca !== ''): ?>
                    <h2>Nenhum resultado encontrado</h2>
                    <p>Não há quiz com o título "<strong><?php echo htmlspecialchars($busca); ?></strong>".</p>
                    <a href="MEUS_QUIZZES_ALUNO.php" class="btn-voltar">Limpar Pesquisa</a>
                <?php else: ?>
                    <h2>Nenhum quiz disponível</h2>
                    <p>Ainda não há quizzes publicados pelos seus professores. Volte mais tarde!</p>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="quiz-grid">
                <?php foreach ($quizzes as $quiz):
                    $dif   = $quiz['Dificuldade'];
                    $total = (int)$quiz['total_perguntas'];
                    $media = round((float)$quiz['media_tempo']);

                    if (strcasecmp($dif, 'Fácil') === 0) {
                        $difClass = 'dif-facil';
                        $difIcon  = 'fa-seedling';
                    } elseif (strcasecmp($dif, 'Avançado') === 0) {
                        $difClass = 'dif-avancado';
                        $difIcon  = 'fa-fire';
                    } else {
                        $difClass = 'dif-medio';
                        $difIcon  = 'fa-bolt';
                    }
                ?>
                    <div class="quiz-card">

                        <!-- Topo colorido por dificuldade -->
                        <div class="card-top <?php echo $difClass; ?>"></div>

                        <div class="card-body">

                            <!-- Badge dificuldade -->
                            <span class="badge-dif <?php echo $difClass; ?>"
                                  title="<?php echo "{$total} pergunta(s) · {$media}s média por pergunta"; ?>">
                                <i class="fa-solid <?php echo $difIcon; ?>"></i>
                                <?php echo htmlspecialchars($dif); ?>
                            </span>

                            <!-- Título -->
                            <h3 class="card-titulo"><?php echo htmlspecialchars($quiz['titulo']); ?></h3>

                            <!-- Professor -->
                            <p class="card-professor">
                                <i class="fa-solid fa-chalkboard-user"></i>
                                <?php echo htmlspecialchars($quiz['nome_professor'] ?? 'Professor'); ?>
                            </p>

                            <!-- Métricas -->
                            <div class="card-meta">
                                <div class="meta-item">
                                    <i class="fa-solid fa-clipboard-question"></i>
                                    <span><?php echo $total; ?> perguntas</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-regular fa-clock"></i>
                                    <span><?php echo $media; ?>s/pergunta</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-solid fa-users"></i>
                                    <span><?php echo (int)$quiz['total_jogadas']; ?> jogadas</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa-regular fa-calendar"></i>
                                    <span><?php echo date('d/m/Y', strtotime($quiz['criado_em'])); ?></span>
                                </div>
                            </div>

                            <!-- Botão -->
                            <a href="JOGAR_QUIZ.php?id=<?php echo $quiz['id']; ?>" class="btn-jogar">
                                <i class="fa-solid fa-play"></i> Jogar Agora
                            </a>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        (function () {
            const toggleBtn  = document.getElementById('toggle-theme');
            const themeLabel = document.getElementById('theme-label');
            const logoEl     = document.getElementById('logo');

            let temaAtual = localStorage.getItem('theme') || 'light';

            function aplicarEstadoVisual(tema) {
                document.documentElement.setAttribute('data-theme', tema);
                if (tema === 'dark') {
                    document.documentElement.classList.add('dark-mode');
                    if (themeLabel) themeLabel.textContent = 'Tema Claro';
                    if (logoEl && logoEl.dataset.dark)  logoEl.src = logoEl.dataset.dark;
                } else {
                    document.documentElement.classList.remove('dark-mode');
                    if (themeLabel) themeLabel.textContent = 'Tema Escuro';
                    if (logoEl && logoEl.dataset.light) logoEl.src = logoEl.dataset.light;
                }
            }

            aplicarEstadoVisual(temaAtual);

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    temaAtual = temaAtual === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', temaAtual);
                    aplicarEstadoVisual(temaAtual);
                });
            }
        })();
    </script>
</body>
</html>