<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$database = $_ENV['DB_DATABASE'];

$conexao = new mysqli($servername, $username, $password, $database);

if ($conexao->connect_error) {
    $response = ['status' => 'error', 'message' => 'Erro na conexão com o banco de dados: ' . $conexao->connect_error];
    echo json_encode($response);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
$id_usuario = isset($data->id_usuario) ? $data->id_usuario : null;

if ($id_usuario === null) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos.']);
    exit();
}

if (!isset($_SESSION['usuario']) || !isset($_SESSION['userLevel'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado.']);
    exit();
}

if ($_SESSION['userLevel'] > 0) {
    $sql = "DELETE FROM administradores WHERE id_usuario = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Usuário excluído com sucesso!'];
    } else {
        $response = ['status' => 'error', 'message' => 'Erro ao excluir o usuário: ' . $stmt->error];
    }
} else {
    $sql = "DELETE FROM administradores WHERE id_usuario = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    
    if ($stmt->execute()) {
        session_destroy();
        
        $response = ['status' => 'success', 'message' => 'Sua conta foi excluída com sucesso.'];
    } else {
        $response = ['status' => 'error', 'message' => 'Erro ao excluir sua conta: ' . $stmt->error];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
$conexao->close();
?>