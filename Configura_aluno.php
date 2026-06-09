<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'aluno') {
    header("Location: TELA_DE_LOGIN.php");
    exit;
}

$idUsuario       = $_SESSION['user_id'];
$emailUsuario    = '';
$mensagemSucesso = '';
$mensagemErro    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'alterar_senha') {
        $novaSenha = trim($_POST['nova_senha']);

        if (strlen($novaSenha) < 8) {
            $mensagemErro = "A nova senha deve ter pelo menos 8 caracteres.";
        } else {
            try {
                $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT); // nunca salvar senha pura
                $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")->execute([$senhaHash, $idUsuario]);
                $mensagemSucesso = "Senha atualizada com sucesso!";
            } catch (Exception $e) {
                $mensagemErro = "Erro ao atualizar a senha: " . $e->getMessage();
            }
        }
    }

    if ($_POST['action'] === 'excluir_conta') {
        try {
            $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$idUsuario]);

            // destrói a sessão antes de confirmar pro front
            $_SESSION = [];
            session_destroy();

            echo json_encode(['status' => 'success']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
    $stmt->execute([$idUsuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $emailUsuario = $user ? $user['email'] : 'Erro ao carregar e-mail';
} catch (Exception $e) {
    $emailUsuario = "Erro ao carregar e-mail";
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
    <title>Córtex - Configurações</title>
    <link rel="stylesheet" href="TELA_INICIO_PROF.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="CONFIGURA_ALUNO.css">
</head>
<body>

    <aside class="sidebar">
        <div>
            <div class="logo-area">
                <img id="logo" src="LOGO-Cortex(maior).png" data-light="LOGO-Cortex(maior).png" data-dark="Logo-Cortex-Branca.png" alt="Logo Córtex">
            </div>
            <nav class="sidebar-menu">
                <button class="btn" onclick="window.location.href='PAGINA_ALUNO.php'">Menu Inicial</button>
                <button class="btn" onclick="window.location.href='MEUS_QUIZZES_ALUNO.php'">Meus Quizzes</button>
                <button class="btn" onclick="window.location.href='HISTORICO_ALUNO.php'">Histórico</button>
                <button class="btn active">Configurações</button>
                <button class="btn btn-sair" onclick="window.location.href='logout.php'">Sair</button>
            </nav>
        </div>
        <button class="theme-toggle" id="toggle-theme">
            <span id="theme-label">Tema Escuro</span>
        </button>
    </aside>

    <main class="container">
        <header class="header">
            <h1>Configurações da Conta</h1>
            <p class="subtitle">Gerencie as suas informações pessoais e credenciais de acesso.</p>
        </header>

        <?php if (!empty($mensagemSucesso)): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $mensagemSucesso; ?></div>
        <?php endif; ?>

        <?php if (!empty($mensagemErro)): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $mensagemErro; ?></div>
        <?php endif; ?>

        <section class="config-section">
            <h2 class="config-title"><i class="fa-solid fa-user"></i> Informações Pessoais</h2>
            <form action="#" method="POST">
                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($_SESSION['user_nome']); ?>" disabled>
                    <small style="color:#888; margin-top:5px; display:block;">O nome não pode ser alterado por aqui.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">E-mail de Acesso</label>
                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($emailUsuario); ?>" disabled>
                </div>
            </form>
        </section>

        <section class="config-section">
            <h2 class="config-title"><i class="fa-solid fa-lock"></i> Segurança</h2>
            <form action="CONFIGURA_ALUNO.php" method="POST">
                <input type="hidden" name="action" value="alterar_senha">
                <div class="form-group">
                    <label class="form-label">Nova Senha</label>
                    <div class="password-container">
                        <input type="password" id="nova_senha" name="nova_senha" class="form-input" placeholder="Mínimo de 8 caracteres" required>
                        <i class="fa-solid fa-eye toggle-password" id="btn-toggle-password"></i>
                    </div>
                </div>
                <button type="submit" class="btn-save">Atualizar Senha</button>
            </form>
        </section>

        <section class="config-section" style="border-color: rgba(233, 75, 60, 0.3);">
            <h2 class="config-title" style="color: #E94B3C;"><i class="fa-solid fa-triangle-exclamation"></i> Zona de Perigo</h2>
            <p style="color: var(--texto); margin-bottom: 20px;">Ao excluir a sua conta, todo o seu histórico de quizzes e resultados serão apagados permanentemente.</p>
            <button type="button" class="btn-danger" id="btn-excluir-conta">Excluir Minha Conta</button>
        </section>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // tema — roda em IIFE pra não poluir o escopo global
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

        // mostrar/ocultar senha
        const btnTogglePassword = document.getElementById('btn-toggle-password');
        const inputNovaSenha    = document.getElementById('nova_senha');

        btnTogglePassword.addEventListener('click', function () {
            if (inputNovaSenha.type === 'password') {
                inputNovaSenha.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                inputNovaSenha.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // confirmação antes de excluir — ação irreversível
        document.getElementById('btn-excluir-conta').addEventListener('click', function () {
            Swal.fire({
                title: 'Tem certeza absoluta?',
                text: "Esta ação é irreversível! Seus dados e histórico serão apagados para sempre.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#E94B3C',
                cancelButtonColor: '#888',
                confirmButtonText: 'Sim, excluir minha conta',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'excluir_conta');

                    fetch('CONFIGURA_ALUNO.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Conta Excluída',
                                    text: 'Sua conta foi removida com sucesso. Até logo!',
                                    confirmButtonColor: '#2fb399'
                                }).then(() => {
                                    window.location.href = 'TELA_DE_LOGIN.php';
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro',
                                    text: 'Não foi possível deletar a conta: ' + data.message,
                                    confirmButtonColor: '#E94B3C'
                                });
                            }
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro de Conexão',
                                text: 'Ocorreu uma falha na comunicação com o servidor.',
                                confirmButtonColor: '#E94B3C'
                            });
                        });
                }
            });
        });
    </script>
</body>
</html>