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
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == UPLOAD_ERR_OK) {
        $questão_id = $_POST['questão_id'] ?? '';
        $imagem = $_FILES['imagem'];

        $caminho = 'uploads/' . basename($imagem['name']);
        if (move_uploaded_file($imagem['tmp_name'], $caminho)) {
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

            $sql = "INSERT INTO imagens (questão_id, caminho) VALUES (?, ?)";
            $stmt = $conexao->prepare($sql);
            $stmt->bind_param('is', $questão_id, $caminho);

            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Imagem anexada com sucesso!'];
            } else {
                $response = ['status' => 'error', 'message' => 'Erro ao anexar imagem: ' . $stmt->error];
            }

            $stmt->close();
            $conexao->close();
            echo json_encode($response);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao mover o arquivo.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Nenhum arquivo enviado ou erro no envio do arquivo.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Usuário não autorizado']);
}
?>