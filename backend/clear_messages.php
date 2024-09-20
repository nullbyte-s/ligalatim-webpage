<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function verificarPapelUsuario($usuario) {
    $servername = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $database = $_ENV['DB_DATABASE'];

    $conexao = new mysqli($servername, $username, $password, $database);

    if ($conexao->connect_error) {
        error_log("Erro na conexão com o banco de dados: " . $conexao->connect_error);
        return false;
    }

    $sql = "SELECT COUNT(*) AS userLevel FROM administradores WHERE id_usuario = (SELECT id FROM usuarios WHERE usuario = ?)";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $adminCheck = $result->fetch_assoc();

    $conexao->close();
    return $adminCheck['userLevel'] > 0;
}

$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
$papel = $usuario ? verificarPapelUsuario($usuario) : false;

if ($usuario && $papel) {
    $servername = $_ENV['DB_HOST'];
    $username = $_ENV['DB_USERNAME'];
    $password = $_ENV['DB_PASSWORD'];
    $database = $_ENV['DB_DATABASE'];

    $conexao = new mysqli($servername, $username, $password, $database);

    if ($conexao->connect_error) {
        $response = ['status' => 'error', 'message' => 'Erro na conexão com o banco de dados: ' . $conexao->connect_error];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    $sql = "DELETE FROM contato";

    if ($conexao->query($sql) === TRUE) {
        $response = ['status' => 'success', 'message' => 'Mensagens apagadas com sucesso'];
    } else {
        $response = ['status' => 'error', 'message' => 'Erro ao apagar mensagens: ' . $conexao->error];
    }

    header('Content-Type: application/json');
    echo json_encode($response);

    $conexao->close();
} else {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Usuário não autorizado'];
    echo json_encode($response);
}
?>