<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

$idProfessor = $_SESSION['user_id'];
$idQuiz      = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idQuiz === 0) {
    header("Location: QUIZZES_CRIADOS.php");
    exit;
}

try {
    // garante que o quiz existe e pertence ao professor logado
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND id_professor = ?");
    $stmt->execute([$idQuiz, $idProfessor]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        header("Location: QUIZZES_CRIADOS.php");
        exit;
    }

    $stmtPerguntas = $pdo->prepare("SELECT * FROM perguntas WHERE id_quiz = ? ORDER BY id ASC");
    $stmtPerguntas->execute([$idQuiz]);
    $perguntas_bd = $stmtPerguntas->fetchAll(PDO::FETCH_ASSOC);

    // busca as alternativas de cada pergunta e embute no array
    $perguntas = [];
    foreach ($perguntas_bd as $pergunta) {
        $stmtAlt = $pdo->prepare("SELECT * FROM alternativas WHERE id_pergunta = ? ORDER BY id ASC");
        $stmtAlt->execute([$pergunta['id']]);
        $pergunta['opcoes'] = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);
        $perguntas[] = $pergunta;
    }
} catch (Exception $e) {
    die("Erro ao carregar o quiz: " . $e->getMessage());
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
    <title>Córtex - Editar Quiz</title>
    <link rel="stylesheet" href="CRIAR_QUIZ.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .theme-toggle {
            border: 1px solid var(--border);
            background: var(--card-bg);
            color: var(--texto);
            padding: 15px 18px;
            border-radius: 16px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: left;
            width: 100%;
            margin-top: auto;
        }

        .theme-toggle:hover {
            background: rgba(47, 179, 153, 0.12);
            color: var(--verde);
            border-color: var(--verde);
        }

        .btn-excluir {
            background: transparent;
            border: 1px solid rgba(233, 75, 60, 0.4);
            color: var(--laranja);
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-excluir:hover {
            background: var(--laranja);
            color: white;
            box-shadow: 0 4px 12px rgba(233, 75, 60, 0.2);
        }

        .acoes-header-pergunta {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .dificuldade-area {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div>
            <div class="logo-area">
                <img id="logo" src="LOGO-Cortex(maior).png" data-light="LOGO-Cortex(maior).png" data-dark="Logo-Cortex-Branca.png" alt="Logo">
            </div>
            <nav class="sidebar-menu">
                <button class="btn" onclick="window.location.href='TELA_INICIO_PROF.php'">Menu Inicial</button>
                <button class="btn" onclick="window.location.href='CRIAR_QUIZ.php'">Criar Quiz</button>
                <button class="btn active" onclick="window.location.href='QUIZZES_CRIADOS.php'">Meus Quizzes</button>
                <button class="btn" onclick="window.location.href='CONFIGURA.php'">Configurações</button>
                <button class="btn" onclick="window.location.href='logout.php'" style="color: #E94B3C;">Sair</button>
            </nav>
        </div>
        <button class="theme-toggle" id="toggle-theme"><span id="theme-label">Tema Escuro</span></button>
    </aside>

    <main class="container">
        <form action="atualizar_quiz.php" method="POST" id="quizForm">
            <input type="hidden" name="id_quiz" value="<?php echo $idQuiz; ?>">

            <header class="header">
                <h1>Editar Quiz</h1>
                <p class="subtitle">Faça alterações no conteúdo e nas perguntas do seu quiz.</p>
            </header>

            <div class="card basic-config-card">
                <div class="grid-2-col">
                    <div>
                        <label class="form-label">Título do Quiz</label>
                        <input type="text" name="quiz_title" class="form-input" required value="<?php echo htmlspecialchars($quiz['titulo']); ?>">
                    </div>
                    <div>
                        <label class="form-label">Tempo por Questão</label>
                        <select name="quiz_time" class="form-select">
                            <option value="30" <?php echo (isset($quiz['tempo']) && $quiz['tempo'] == '30') ? 'selected' : ''; ?>>30 segundos</option>
                            <option value="60" <?php echo (isset($quiz['tempo']) && $quiz['tempo'] == '60') ? 'selected' : ''; ?>>60 segundos</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="container-perguntas">
                <?php foreach ($perguntas as $index => $p):
                    $num = $index + 1;

                    // fallback para alternativas que possam não existir no banco
                    $altA = $p['opcoes'][0] ?? ['texto_alternativa' => '', 'is_correta' => 0];
                    $altB = $p['opcoes'][1] ?? ['texto_alternativa' => '', 'is_correta' => 0];
                    $altC = $p['opcoes'][2] ?? ['texto_alternativa' => '', 'is_correta' => 0];
                    $altD = $p['opcoes'][3] ?? ['texto_alternativa' => '', 'is_correta' => 0];
                ?>
                    <div class="card card-pergunta" data-index="<?php echo $num; ?>">
                        <input type="hidden" name="id_pergunta[<?php echo $num; ?>]" value="<?php echo $p['id']; ?>">

                        <div class="header-pergunta">
                            <span class="badge badge-success numero-badge">Questão <?php echo $num; ?></span>

                            <div class="acoes-header-pergunta">
                                <div class="dificuldade-area">
                                    <label class="form-label" style="margin:0;">Nível:</label>
                                    <select name="dificuldade[<?php echo $num; ?>]" class="form-select" style="padding: 5px 10px; font-size: 13px;">
                                        <option value="Fácil"         <?php echo (isset($p['dificuldade']) && $p['dificuldade'] == 'Fácil')         ? 'selected' : ''; ?>>Fácil</option>
                                        <option value="Intermediário" <?php echo (empty($p['dificuldade']) || $p['dificuldade'] == 'Intermediário') ? 'selected' : ''; ?>>Intermediário</option>
                                        <option value="Avançado"      <?php echo (isset($p['dificuldade']) && $p['dificuldade'] == 'Avançado')      ? 'selected' : ''; ?>>Avançado</option>
                                    </select>
                                </div>

                                <button type="button" class="btn-excluir" onclick="excluirPergunta(this)" style="<?php echo count($perguntas) > 1 ? 'display: flex;' : 'display: none;'; ?>">
                                    <i class="fa-solid fa-trash-can"></i> Excluir
                                </button>
                            </div>
                        </div>

                        <div class="body-pergunta">
                            <div class="enunciado-area">
                                <label class="form-label">Enunciado da Pergunta</label>
                                <input type="text" name="enunciado[<?php echo $num; ?>]" class="form-input input-enunciado" required value="<?php echo htmlspecialchars($p['texto_pergunta'] ?? ''); ?>">
                            </div>

                            <div class="grid-alternativas">
                                <div class="box-alternativa box-laranja">
                                    <span class="circle-letter badge-laranja">A</span>
                                    <input type="text" name="alternativas[<?php echo $num; ?>][A]" class="classe-alternativa" required value="<?php echo htmlspecialchars($altA['texto_alternativa']); ?>">
                                    <input type="radio" name="correta[<?php echo $num; ?>]" value="A" required title="Marcar como correta" <?php echo $altA['is_correta'] == 1 ? 'checked' : ''; ?>>
                                </div>
                                <div class="box-alternativa box-azul">
                                    <span class="circle-letter badge-azul">B</span>
                                    <input type="text" name="alternativas[<?php echo $num; ?>][B]" class="classe-alternativa" required value="<?php echo htmlspecialchars($altB['texto_alternativa']); ?>">
                                    <input type="radio" name="correta[<?php echo $num; ?>]" value="B" title="Marcar como correta" <?php echo $altB['is_correta'] == 1 ? 'checked' : ''; ?>>
                                </div>
                                <div class="box-alternativa box-verde">
                                    <span class="circle-letter badge-verde">C</span>
                                    <input type="text" name="alternativas[<?php echo $num; ?>][C]" class="classe-alternativa" required value="<?php echo htmlspecialchars($altC['texto_alternativa']); ?>">
                                    <input type="radio" name="correta[<?php echo $num; ?>]" value="C" title="Marcar como correta" <?php echo $altC['is_correta'] == 1 ? 'checked' : ''; ?>>
                                </div>
                                <div class="box-alternativa box-amarelo">
                                    <span class="circle-letter badge-amarelo">D</span>
                                    <input type="text" name="alternativas[<?php echo $num; ?>][D]" class="classe-alternativa" required value="<?php echo htmlspecialchars($altD['texto_alternativa']); ?>">
                                    <input type="radio" name="correta[<?php echo $num; ?>]" value="D" title="Marcar como correta" <?php echo $altD['is_correta'] == 1 ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="botoes-acoes">
                <button type="button" class="btn-primary btn-azul-flat" onclick="adicionarPergunta()">Nova Pergunta</button>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </main>

    <script src="mudar-tema.js"></script>
    <script>
        let qtdPerguntas = <?php echo count($perguntas) > 0 ? count($perguntas) : 1; ?>;

        function adicionarPergunta() {
            qtdPerguntas++;
            const container    = document.getElementById('container-perguntas');
            const novaPergunta = document.querySelector('.card-pergunta').cloneNode(true);
            const letras       = ['A', 'B', 'C', 'D'];

            novaPergunta.setAttribute('data-index', qtdPerguntas);
            novaPergunta.querySelector('.numero-badge').innerText = `Questão ${qtdPerguntas}`;
            novaPergunta.querySelector('.btn-excluir').style.display = 'flex';

            // valor "nova" sinaliza para o back-end que é um INSERT, não UPDATE
            const hidden  = novaPergunta.querySelector('input[type="hidden"]');
            hidden.name   = `id_pergunta[${qtdPerguntas}]`;
            hidden.value  = 'nova';

            novaPergunta.querySelector('.dificuldade-area select').name = `dificuldade[${qtdPerguntas}]`;
            novaPergunta.querySelector('.input-enunciado').name  = `enunciado[${qtdPerguntas}]`;
            novaPergunta.querySelector('.input-enunciado').value = '';

            novaPergunta.querySelectorAll('.classe-alternativa').forEach((input, index) => {
                input.name  = `alternativas[${qtdPerguntas}][${letras[index]}]`;
                input.value = '';
            });

            novaPergunta.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.name    = `correta[${qtdPerguntas}]`;
                radio.checked = false;
            });

            container.appendChild(novaPergunta);
            novaPergunta.scrollIntoView({ behavior: 'smooth' });
            atualizarNumeracao();
        }

        function excluirPergunta(botao) {
            const cards = document.querySelectorAll('.card-pergunta');
            if (cards.length > 1) {
                botao.closest('.card-pergunta').remove();
                atualizarNumeracao();
            } else {
                alert("O quiz precisa ter pelo menos uma pergunta.");
            }
        }

        // renomeia os índices de todos os campos após adicionar ou excluir
        function atualizarNumeracao() {
            const letras = ['A', 'B', 'C', 'D'];
            const cards  = document.querySelectorAll('#container-perguntas .card-pergunta');
            qtdPerguntas = cards.length;

            cards.forEach((card, index) => {
                const i = index + 1;
                card.setAttribute('data-index', i);
                card.querySelector('.numero-badge').innerText           = `Questão ${i}`;
                card.querySelector('input[type="hidden"]').name         = `id_pergunta[${i}]`;
                card.querySelector('.dificuldade-area select').name     = `dificuldade[${i}]`;
                card.querySelector('.input-enunciado').name             = `enunciado[${i}]`;

                card.querySelectorAll('.classe-alternativa').forEach((input, idx) => {
                    input.name = `alternativas[${i}][${letras[idx]}]`;
                });

                card.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.name = `correta[${i}]`;
                });

                card.querySelector('.btn-excluir').style.display = cards.length === 1 ? 'none' : 'flex';
            });
        }
    </script>
</body>
</html>