<?php
require '../vendor/autoload.php';
require '../authentication.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$database = $_ENV['DB_DATABASE'];
$conexao = new mysqli($servername, $username, $password, $database);

if ($conexao->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Erro na conexão com o banco de dados: ' . $conexao->connect_error]);
    exit();
}

function obterProximoIdFormulario() {
    global $conexao;
    $sql = "SELECT IFNULL(MAX(id), 0) + 1 AS nextId FROM formularios";
    $result = $conexao->query($sql);
    $nextId = $result->fetch_assoc();

    $conexao->close();
    return ['status' => 'success', 'nextId' => $nextId['nextId']];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['nextId'])) {
    $response = obterProximoIdFormulario();
    echo json_encode($response);
    exit();
}

$response = verificarToken($conexao);
if ($response['status'] === 'error') {
    echo json_encode($response);
    exit();
}

$usuario = $response['usuario'];
$papel = $response['papel'];

if (!$papel) {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autorizado']);
    exit();
}

if ($usuario && $papel === 2) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        global $conexao;
        $conexao->begin_transaction();

        try {
            $sql = "INSERT INTO formularios (titulo, descricao) VALUES (?, ?)";
            $stmt = $conexao->prepare($sql);
            $stmt->bind_param('ss', $titulo, $descricao);
            $stmt->execute();
            $formulario_id = $stmt->insert_id;
            $questionIndex = 1;
            
            while (isset($_POST["texto_$questionIndex"])) {
                $texto = $_POST["texto_$questionIndex"];
                $tipo = $_POST["tipo_$questionIndex"];
                $grafico = $_POST["grafico_$questionIndex"];
                $opcoes = $_POST["opcoes_$questionIndex"] ?? [];
                
                $sql = "INSERT INTO questoes (formulario_id, texto, tipo, grafico) VALUES (?, ?, ?, ?)";
                $stmt = $conexao->prepare($sql);
                $stmt->bind_param('isss', $formulario_id, $texto, $tipo, $grafico);
                $stmt->execute();
                $questao_id = $stmt->insert_id;

                if ($tipo === 'multipla' || $tipo === 'multiplas') {
                    foreach ($opcoes as $opcao) {
                        $sql = "INSERT INTO opcoes (id_questao, texto) VALUES (?, ?)";
                        $stmt = $conexao->prepare($sql);
                        $stmt->bind_param('is', $questao_id, $opcao);
                        $stmt->execute();
                    }
                }

                if (isset($_FILES["imagens_$questionIndex"])) {
                    $imagens = $_FILES["imagens_$questionIndex"];
                    if ($imagens && isset($imagens['tmp_name'])) {
                        $upload_dir = '../uploads/';
                        foreach ($imagens['tmp_name'] as $key => $tmp_name) {
                            $filename = basename($imagens['name'][$key]);
                            $upload_file = $upload_dir . $filename;

                            if (move_uploaded_file($tmp_name, $upload_file)) {
                                $sql = "INSERT INTO imagens (id_questao, caminho) VALUES (?, ?)";
                                $stmt = $conexao->prepare($sql);
                                $stmt->bind_param('is', $questao_id, $upload_file);
                                $stmt->execute();
                            }
                        }
                    }
                }
                $questionIndex++;
            }
            $conexao->commit();
            $response = ['status' => 'success', 'message' => 'Formulário e questões criados com sucesso!'];
        } catch (Exception $e) {
            $conexao->rollback();
            $response = ['status' => 'error', 'message' => 'Erro ao criar formulário e questões: ' . $e->getMessage()];
        }

        $conexao->close();
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autorizado']);
}
?>