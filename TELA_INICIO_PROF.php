<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Proteção de segurança: Só professores logados entram aqui
if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'professor') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

require_once 'conexao.php';

$totalQuizzes = 0;
$totalTentativas = 0;
$totalAlunos = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE id_professor = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $totalQuizzes = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(p.id)
        FROM pontuacoes p
        INNER JOIN quizzes q ON q.id = p.id_quiz
        WHERE q.id_professor = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $totalTentativas = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id_aluno)
        FROM pontuacoes p
        INNER JOIN quizzes q ON q.id = p.id_quiz
        WHERE q.id_professor = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $totalAlunos = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    $totalQuizzes = $totalTentativas = $totalAlunos = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Professor | Córtex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="TELA_INICIO_PROF.css?v=2">
</head>

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
                <button class="btn active" onclick="window.location.href='TELA_INICIO_PROF.php'">Menu Inicial</button>
                <button class="btn" onclick="window.location.href='CRIAR_QUIZ.php'">Criar Quiz</button>
                <button class="btn" onclick="window.location.href='Resultados.php'">Resultados</button>
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
            <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_nome']); ?>!</h1>
            <p class="subtitle">
                Escolha uma opcao para criar, acompanhar ou organizar os seus quizzes.
            </p>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $totalQuizzes; ?></span>
                <span class="stat-label">Quizzes criados</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $totalTentativas; ?></span>
                <span class="stat-label">Tentativas recebidas</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $totalAlunos; ?></span>
                <span class="stat-label">Alunos no ranking</span>
            </div>
        </section>

        <section class="dashboard">
            <div class="card">
                <span class="badge badge-success">Novo</span>
                <h2>Criar Novo Quiz</h2>
                <p>Monte perguntas, defina alternativas e salve um novo quiz para os alunos responderem.</p>
                <button class="btn-primary" onclick="window.location.href='CRIAR_QUIZ.php'">Criar Quiz</button>
            </div>
            <div class="card">
                <span class="badge badge-info">Gerenciar</span>
                <h2>Quizzes Criados</h2>
                <p>Veja os quizzes salvos, edite informacoes, confira resultados ou exclua atividades.</p>
                <button class="btn-primary btn-secondary" onclick="window.location.href='QUIZZES_CRIADOS.php'">Ver Quizzes</button>
            </div>
        </section>
    </main>

    <script src="mudar-tema.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (isset($_SESSION['status_quiz'])): ?>
        <script>
            const status = "<?php echo $_SESSION['status_quiz']; ?>";
            if (status === 'sucesso') {
                Swal.fire({
                    icon: 'success',
                    title: 'Quiz Cadastrado!',
                    text: 'As perguntas e alternativas foram salvas com sucesso no banco de dados.',
                    confirmButtonColor: '#2FB399',
                    confirmButtonText: 'Excelente'
                });
            } else if (status === 'erro') {
                Swal.fire({
                    icon: 'error',
                    title: 'Falha no Cadastro',
                    text: 'Não foi possível salvar o seu Quiz. Verifique os campos e tente novamente.',
                    confirmButtonColor: '#E94B3C',
                    confirmButtonText: 'Fechar'
                });
            }
        </script>
        <?php unset($_SESSION['status_quiz']); ?>
    <?php endif; ?>
</body>

</html>
