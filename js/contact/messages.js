$(document).ready(function () {
    $.ajax({
        url: 'backend/contact.php?action=listContact',
        method: 'GET',
        dataType: 'json',
        headers: {
            'Authorization': 'Bearer ' + token
        },
        success: function (data) {
            if (Array.isArray(data) && data.length > 0) {
                populateDataTable(data);
            } else if (data.status === 'success' && data.message) {
                $('#messagesBody').html(
                    `<tr>
                        <td colspan="5" class="text-center text-danger">${data.message}</td>
                    </tr>`
                );
            } else {
                $('#messagesBody').html(
                    `<tr>
                        <td colspan="5" class="text-center text-danger">Erro ao carregar mensagens.</td>
                    </tr>`
                );
            }
        },
        error: function (xhr, status, error) {
            $('#messagesBody').html(`
                <tr>
                    <td colspan="4" class="text-center text-danger">Erro ao carregar mensagens: ${error}</td>
                </tr>
            `);
        }
    });
});

function dateType(data) {
    return moment(data).format('DD/MM/YYYY');
}

function populateDataTable(data) {
    if ($.fn.DataTable.isDataTable('#messagesTable')) {
        table.clear().destroy();
    }

    var table = $('#messagesTable').DataTable({
        paging: true,
        language: {
            "sEmptyTable": "Nenhum dado encontrado",
            "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
            "sInfoFiltered": "(Filtrados de _MAX_ registros)",
            "sLengthMenu": "_MENU_ resultados por página",
            "sLoadingRecords": "Carregando...",
            "sProcessing": "Processando...",
            "sZeroRecords": "Nenhum registro encontrado",
            "sSearch": "Pesquisar",
            "oPaginate": {
                "sNext": "Próximo",
                "sPrevious": "Anterior",
                "sFirst": "Primeiro",
                "sLast": "Último"
            },
            "oAria": {
                "sSortAscending": ": Ordenar colunas de forma ascendente",
                "sSortDescending": ": Ordenar colunas de forma descendente"
            }
        },
        data: data,
        columns: [
            {
                title: 'Ações',
                width: '5%',
                render: function (data, type, row) {
                    return `
                        <div class="text-center">
                            <input type="hidden" name="indice" value="${row.indice}">
                            <button class="bi bi-pencil-fill editar-btn" name="editar"></button><br>
                            <button class="bi bi-trash3 excluir-btn" name="excluir"></button><br>
                            <button class="bi bi-plus-circle adc-btn" name="adicionar"></button>
                        </div>
                    `;
                }
            },
            {
                data: 'data_criacao', render: function (data, type) {
                    if (type === 'sort') {
                        return data;
                    }
                    return dateType(data);
                }
            },
            { data: 'nome' },
            { data: 'email' },
            { data: 'mensagem' },
            { data: 'visto', render: function (data) { return data === 1 ? 'Sim' : 'Não'; } }
        ],
        scrollX: true,
        responsive: {
            details: {
                type: 'column'
            }
        },
        columnDefs: [
            {
                targets: '_all',
                width: 150,
                render: function (data, type, row, meta) {
                    var isLongText = type === 'display' && data.length > 60;
                    var displayText = isLongText ? data.substr(0, 60) + ' <strong>(...)</strong>' : data;
                    var cssClass = isLongText ? 'expandable' : '';
                    return `<span class="${cssClass}" title="${data}">${displayText}</span>`;
                }
            }
        ],
    });
    table.order([1, 'desc']).draw();
    $('.expandable').click(function () {
        var content = $(this).attr('title');
        $(this).html(content);
    });
}