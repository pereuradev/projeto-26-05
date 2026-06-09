<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <script>
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark-mode');
    }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Córtex - Cadastro</title>
    <link rel="shortcut icon" href="Logo-Cortex-Branca.png" type="image/x-icon">
    <link rel="stylesheet" href="TELA_DE_LOGIN.css?v=6">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .password-container { position: relative; width: 100%; }
        .password-container input { padding-right: 45px !important; } /* Espaço para o ícone */
        .toggle-password { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; font-size: 16px; transition: 0.2s; z-index: 10; }
        .toggle-password:hover { color: var(--texto); }
    </style>
</head>
<body class="auth-page cadastro-page">
<main>
    <button id="toggle-theme" class="btn-theme">Alternar Tema</button>

    <div class="shape-verde-left"></div>
    <div class="shape-azul-left"></div>
    <div class="shape-triangle-top"></div>
    <div class="shape-square-top"></div>
    <div class="shape-square-bottom"></div>
    <div class="shape-laranja-right"></div>
    <div class="shape-amarelo-right"></div>

    <div class="container">
        <div class="header">
            <img id="minhaImagem" 
                 src="LOGO-Cortex(maior).png" 
                 data-light="LOGO-Cortex(maior).png" 
                 data-dark="Logo-Cortex-Branca.png" 
                 alt="Logo Cortex" 
                 class="logo">
            <h1>Cadastro</h1>
            <p class="auth-subtitle">Crie seu acesso para usar a plataforma Córtex.</p>
        </div>

        <form id="form-cadastro" method="POST" action="cadastro.php">
            <fieldset>
                <legend>Crie a sua conta gratuita</legend>

                <p class="login-link">
                    Já tem uma conta? <a href="TELA_DE_LOGIN.php">Faça login aqui</a>
                </p>

                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required autocomplete="name">
                </div>

                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" placeholder="seu.email@exemplo.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <div class="password-container">
                        <input type="password" id="senha" name="senha" placeholder="Mínimo 8 caracteres" required minlength="8" autocomplete="new-password">
                        <i class="fa-solid fa-eye toggle-password" id="btn-toggle-password"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="tipo">Quem é você?</label>
                    <select id="tipo" name="tipo" required>
                        <option value="professor">Professor</option>
                        <option value="aluno">Aluno</option>
                    </select>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Cadastrar</button>
                <button type="reset" class="btn btn-secondary">Limpar</button>
            </div>
        </form>
    </div>
</main>

<script src="mudar-tema.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Lógica para alternar a visibilidade da senha
    const btnTogglePassword = document.getElementById('btn-toggle-password');
    const inputSenha = document.getElementById('senha');

    btnTogglePassword.addEventListener('click', function() {
        if (inputSenha.type === 'password') {
            inputSenha.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            inputSenha.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });

    // POP-UP ANTES DE ENVIAR O CADASTRO (Melhoria de Experiência)
    const formCadastro = document.getElementById('form-cadastro');
    formCadastro.addEventListener('submit', function(e) {
        // Impede o envio imediato para mostrar a animação
        e.preventDefault(); 
        
        Swal.fire({
            title: 'Processando...',
            text: 'Estamos criando o seu perfil no Córtex.',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
                // Envia o formulário de fato após 1 segundo de animação
                setTimeout(() => {
                    formCadastro.submit();
                }, 1000);
            }
        });
    });
</script>

<?php if (isset($_SESSION['cadastro_status'])): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $_SESSION['cadastro_status'] === "sucesso" ? "success" : "error"; ?>',
            title: '<?php echo $_SESSION['cadastro_status'] === "sucesso" ? "Sucesso!" : "Erro"; ?>',
            text: '<?php echo $_SESSION['cadastro_msg']; ?>',
            confirmButtonColor: '#2FB399'
        });
    </script>
<?php unset($_SESSION['cadastro_status'], $_SESSION['cadastro_msg']); endif; ?>
</body>
</html>
