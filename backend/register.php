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
    $response = ['status' => 'error', 'message' => 'Erro na conexão com o banco de dados: ' . $conexao->connect_error];
    echo json_encode($response);
    exit();
}

$contarUsuariosSql = "SELECT COUNT(*) FROM usuarios";
$result = $conexao->query($contarUsuariosSql);

if ($result) {
    $quantidadeUsuarios = $result->fetch_row()[0];
} else {
    $response = ['status' => 'error', 'message' => 'Erro ao contar usuários: ' . $conexao->error];
    echo json_encode($response);
    exit();
}

$limiteUsuarios = 50;

if ($quantidadeUsuarios >= $limiteUsuarios) {
    $response = ['status' => 'error', 'message' => 'Limite máximo alcançado. Não é possível cadastrar mais usuários.'];
} else {
    $data = json_decode(file_get_contents("php://input"));
    $usuario = $conexao->real_escape_string($data->usuario);
    $senha = $conexao->real_escape_string($data->senha);
    $nome = $conexao->real_escape_string($data->nome);

    $verificarUsuarioSql = "SELECT COUNT(*) FROM usuarios WHERE usuario = ?";
    $stmtVerificar = $conexao->prepare($verificarUsuarioSql);
    $stmtVerificar->bind_param("s", $usuario);
    $stmtVerificar->execute();
    $stmtVerificar->bind_result($usuarioExistente);
    $stmtVerificar->fetch();
    $stmtVerificar->close();

    if ($usuarioExistente > 0) {
        $response = ['status' => 'error', 'message' => 'Usuário já existe. Escolha outro nome de usuário.'];
    } else {
        $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
        $inserirUsuarioSql = "INSERT INTO usuarios (usuario, senha, nome) VALUES (?, ?, ?)";
        $stmtInserir = $conexao->prepare($inserirUsuarioSql);
        $stmtInserir->bind_param("sss", $usuario, $hashSenha, $nome);

        if ($stmtInserir->execute()) {
            $response = ['status' => 'success', 'message' => 'Usuário cadastrado com sucesso!'];
            $response['redirect'] = 'login.html';
        } else {
            $response = ['status' => 'error', 'message' => 'Erro ao cadastrar usuário: ' . $stmtInserir->error];
        }
        $stmtInserir->close();
    }
}

$conexao->close();

header('Content-Type: application/json');
echo json_encode($response);
?>