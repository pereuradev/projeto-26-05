<?php
session_start();
if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
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
    <title>Córtex - Criar Novo Quiz</title>
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
                <button class="btn active" onclick="window.location.href='CRIAR_QUIZ.php'">Criar Quiz</button>
                <button class="btn" onclick="window.location.href='Resultados.php'">Resultados</button>
                <button class="btn" onclick="window.location.href='QUIZZES_CRIADOS.php'">Meus Quizzes</button>
                <button class="btn" onclick="window.location.href='CONFIGURA.php'">Configurações</button>
                <button class="btn" onclick="window.location.href='logout.php'" style="color: #E94B3C;">Sair</button>
            </nav>
        </div>
        <button class="theme-toggle" id="toggle-theme"><span id="theme-label">Tema Escuro</span></button>
    </aside>

    <main class="container">
        <form action="salvar_quiz.php" method="POST" id="quizForm">
            <header class="header">
                <h1>Criar Novo Quiz</h1>
            </header>

            <div class="card basic-config-card">
                <div class="grid-2-col">
                    <div>
                        <label class="form-label">Título do Quiz</label>
                        <input type="text" name="quiz_title" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Tempo por Questão</label>
                        <select name="quiz_time" class="form-select">
                            <option value="30" selected>30 segundos</option>
                            <option value="60">60 segundos</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="container-perguntas">
                <div class="card card-pergunta" data-index="1">
                    <div class="header-pergunta">
                        <span class="badge badge-success numero-badge">Questão 1</span>

                        <div class="acoes-header-pergunta">
                            <div class="dificuldade-area">
                                <label class="form-label" style="margin:0;">Nível:</label>
                                <select name="dificuldade[1]" class="form-select" style="padding: 5px 10px; font-size: 13px;">
                                    <option value="Fácil">Fácil</option>
                                    <option value="Intermediário" selected>Intermediário</option>
                                    <option value="Avançado">Avançado</option>
                                </select>
                            </div>

                            <button type="button" class="btn-excluir" onclick="excluirPergunta(this)" style="display: none;">
                                <i class="fa-solid fa-trash-can"></i> Excluir
                            </button>
                        </div>
                    </div>

                    <div class="body-pergunta">
                        <div class="enunciado-area">
                            <label class="form-label">Enunciado da Pergunta</label>
                            <input type="text" name="enunciado[1]" class="form-input input-enunciado" required>
                        </div>

                        <div class="grid-alternativas">
                            <div class="box-alternativa box-laranja">
                                <span class="circle-letter badge-laranja">A</span>
                                <input type="text" name="alternativas[1][A]" class="classe-alternativa" required>
                                <input type="radio" name="correta[1]" value="A" required title="Marcar como correta">
                            </div>
                            <div class="box-alternativa box-azul">
                                <span class="circle-letter badge-azul">B</span>
                                <input type="text" name="alternativas[1][B]" class="classe-alternativa" required>
                                <input type="radio" name="correta[1]" value="B" title="Marcar como correta">
                            </div>
                            <div class="box-alternativa box-verde">
                                <span class="circle-letter badge-verde">C</span>
                                <input type="text" name="alternativas[1][C]" class="classe-alternativa" required>
                                <input type="radio" name="correta[1]" value="C" title="Marcar como correta">
                            </div>
                            <div class="box-alternativa box-amarelo">
                                <span class="circle-letter badge-amarelo">D</span>
                                <input type="text" name="alternativas[1][D]" class="classe-alternativa" required>
                                <input type="radio" name="correta[1]" value="D" title="Marcar como correta">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="botoes-acoes">
                <button type="button" class="btn-primary btn-azul-flat" onclick="adicionarPergunta()">Nova Pergunta</button>
                <button type="submit" class="btn-primary">Salvar Quiz</button>
            </div>
        </form>
    </main>

    <script src="mudar-tema.js"></script>
    <script>
        let qtdPerguntas = 1;

        function adicionarPergunta() {
            qtdPerguntas++;
            const container     = document.getElementById('container-perguntas');
            const novaPergunta  = document.querySelector('.card-pergunta').cloneNode(true);
            const letras        = ['A', 'B', 'C', 'D'];

            novaPergunta.setAttribute('data-index', qtdPerguntas);
            novaPergunta.querySelector('.numero-badge').innerText = `Questão ${qtdPerguntas}`;
            novaPergunta.querySelector('.btn-excluir').style.display = 'flex';
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
            botao.closest('.card-pergunta').remove();
            atualizarNumeracao();
        }

        // renomeia os índices de todos os campos após adicionar ou excluir
        function atualizarNumeracao() {
            const letras = ['A', 'B', 'C', 'D'];
            const cards  = document.querySelectorAll('#container-perguntas .card-pergunta');
            qtdPerguntas = cards.length;

            cards.forEach((card, index) => {
                const i = index + 1;
                card.setAttribute('data-index', i);
                card.querySelector('.numero-badge').innerText              = `Questão ${i}`;
                card.querySelector('.dificuldade-area select').name        = `dificuldade[${i}]`;
                card.querySelector('.input-enunciado').name                = `enunciado[${i}]`;

                card.querySelectorAll('.classe-alternativa').forEach((input, idx) => {
                    input.name = `alternativas[${i}][${letras[idx]}]`;
                });

                card.querySelectorAll('input[type="radio"]').forEach(radio => {
                    radio.name = `correta[${i}]`;
                });

                // esconde o botão excluir quando só resta uma pergunta
                card.querySelector('.btn-excluir').style.display = cards.length === 1 ? 'none' : 'flex';
            });
        }
    </script>
</body>
</html>