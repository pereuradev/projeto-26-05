<?php
session_start();
require_once 'conexao.php';

// Proteção: Apenas alunos logados
if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

// Pega o ID do aluno logado (ajuste se a sua sessão chamar apenas 'id')
$id_aluno = $_SESSION['user_id'] ?? $_SESSION['id']; 

try {
    // Busca o histórico fazendo JOIN com a tabela de quizzes para pegar o título
    // E um JOIN com usuarios para pegar o nome do professor
    $sql = "
        SELECT 
            p.pontuacao,
            p.data_jogo,
            q.titulo AS nome_quiz,
            u.nome AS nome_professor
        FROM pontuacoes p
        INNER JOIN quizzes q ON p.id_quiz = q.id
        LEFT JOIN usuarios u ON q.id_professor = u.id
        WHERE p.id_aluno = :id_aluno
        ORDER BY p.data_jogo DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_aluno' => $id_aluno]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $historico = [];
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
    <title>Córtex - Meu Histórico</title>
    <link rel="stylesheet" href="TELA_INICIO_PROF.css"> <link rel="stylesheet" href="HISTORICO_ALUNO.css">  </head>
<body>

    <div class="shape circle-green"></div>
    <div class="shape square-blue"></div>
    <div class="shape circle-orange"></div>

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
                <button class="btn" onclick="window.location.href='MEUS_QUIZZES_ALUNO.php'">Meus Quizzes</button>
                <button class="btn active">Histórico</button>
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
            <h1>Meu Histórico</h1>
            <p class="subtitle">Acompanhe seu desempenho e as notas das suas atividades anteriores.</p>
        </header>

        <div class="historico-container">
            <?php if (empty($historico)): ?>
                <div class="empty-state">
                    <h2>Nenhum quiz jogado ainda</h2>
                    <p>Parece que você ainda não respondeu a nenhum quiz. Vá para a aba "Meus Quizzes" e comece a jogar!</p>
                    <a href="MEUS_QUIZZES_ALUNO.php" class="btn-voltar">Ver Quizzes Disponíveis</a>
                </div>
            <?php else: ?>
                <div class="historico-list">
                    <?php foreach ($historico as $item): 
                        // Define a cor da nota baseada no valor (supondo nota de 0 a 100)
                        $nota = (int)$item['pontuacao'];
                        $corNota = 'nota-baixa'; // Vermelho (padrão < 50)
                        if ($nota >= 70) {
                            $corNota = 'nota-alta'; // Verde
                        } elseif ($nota >= 50) {
                            $corNota = 'nota-media'; // Amarelo/Laranja
                        }
                    ?>
                        <div class="historico-card">
                            <div class="historico-info">
                                <h3><?php echo htmlspecialchars($item['nome_quiz']); ?></h3>
                                <p>Prof. <?php echo htmlspecialchars($item['nome_professor'] ?? 'Desconhecido'); ?></p>
                                <span class="data-jogo">
                                    Jogado em: <?php echo date('d/m/Y \à\s H:i', strtotime($item['data_jogo'])); ?>
                                </span>
                            </div>
                            <div class="historico-score <?php echo $corNota; ?>">
                                <span class="score-valor"><?php echo $nota; ?></span>
                                <span class="score-label">Pontos</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
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