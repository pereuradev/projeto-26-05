<?php
session_start();
require_once 'conexao.php';
require_once 'calcular_dificuldade.php'; // ← função de cálculo automático

// Proteção: Apenas professores logados
if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

$idProfessor = $_SESSION['user_id'];
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

try {
    $sql = "
        SELECT 
            q.id,
            q.titulo,
            q.criado_em,
            COUNT(p.id)        AS total_perguntas,
            AVG(p.tempo_limite) AS media_tempo
        FROM quizzes q
        LEFT JOIN perguntas p ON q.id = p.id_quiz
        WHERE q.id_professor = ?
    ";

    $params = [$idProfessor];

    if ($busca !== '') {
        $sql .= " AND q.titulo LIKE ?";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            margin-top: 10px;
        }

        .search-input {
            flex: 1;
            padding: 14px 18px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--texto);
            font-size: 15px;
            outline: none;
            transition: var(--transition);
        }

        .search-input:focus {
            border-color: var(--verde);
            box-shadow: 0 0 0 3px rgba(47, 179, 153, 0.15);
        }

        .search-btn {
            background: var(--verde);
            color: white;
            padding: 0 24px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            background: #269680;
            box-shadow: 0 4px 12px rgba(47, 179, 153, 0.3);
            transform: translateY(-2px);
        }

        .grid-quizzes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }

        .quiz-card {
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

        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .quiz-header h3 {
            color: var(--texto);
            margin: 0 0 10px 0;
            font-size: 18px;
            line-height: 1.3;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        .quiz-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #888;
            align-items: center;
        }

        .quiz-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .btn-edit {
            flex: 1;
            background: rgba(47, 179, 153, 0.1);
            color: var(--verde);
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-edit:hover {
            background: var(--verde);
            color: white;
        }

        .btn-delete {
            background: rgba(233, 75, 60, 0.1);
            color: var(--laranja);
            padding: 10px 15px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-delete:hover {
            background: var(--laranja);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: var(--texto);
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px dashed var(--border);
        }

        /* Badges de Dificuldade */
        .badge-dif {
            position: static;
            align-self: flex-start;
            max-width: 100%;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .dif-facil {
            background: rgba(47, 179, 153, 0.15);
            color: var(--verde);
        }

        .dif-medio {
            background: rgba(243, 156, 18, 0.15);
            color: #f39c12;
        }

        .dif-avancado {
            background: rgba(233, 75, 60, 0.15);
            color: var(--laranja);
        }

        /* Tooltip explicativo da dificuldade */
        .badge-dif[title] {
            cursor: help;
        }

        /* Indicador de cálculo automático */
        .auto-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 10px;
            color: #aaa;
            margin-top: 4px;
        }

        .auto-badge i {
            font-size: 9px;
        }

        @media(max-width: 768px) {
            .search-container {
                flex-direction: column;
            }

            .search-btn {
                justify-content: center;
                min-height: 46px;
            }

            .grid-quizzes {
                grid-template-columns: 1fr;
            }
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
                <button class="btn" onclick="window.location.href='Resultados.php'">Resultados</button>
                <button class="btn active">Meus Quizzes</button>
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
            <h1>Meus Quizzes</h1>
            <p class="subtitle">Faça a gestão das atividades que você já criou para as suas turmas.</p>
        </header>

        <form class="search-container" method="GET" action="QUIZZES_CRIADOS.php">
            <input type="text" name="busca" class="search-input"
                placeholder="Pesquisar quiz pelo título..."
                value="<?php echo htmlspecialchars($busca); ?>">
            <button type="submit" class="search-btn">
                <i class="fa-solid fa-magnifying-glass"></i> Pesquisar
            </button>
        </form>

        <?php if (empty($quizzes)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-folder-open" style="font-size:40px;color:var(--verde);margin-bottom:15px;"></i>
                <?php if ($busca !== ''): ?>
                    <h2>Nenhum resultado encontrado</h2>
                    <p>Não encontramos nenhum quiz com o título "<strong><?php echo htmlspecialchars($busca); ?></strong>".</p>
                    <button class="btn-primary" style="margin-top:15px;"
                        onclick="window.location.href='QUIZZES_CRIADOS.php'">Limpar Pesquisa</button>
                <?php else: ?>
                    <h2>Nenhum quiz encontrado</h2>
                    <p>Você ainda não criou nenhum quiz. Clique no botão abaixo para começar!</p>
                    <button class="btn-primary" style="margin-top:15px;"
                        onclick="window.location.href='CRIAR_QUIZ.php'">Criar Meu Primeiro Quiz</button>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="grid-quizzes">
                <?php foreach ($quizzes as $quiz):
                    $dif   = $quiz['Dificuldade'];
                    $total = (int)$quiz['total_perguntas'];
                    $media = round((float)$quiz['media_tempo']);

                    if (strcasecmp($dif, 'Fácil') === 0) {
                        $classBadge = 'dif-facil';
                        $icone      = 'fa-seedling';
                    } elseif (strcasecmp($dif, 'Avançado') === 0) {
                        $classBadge = 'dif-avancado';
                        $icone      = 'fa-fire';
                    } else {
                        $classBadge = 'dif-medio';
                        $icone      = 'fa-bolt';
                    }

                    // Tooltip explica como foi calculado
                    $tooltip = "Calculado automaticamente: {$total} pergunta(s) · {$media}s de média por pergunta";
                ?>
                    <div class="quiz-card">

                        <span class="badge-dif <?php echo $classBadge; ?>"
                            title="<?php echo $tooltip; ?>">
                            <i class="fa-solid <?php echo $icone; ?>"></i>
                            <?php echo htmlspecialchars($dif); ?>
                        </span>

                        <div class="quiz-header">
                            <h3><?php echo htmlspecialchars($quiz['titulo']); ?></h3>
                            <div class="quiz-meta">
                                <span>
                                    <i class="fa-solid fa-clipboard-question"></i>
                                    <?php echo $total; ?> Perguntas
                                </span>
                                <span>
                                    <i class="fa-regular fa-clock"></i>
                                    <?php echo $media; ?>s/pergunta
                                </span>
                            </div>
                            <div class="quiz-meta" style="margin-top:6px;">
                                <span>
                                    <i class="fa-regular fa-calendar"></i>
                                    <?php echo date('d/m/Y', strtotime($quiz['criado_em'])); ?>
                                </span>
                                <span class="auto-badge">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    Dificuldade automática
                                </span>
                            </div>
                        </div>

                        <div class="quiz-actions">
                            <button class="btn-edit"
                                onclick="window.location.href='EDITAR_QUIZ.php?id=<?php echo $quiz['id']; ?>'">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </button>
                            <form action="excluir_quiz.php" method="POST"
                                onsubmit="return confirm('Tem certeza que deseja excluir este quiz?');"
                                style="margin:0;">
                                <input type="hidden" name="id_quiz" value="<?php echo $quiz['id']; ?>">
                                <button type="submit" class="btn-delete" title="Excluir">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="mudar-tema.js"></script>
</body>

</html>
