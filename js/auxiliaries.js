var token = localStorage.getItem('token');

if (!token) {
    carregarPagina('home.html');
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
    var message = response.message;

    if (response.status === 'success') {
        $.notify(message, 'success');
    } else {
        $.notify(message, 'error');
    }
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

        fetch('backend/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token: token })
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    resolve('success');
                } else {
                    resolve('error');
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                resolve('error');
            });
    });
}

function verificarSessao(callback) {
    fetch('backend/get_session.php')
        .then(response => response.json())
        .then(data => {
            callback(data);
        })
        .catch(error => {
            console.error('Erro ao verificar sessão:', error);
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
        }, 1500);
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
            }, 1500);
            setTimeout(() => {
                carregarPagina('home.html').then(() => {
                    scrollToTop();
                });
                $("#navbarItems button").removeClass("active");
            }, 2200);
        }
    });
}

function logout() {
    fetch('backend/login.php?logout=1', {
        method: 'GET',
    })
        .then(response => {
            if (response.ok) {
                localStorage.removeItem('token');
                setTimeout(() => {
                    carregarPagina('home.html').then(() => {
                        location.reload();
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