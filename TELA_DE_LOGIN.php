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
    <title>Córtex - Login</title>
    <link rel="shortcut icon" href="Logo-Cortex-Branca.png" type="image/x-icon">
    <link rel="stylesheet" href="TELA_DE_LOGIN.css?v=6">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .password-container {
            position: relative;
            width: 100%;
        }
        .password-container input {
            padding-right: 45px;
        }
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 16px;
            transition: color 0.2s;
        }
        .toggle-password:hover {
            color: var(--color-verde, #2FB399);
        }
    </style>
</head>
<body class="auth-page login-page">
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
            <h1>Login</h1>
            <p class="auth-subtitle">Acesse seu painel e continue seus quizzes.</p>
        </div>

        <form id="form-login" method="POST" action="login.php">
            <fieldset>
                <legend>Bem-vindo de volta!</legend>

                <p class="login-link">
                    Não tem uma conta? <a href="TELA_DE_CADASTRO.php">Faça cadastro aqui</a>
                </p>

                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" placeholder="seu.email@exemplo.com" required autocomplete="email">
                </div>

                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <div class="password-container">
                        <input type="password" id="senha" name="senha" placeholder="Mínimo 8 caracteres" required minlength="8" autocomplete="current-password">
                        <i class="fa-solid fa-eye toggle-password" id="btn-toggle-senha"></i>
                    </div>
                    <div class="forgot-password" style="margin-top: 8px; text-align: right; font-size: 14px;">
                        <a href="esqueci_senha.php" style="color: var(--color-verde); text-decoration: none; font-weight: 600;">Esqueceu sua senha?</a>
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Entrar</button>
                <button type="reset" class="btn btn-secondary">Limpar</button>
            </div>
        </form>
    </div>
</main>

<script src="mudar-tema.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ==========================================
    // OLHINHO DA SENHA
    // ==========================================
    const btnToggleSenha = document.getElementById('btn-toggle-senha');
    const inputSenha     = document.getElementById('senha');

    btnToggleSenha.addEventListener('click', function () {
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

    // ==========================================
    // POP-UP DE AUTENTICAÇÃO
    // ==========================================
    const formLogin = document.getElementById('form-login');

    formLogin.addEventListener('submit', function(e) {
        if (formLogin.checkValidity()) {
            e.preventDefault();

            Swal.fire({
                title: 'Autenticando...',
                text: 'Validando suas credenciais no Córtex.',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                    setTimeout(() => {
                        formLogin.submit();
                    }, 800);
                }
            });
        }
    });
</script>

<?php if (isset($_SESSION['login_erro'])): ?>
    <script>
        Swal.fire({ 
            icon: 'error', 
            title: 'Falha no Login', 
            text: '<?php echo $_SESSION['login_erro']; ?>', 
            confirmButtonColor: '#E94B3C' 
        });
    </script>
<?php unset($_SESSION['login_erro']); endif; ?>
</body>
</html>
