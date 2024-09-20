function submitLoginForm() {
    document.getElementById('loginButton').disabled = true;
    var usuario = document.getElementById('username').value;
    var senha = document.getElementById('password').value;

    if (usuario.trim() === '' || senha.trim() === '') {
        displayNotification({ message: 'Por favor, preencha todos os campos.' });
        document.getElementById('loginButton').disabled = false;
        return;
    }

    fetch('backend/authentication.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ usuario, senha })
    })
        .then(response => response.json())
        .then(data => {
            displayNotification(data);
            if (data.status === 'success' && data.token) {
                localStorage.setItem('token', data.token);
                if (data.redirect) {
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }
            } else {
                document.getElementById('loginButton').disabled = false;
            }
        })
        .catch(error => {
            displayNotification({ status: 'error', message: 'Erro ao enviar dados: ' + error });
        });
}

function initAuthenticatedPageLoad(papel) {
    try {
        carregarPagina('authenticated.html').then(function () {
            byAccessLevel(papel);
            panelClick();
        });
    } catch (error) {
        console.error('Erro ao carregar a página:', error);
    }
}

verificarAutenticacao().then(data => {
    if (data.status === 'success') {
        document.getElementById('navbarItems').innerHTML = `
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="bi bi-person-circle fs-3"></i>
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="/">Painel</a>
                    <a class="dropdown-item" onclick="logout()">Sair</a>
                </div>
            </div>
        `;
        initAuthenticatedPageLoad(data.papel);
    } else {
        document.getElementById('navbarItems').innerHTML = `
            <button class="nav-item nav-link" onclick="carregarPagina('contact/contact.html', ['contact/contact.js'])">
                <h4 class="bi bi-chat-right-text-fill fs-3"></h4>
            </button>
            <button class="nav-item nav-link" onclick="carregarPagina('login.html')">
                <h4 class="bi bi-person-circle fs-3"></h4>
            </button>
        `;
        fetch('backend/authentication.php?logout=1', {
            method: 'GET'
        }).then(() => {
            if (token) {
                localStorage.removeItem('token');
                carregarPagina('home.html', [], ['home.css']);
            }
        }).catch(error => {
            console.error('Erro ao fazer logout:', error);
        });
    }
}).catch(error => {
    console.error('Erro ao verificar autenticação:', error);
});