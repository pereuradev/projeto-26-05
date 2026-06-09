// ============================================================
// mudar-tema.js — Controla o tema em todas as páginas
// Usa TANTO html.dark-mode QUANTO body.dark-mode para garantir
// compatibilidade com ambos os CSS (login e cadastro)
// ============================================================

function aplicarTema(tema) {
    const html = document.documentElement;
    const body = document.body;
    const logo = document.getElementById('minhaImagem') || document.getElementById('logo');
    const btnTheme = document.getElementById('toggle-theme') || document.querySelector('.btn-theme');

    if (tema === 'dark') {
        // Aplica em AMBOS html e body para cobrir os dois CSS
        html.setAttribute('data-theme', 'dark');
        html.classList.add('dark-mode');
        body.classList.add('dark-mode');

        // Atualiza texto do botão
        if (btnTheme) btnTheme.textContent = 'Tema Claro';

        // Atualiza logo
        if (logo) {
            logo.src = logo.dataset.dark || 'Logo-Cortex-Branca.png';
        }
    } else {
        // Remove de AMBOS
        html.setAttribute('data-theme', 'light');
        html.classList.remove('dark-mode');
        body.classList.remove('dark-mode');

        // Atualiza texto do botão
        if (btnTheme) btnTheme.textContent = 'Tema Escuro';

        // Atualiza logo
        if (logo) {
            logo.src = logo.dataset.light || 'LOGO-Cortex(maior).png';
        }
    }
}

// Aplica o tema salvo ANTES do DOM estar pronto (evita o "flash" branco)
(function () {
    const temaSalvo = localStorage.getItem('theme') || 'light';
    if (temaSalvo === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        document.documentElement.classList.add('dark-mode');
        // body ainda pode não existir aqui, mas o html já protege o fundo
    }
})();

// Após o DOM carregar, aplica tudo corretamente e liga o botão
document.addEventListener('DOMContentLoaded', () => {
    const temaSalvo = localStorage.getItem('theme') || 'light';
    aplicarTema(temaSalvo);

    const btnTheme = document.getElementById('toggle-theme') || document.querySelector('.btn-theme');

    if (btnTheme) {
        btnTheme.addEventListener('click', () => {
            const temaAtual = localStorage.getItem('theme') || 'light';
            const novoTema = temaAtual === 'dark' ? 'light' : 'dark';
            localStorage.setItem('theme', novoTema);
            aplicarTema(novoTema);
        });
    }
});