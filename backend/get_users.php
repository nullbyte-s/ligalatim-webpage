<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) || !isset($_SESSION['userLevel']) || $_SESSION['userLevel'] <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
    exit();
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

$sql = "SELECT id, nome, papel FROM usuarios";
$result = $conexao->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'users' => $users]);
$conexao->close();
?>