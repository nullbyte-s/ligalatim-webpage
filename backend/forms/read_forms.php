<?php
require '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;

if ($usuario) {
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

    $sql = "SELECT * FROM formularios";
    $result = $conexao->query($sql);

    if ($result) {
        $formularios = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($formularios);
    } else {
        $response = ['status' => 'error', 'message' => 'Erro ao carregar formularios: ' . $conexao->error];
        echo json_encode($response);
    }

    $conexao->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autenticado']);
}
?>