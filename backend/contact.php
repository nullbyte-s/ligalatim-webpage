<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $mensagem = $_POST['mensagem'];

    // Conexão com o banco de dados
    $dsn = 'sqlite:db.sqlite3';

    try {
        $conexao = new PDO($dsn);
        $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        // Preparar a consulta SQL com placeholders
        $sql = "INSERT INTO contato (nome, email, mensagem) VALUES (:nome, :email, :mensagem)";
        $stmt = $conexao->prepare($sql);
    
        // Vincular os valores aos placeholders
        $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':mensagem', $mensagem, PDO::PARAM_STR);
        $stmt->execute();
    
        $response = ['status' => 'success', 'message' => 'Mensagem enviada com sucesso!'];
        echo json_encode($response);
    } catch (PDOException $e) {
        $response = ['status' => 'error', 'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()];
        echo json_encode($response);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método de requisição inválido.']);
}
?>