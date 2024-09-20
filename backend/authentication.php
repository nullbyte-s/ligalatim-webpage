<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$data = json_decode(file_get_contents("php://input"));
$headers = getallheaders();

function verificarToken($conexao) {
    global $data;
    global $headers;
    $token = $data->token ?? (isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null);

    if ($token) {
        $sql = "SELECT usuario, papel FROM usuarios WHERE token = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return ['status' => 'success', 'usuario' => $row['usuario'], 'papel' => $row['papel']];
        } else {
            return ['status' => 'error', 'message' => 'Token inválido ou inexistente'];
        }
    } else {
        return ['status' => 'error', 'message' => 'Token não fornecido'];
    }

    $conexao->close();
    return null;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        if (isset($headers['Authorization'])) {
            return;
        }

        $usuario = isset($data->usuario) ? $data->usuario : null;
        $senha = isset($data->senha) ? $data->senha : null;

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
            error_log("Enviando resposta: " . json_encode($response));
            echo json_encode($response);
            exit();
        }

        $sql = "SELECT id, senha FROM usuarios WHERE usuario = ?";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!empty($data->usuario) && !empty($data->senha)) {
            if ($row !== null) {
                $hashSenhaArmazenada = $row['senha'];

                if (password_verify($senha, $hashSenhaArmazenada)) {
                    $token = password_hash(uniqid(rand(), true), PASSWORD_DEFAULT);
                    $sql = "UPDATE usuarios SET token = ? WHERE usuario = ?";
                    $stmt = $conexao->prepare($sql);
                    $stmt->bind_param("ss", $token, $usuario);

                    if ($stmt->execute()) {
                        $userId = $row['id'];
                        $sql = "SELECT COUNT(*) AS userLevel FROM administradores WHERE id_usuario = ?";
                        $stmt = $conexao->prepare($sql);
                        $stmt->bind_param("i", $userId);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $adminCheck = $result->fetch_assoc();
                        $papel = $adminCheck['userLevel'] > 0;
                        $response = [
                            'status' => 'success',
                            'message' => 'Login bem-sucedido!',
                            'token' => $token,
                            'papel' => $papel,
                            'redirect' => 'authenticated.html',
                            'dropdown' => 'Usuário'
                        ];
                        $_SESSION['usuario'] = $usuario;
                        $_SESSION['userLevel'] = $papel;
                    } else {
                        header('Content-Type: application/json');
                        $response = ['status' => 'error', 'message' => 'Erro ao atualizar o token: ' . $stmt->error];
                        error_log(json_encode($response));
                    }
                } else {
                    $response = ['status' => 'error', 'message' => 'Usuário ou senha incorretos.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Usuário ou senha incorretos.'];
            }
            header('Content-Type: application/json');
            echo json_encode($response);
        } elseif (!empty($data->token)) {
            $response = verificarToken($conexao);
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Dados insuficientes para a operação']);
        }
        $conexao->close();
        break;
    
    case 'GET':
        if (isset($_GET["logout"])) {
            session_destroy();
            exit; 
        }
        return;

    default:
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Autorização necessária']);
        break;
}
?>