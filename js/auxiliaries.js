const token = localStorage.getItem('token');
let usersData = [];

if (!token) {
    carregarPagina('home.html', [], ['home.css']);
}

$(document).ready(function () {
    $("#navbarItems button").on("click", function () {
        $("#navbarItems button").removeClass("active");
        $(this).addClass("active");
    });
});

function panelClick() {
    $(".dropdown-item").on("click", function () {
        $("#navbarItems button").removeClass("active");
    });
}

function displayNotification(response) {
    mdtoast(response.message, {
        duration: 2000,
        type: response.status === 'success' ? mdtoast.SUCCESS : mdtoast.ERROR,
        interaction: false
    });
}

function handleEnterKey(event, submitFunction) {
    if (event.key === 'Enter') {
        submitFunction();
    }
}

function scrollToTop() {
    var currentPosition = document.documentElement.scrollTop || document.body.scrollTop;
    var targetPosition = 0;
    var distance = targetPosition - currentPosition;
    var duration = 500;

    function animateScroll(timestamp) {
        var progress = Math.min(1, (timestamp - start) / duration);
        document.documentElement.scrollTop = document.body.scrollTop = currentPosition + distance * progress;
        if (progress < 1) {
            requestAnimationFrame(animateScroll);
        }
    }

    var start = null;

    requestAnimationFrame(function (timestamp) {
        start = timestamp;
        animateScroll(timestamp);
    });
}

function verificarAutenticacao() {
    return new Promise((resolve, reject) => {

        if (!token) {
            resolve('error');
            return;
        }

        fetch('backend/authentication.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ token: token })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    resolve({
                        status: 'success',
                        usuario: data.usuario,
                        papel: data.papel
                    });
                } else {
                    resolve({
                        status: 'error',
                        message: data.message || 'Erro desconhecido'
                    });
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                resolve('error');
            });
    });
}

function submitMessage() {
    var nome = document.getElementById('nome').value;
    var email = document.getElementById('email').value;
    var mensagem = document.getElementById('mensagem').value;

    if (nome.trim() === '' || email.trim() === '' || mensagem.trim() === '') {
        document.querySelector('#notificacao').innerHTML = '<div class="alert alert-danger">Por favor, preencha todos os campos.</div>';
        setTimeout(function () {
            document.querySelector('#notificacao').innerHTML = '';
        }, 2000);
        return;
    }

    document.querySelector('#notificacao').innerHTML = '';
    document.getElementById('sendButton').disabled = true;

    const formData = new FormData(document.getElementById('contactForm'));

    $.ajax({
        url: 'backend/contact.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        contentType: false,
        processData: false,
        success: function (data) {
            document.querySelector('#notificacao').innerHTML = `<div class="alert alert-${data.status === 'success' ? 'success' : 'danger'}">${data.message}</div>`;
            document.getElementById('contactForm').reset();
        },
        error: function (error) {
            document.querySelector('#notificacao').innerHTML = '<div class="alert alert-danger">Erro no envio da mensagem.</div>';
        },
        complete: function () {
            setTimeout(function () {
                document.querySelector('#notificacao').innerHTML = '';
            }, 2000);
            setTimeout(() => {
                carregarPagina('home.html', [], ['home.css']).then(() => {
                    scrollToTop();
                });
                $("#navbarItems button").removeClass("active");
            }, 3000);
        }
    });
}

function setupContactTable() {
    var contactContainer = document.getElementById('contactContainer');
    if (!contactContainer) {
        console.error('Elemento #contactContainer não encontrado.');
        return;
    }

    var table = document.createElement('table');
    table.classList.add('table', 'table-striped', 'table-bordered');
    var thead = document.createElement('thead');
    var tr = document.createElement('tr');
    tr.innerHTML = '<th>Nome</th><th>Email</th><th>Mensagem</th>';
    thead.appendChild(tr);
    table.appendChild(thead);
    var tbody = document.createElement('tbody');
    table.appendChild(tbody);
    contactContainer.appendChild(table);
    return tbody;
}

function visualizarMensagens(data) {
    var tableBody = setupContactTable();

    tableBody.innerHTML = '';

    data.forEach(function (row) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td>' + row.nome + '</td>' +
            '<td>' + row.email + '</td>' +
            '<td>' + row.mensagem + '</td>';
        tableBody.appendChild(tr);
    });

    var clearButton = document.createElement('button');
    clearButton.textContent = 'Limpar Mensagens';
    clearButton.classList.add('btn', 'btn-danger', 'mt-3');
    clearButton.addEventListener('click', function () {
        limparMensagens();
    });

    var tableContainer = document.getElementById('contactContainer');
    tableContainer.appendChild(clearButton);
}

function limparMensagens() {
    fetch('backend/clear_messages.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                setTimeout(() => {
                    location.reload();
                }, 500);
            } else {
                console.error('Erro ao limpar mensagens:', data.message);
            }
        })
        .catch(error => {
            console.error('Erro ao limpar mensagens:', error);
        });
}

function logout() {
    fetch('backend/authentication.php?logout=1', {
        method: 'GET',
    })
        .then(response => {
            if (response.ok) {
                localStorage.removeItem('token');
                document.querySelector('.py-5').classList.add("hidden");
                setTimeout(() => {
                    carregarPagina('home.html', [], ['home.css']).then(() => {
                        document.getElementById('navbarItems').innerHTML = `
                        <button class="nav-item nav-link" onclick="carregarPagina('contact/contact.html', ['contact/contact.js'])">
                            <h4 class="bi bi-chat-right-text-fill fs-3"></h4>
                        </button>
                        <button class="nav-item nav-link" onclick="carregarPagina('login.html')">
                            <h4 class="bi bi-person-circle fs-3"></h4>
                        </button>
                    `;
                    });
                }, 500);
            } else {
                console.error('Erro ao realizar logout');
            }
        })
        .catch(error => {
            console.error('Erro ao realizar logout:', error);
        });
}

function byAccessLevel(role = 0) {
    const menuOptions = document.getElementById('menuOptions');
    menuOptions.innerHTML = '';

    const createCard = ({ type, href, onclick, text, title, message, icon }) => {
        const cardBase = `
            <div class="card mt-2 shadow rounded">
                ${icon ? `<i class="card-title bi ${icon} first-content"></i>` : ''}
                ${type === 'menu'
                ? `<a class="second-content" href="${href}" onclick="${onclick}">${text}</a>`
                : `<div class="first-content">
                    <i class="bi bi-exclamation-diamond-fill text-secondary">
                        <h5 class="card-title">${title}</h5>
                    </i>
                </div>
                ${message ? `<p class="alert second-content">${message}</p>` : ''}`}
            </div>
        `;
        return cardBase;
    };

    if (role > 1) {
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: 'loadUsers()',
            text: 'Gerenciar Usuários',
            icon: 'bi-people'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: "carregarPagina('developing.html', [], ['developing.css'])",
            text: 'Gerenciar Backups',
            icon: 'bi-database-fill-gear'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: "carregarPagina('contact/messages.html', ['contact/messages.js', 'jquery.dataTables.min.js', 'moment.min.js'])",
            text: 'Central de Mensagens',
            icon: 'bi-envelope-paper'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: "carregarPagina('developing.html', [], ['developing.css'])",
            text: 'Controle de Presenças',
            icon: 'bi-clipboard2-check'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: "carregarPagina('developing.html', [], ['developing.css'])",
            text: 'Visualizar Formulários',
            icon: 'bi-ui-checks'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: "carregarPagina('forms/create_form.html', ['forms.js'], ['create_form.css'])",
            text: 'Criar Formulário',
            icon: 'bi-pencil-square'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: "carregarPagina('developing.html', [], ['developing.css'])",
            text: 'Editar Formulários',
            icon: 'bi-input-cursor-text'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: "carregarPagina('developing.html', [], ['developing.css'])",
            text: 'Deletar Formulários',
            icon: 'bi-trash3'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: "carregarPagina('developing.html', [], ['developing.css'])",
            text: 'Enviar Imagem',
            icon: 'bi-file-image'
        });
    } else if (role === 1) {
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: 'carregarPagina("admin-dashboard.html")',
            text: 'Perfil',
            icon: 'bi-person'
        });
        menuOptions.innerHTML += createCard({
            type: 'menu',
            href: '#',
            onclick: 'carregarPagina("admin-dashboard.html")',
            text: 'Configurações',
            icon: 'bi-gear'
        });
    } else {
        menuOptions.innerHTML += createCard({
            type: 'status',
            title: 'Usuário Desativado',
            message: 'Em breve, o administrador confirmará a identidade e poderá liberar o acesso a esta conta.'
        });
    }
}

function updateRoleOnUserSelection() {
    const userId = document.getElementById('id_usuario').value;
    const papelSelect = document.getElementById('papel');

    const selectedUser = usersData.find(user => user.id == userId);
    if (selectedUser) {
        papelSelect.value = selectedUser.papel;
    } else {
        papelSelect.value = "0";
    }
}

async function loadUsers() {
    try {
        const htmlResponse = await fetch('includes/role_manager.html');
        const html = await htmlResponse.text();
        document.getElementById('content').innerHTML = html;
        const userResponse = await fetch('backend/get_users.php');
        const data = await userResponse.json();

        if (data.status === 'success') {
            const userSelect = document.getElementById('id_usuario');
            userSelect.innerHTML = '<option value="">Selecione um usuário</option>';
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.nome;
                userSelect.appendChild(option);
            });
            usersData = data.users;
            document.getElementById('id_usuario').addEventListener('change', updateRoleOnUserSelection);
        } else {
            displayNotification({ status: 'error', message: 'Erro ao carregar usuários.' });
        }
    } catch (error) {
        console.error('Erro ao carregar usuários ou HTML:', error);
    }
}

async function deleteUser() {
    const userElement = document.getElementById('id_usuario');
    const userId = userElement ? userElement.value : '-1';

    if (!userId) {
        displayNotification({ status: 'error', message: 'Por favor, selecione um usuário.' });
        return;
    } else if (userId == -1) {
        deleteCurrentUser();
        return;
    }

    try {
        const response = await fetch('backend/delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id_usuario: userId })
        });
        const data = await response.json();
        displayNotification(data);

        if (data.status === 'success') {
            loadUsers();
        }
    } catch (error) {
        console.error('Erro ao excluir usuário:', error);
    }
}

async function deleteCurrentUser() {
    try {
        const response = await fetch('backend/get_session.php?getUserId=true', {
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });
        const data = await response.json();

        if (data.status === 'success') {
            const userId = data.userId;

            if (userId) {
                const deleteResponse = await fetch('backend/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({ id_usuario: userId })
                });
                const deleteData = await deleteResponse.json();
                displayNotification({ status: 'success', message: deleteData.message });
                localStorage.removeItem('token');
                setTimeout(() => {
                    location.reload();
                }, 1500);
                return deleteData;
            } else {
                console.error('ID do usuário não encontrado.');
                return null;
            }
        } else {
            console.error('Erro ao obter o ID do usuário:', data.message);
            return null;
        }
    } catch (error) {
        console.error('Erro ao fazer requisição:', error);
        return null;
    }
}

function updateUserRole() {
    const idUsuario = document.getElementById('id_usuario').value;
    const papel = document.getElementById('papel').value;

    fetch('backend/update_user_role.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id_usuario: idUsuario, papel: parseInt(papel) })
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayNotification({ status: 'success', message: data.message });

            } else {
                displayNotification({ status: 'error', message: data.message });
            }
        })
        .catch(error => {
            displayNotification({ status: 'error', message: 'Erro na comunicação com o servidor.' });
            console.error('Erro:', error);
        });
}