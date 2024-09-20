<?php
require '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
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
    $id = $_POST['id'] ?? '';
    $titulo = $_POST['titulo'] ?? '';
    $descrição = $_POST['descrição'] ?? '';

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

    $sql = "UPDATE formulários SET titulo = ?, descrição = ? WHERE id = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param('ssi', $titulo, $descrição, $id);

    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Formulário atualizado com sucesso!'];
    } else {
        $response = ['status' => 'error', 'message' => 'Erro ao atualizar formulário: ' . $stmt->error];
    }

    $stmt->close();
    $conexao->close();
    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autorizado']);
}
?>