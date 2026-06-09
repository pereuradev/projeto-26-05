<?php
if (!isset($_GET['token'])) {
    die("Token inválido!");
}
$token = $_GET['token'];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha</title>
    <link rel="stylesheet" href="TELA_DE_LOGIN.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .password-container {
            position: relative;
            width: 100%;
        }

        .password-container input {
            padding-right: 45px !important;
        }

        /* Garante espaço para o ícone */
        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 16px;
            transition: 0.2s;
            z-index: 10;
        }

        .toggle-password:hover {
            color: #333;
        }
    </style>
</head>

<body>

    <main>
        <div class="container">
            <form method="POST" action="salvar_nova_senha.php">
                <fieldset>
                    <legend>Criar Nova Senha</legend>

                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group">
                        <label>Nova senha:</label>
                        <div class="password-container">
                            <input
                                type="password"
                                id="nova_senha"
                                name="nova_senha"
                                required>
                            <i class="fa-solid fa-eye toggle-password" id="btn-toggle-password"></i>
                        </div>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        const btnTogglePassword = document.getElementById('btn-toggle-password');
        const inputNovaSenha = document.getElementById('nova_senha');

        btnTogglePassword.addEventListener('click', function() {
            if (inputNovaSenha.type === 'password') {
                inputNovaSenha.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                inputNovaSenha.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    </script>
</body>

</html>