<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Proteção: Só alunos logados entram aqui
if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

require_once 'conexao.php';

$totalQuizzesDisponiveis = 0;
$totalTentativas = 0;
$melhorPontuacao = 0;

try {
    $totalQuizzesDisponiveis = (int)$pdo->query("SELECT COUNT(*) FROM quizzes")->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pontuacoes WHERE id_aluno = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalTentativas = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(MAX(pontuacao), 0) FROM pontuacoes WHERE id_aluno = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $melhorPontuacao = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $totalQuizzesDisponiveis = $totalTentativas = $melhorPontuacao = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <script>
        // Aplica o tema ANTES do body renderizar, evitando flash
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') document.documentElement.classList.add('dark-mode');
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Aluno | Córtex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="PAGINA_ALUNO.css?v=2"> </head>
<body>
    <div class="shape circle-green"></div>
    <div class="shape square-blue"></div>
    <div class="shape circle-orange"></div>

    <aside class="sidebar">
        <div>
            <div class="logo-area">
                <img id="logo"
                     src="LOGO-Cortex(maior).png"
                     data-light="LOGO-Cortex(maior).png"
                     data-dark="Logo-Cortex-Branca.png"
                     alt="Logo Córtex">
            </div>
            <nav class="sidebar-menu">
                <button class="btn active">
                  Menu Inicial
                </button>
                <button class="btn" onclick="window.location.href='MEUS_QUIZZES_ALUNO.php'">
                  Meus Quizzes
                </button>
                <button class="btn" onclick="window.location.href='HISTORICO_ALUNO.php'">
                   Histórico
                </button>
                <button class="btn" onclick="window.location.href='CONFIGURA_ALUNO.php'">
                     Configurações
                </button>
                <button class="btn" onclick="window.location.href='logout.php'" style="color: #E94B3C;">
                     Sair
                </button>
            </nav>
        </div>
        <button class="theme-toggle" id="toggle-theme">
            <span id="theme-label">Tema Escuro</span>
        </button>
    </aside>

    <main class="container">
        <header class="header">
            <h1>Olá, <?php echo htmlspecialchars($_SESSION['user_nome']); ?></h1>
            <p class="subtitle">
                Escolha um quiz para responder ou consulte as suas notas.
            </p>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $totalQuizzesDisponiveis; ?></span>
                <span class="stat-label">Quizzes disponiveis</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $totalTentativas; ?></span>
                <span class="stat-label">Tentativas feitas</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $melhorPontuacao; ?></span>
                <span class="stat-label">Melhor pontuacao</span>
            </div>
        </section>

        <section class="dashboard">
            <div class="card">
                <span class="badge badge-success">Disponível</span>
                <h2>Responder Quiz</h2>
                <p>Entre na lista de quizzes disponiveis e responda as atividades abertas.</p>
                <button class="btn-primary" onclick="window.location.href='MEUS_QUIZZES_ALUNO.php'">
                    Ver Quizzes
                </button>
            </div>

            <div class="card card-azul">
                <span class="badge badge-info">Resultados</span>
                <h2>Meu Histórico</h2>
                <p>Veja as pontuacoes que voce ja fez e a data de cada tentativa.</p>
                <button class="btn-primary btn-azul" onclick="window.location.href='HISTORICO_ALUNO.php'">
                    Ver Histórico
                </button>
            </div>
        </section>
        
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

            // Sincroniza label/logo ao carregar a página
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
