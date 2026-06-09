<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Córtex - Recuperar Senha</title>

    <link rel="stylesheet" href="REDEFINIR.css">
</head>

<body>

<main>

    <button id="toggle-theme" class="btn-theme">
        Alternar Tema
    </button>
     <div class="shape-verde-left"></div>
    <div class="shape-azul-left"></div>
    <div class="shape-triangle-top"></div>
    <div class="shape-square-top"></div>
    <div class="shape-square-bottom"></div>
    <div class="shape-laranja-right"></div>
    <div class="shape-amarelo-right"></div>
<div class="container">

    <div class="header">
        <h1>Recuperar Senha</h1>
    </div>

    <form method="POST" action="enviar_email.php">

        <fieldset>

            <legend>Digite seu e-mail</legend>

            <div class="form-group">

                <label>E-mail:</label>

                <input
                    type="email"
                    name="email"
                    required
                    placeholder="Digite seu e-mail"
                >

            </div>

        </fieldset>
         <p class="login-link">

                Não tem cadastro?

                <a href="TELA_DE_CADASTRO.php">
                    Faça ele aqui aqui!
                </a>

            </p>

        <div class="form-actions">

            <button type="submit" class="btn btn-primary">
                Enviar Link
            </button>
             

        </div>

    </form>

</div>

</main>
<script>

    const themeBtn = document.getElementById('toggle-theme');
    const imagem = document.getElementById('minhaImagem');

    themeBtn.addEventListener('click', () => {

        // Alterna modo escuro
        document.body.classList.toggle('dark-mode');

        // Troca logo
        if(document.body.classList.contains('dark-mode')){

            imagem.src = "Logo-Cortex Branca.png";

        } else {

            imagem.src = "Logo-Cortex.png";
        }

    });

</script>
</body>
</html>