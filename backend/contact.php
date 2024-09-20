<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
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

if (isset($_GET['action'])) {
    require 'authentication.php';
    $response = verificarToken($conexao);

    if ($response['status'] === 'error') {
        echo json_encode($response);
        exit();
    }

    $usuario = $response['usuario'];
    $papel = $response['papel'];

    if ($usuario && $papel === 2) {
        switch ($_GET['action']) {
            case 'listContact':
                global $conexao;
                $sql = "SELECT * FROM contato";
                $result = $conexao->query($sql);
                if ($result) {
                    $resultados = [];
                    while ($row = $result->fetch_assoc()) {
                        $resultados[] = $row;
                    }
                    header('Content-Type: application/json');
                    if (empty($resultados)) {
                        echo json_encode(['status' => 'success', 'message' => 'Nenhuma mensagem encontrada.']);
                    } else {
                        echo json_encode($resultados);
                    }
                } else {
                    $response = ['status' => 'error', 'message' => 'Erro ao carregar mensagens de contato: ' . $conexao->error];
                    header('Content-Type: application/json');
                    echo json_encode($response);
                }
                $conexao->close();
                break;
            case 'deleteContact':
                //TODO
                break;
            default:
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Ação não reconhecida']);
                http_response_code(400);
                break;
        }
    } else {
        header('Content-Type: application/json');
        $response = ['status' => 'error', 'message' => 'Usuário não autorizado'];
        echo json_encode($response);
    }
} else {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $mensagem = $_POST['mensagem'];
    global $conexao;

    $sql = "INSERT INTO contato (nome, email, mensagem) VALUES (?, ?, ?)";
    $stmt = $conexao->prepare($sql);

    if ($stmt === false) {
        $response = ['status' => 'error', 'message' => 'Erro ao preparar a consulta: ' . $conexao->error];
        echo json_encode($response);
        exit();
    }

    $stmt->bind_param('sss', $nome, $email, $mensagem);

    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Mensagem enviada com sucesso!'];
    } else {
        $response = ['status' => 'error', 'message' => 'Erro ao enviar mensagem: ' . $stmt->error];
    }

    $stmt->close();
    $conexao->close();
    echo json_encode($response);
}
?>