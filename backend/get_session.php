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

$usuario = $_SESSION['usuario'];

header('Content-Type: application/json');

if (isset($_GET['getUserId']) && $_GET['getUserId'] != "") {
    $sql = "SELECT id FROM usuarios WHERE usuario = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row !== null) {
        $userId = $row['id'];
        $response = ['status' => 'success', 'userId' => $userId];
    } else {
        $response = ['status' => 'error', 'message' => 'Usuário não encontrado.'];
    }

    $conexao->close();
    echo json_encode($response);
    exit();
}

function getBearerToken() {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer (.+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

$token = getBearerToken();

$sql = "SELECT token FROM usuarios WHERE usuario = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row !== null) {
    $userToken = $row['token'];
} else {
    $response = ['status' => 'error', 'message' => 'Token não encontrado'];
    exit();
}

$conexao->close();

if ($token && $token === $userToken) {
    if (isset($_SESSION['usuario'])) {
        echo json_encode([
            'usuario' => $_SESSION['usuario'],
            'token' => $token
        ]);
        exit();
    } else {
        echo json_encode([
            'usuario' => null,
            'token' => $token
        ]);
        exit();
    }
} else {
    echo json_encode([
        'usuario' => null,
        'token' => null,
        'message' => 'Token inválido ou não fornecido'
    ]);
    exit();
}
?>