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

$data = json_decode(file_get_contents("php://input"));

$id_usuario = isset($data->id_usuario) ? $data->id_usuario : null;
$papel = isset($data->papel) ? $data->papel : null;

if ($id_usuario === null || $papel === null) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos.']);
    exit();
}

$papel = in_array($papel, [1, 2]) ? $papel : 0;

$sql = "UPDATE usuarios SET papel = ? WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("ii", $papel, $id_usuario);

if ($stmt->execute()) {
    if ($papel === 2) {
        $sql = "SELECT COUNT(*) AS count FROM administradores WHERE id_usuario = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            $sql = "INSERT INTO administradores (id_usuario) VALUES (?)";
            $stmt = $conexao->prepare($sql);
            $stmt->bind_param("i", $id_usuario);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Papel do usuário atualizado com sucesso!'];
            } else {
                $response = ['status' => 'error', 'message' => 'Erro ao inserir o usuário como administrador: ' . $stmt->error];
            }
            $stmt->close();
        }
    } else {
        $sql = "DELETE FROM administradores WHERE id_usuario = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Papel do usuário atualizado com sucesso!'];
        } else {
            $response = ['status' => 'error', 'message' => 'Erro ao remover o usuário da tabela administradores: ' . $stmt->error];
        }
        $stmt->close();
    }
} else {
    $response = ['status' => 'error', 'message' => 'Erro ao atualizar o papel do usuário: ' . $stmt->error];
}

if (!isset($response)) {
    $response = ['status' => 'error', 'message' => 'O usuário já possui a função de administrador.'];
}

header('Content-Type: application/json');
echo json_encode($response);
$conexao->close();
?>