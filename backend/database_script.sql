CREATE DATABASE IF NOT EXISTS ligalatim;
USE ligalatim;
CREATE TABLE IF NOT EXISTS usuarios (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(255),
  usuario VARCHAR(255),
  senha VARCHAR(255),
  token VARCHAR(255),
  papel TINYINT DEFAULT 0
);
CREATE TABLE IF NOT EXISTS administradores (
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_usuario INT,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);
CREATE TABLE IF NOT EXISTS contato (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nome VARCHAR(255),
  email VARCHAR(255),
  mensagem VARCHAR(500),
  visto TINYINT DEFAULT 0,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS formularios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  descricao TEXT,
  visibilidade TINYINT DEFAULT 0,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS questoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  formulario_id INT,
  texto VARCHAR(2048) NOT NULL,
  tipo ENUM(
    'booleano',
    'multipla',
    'multiplas',
    'aberta'
  ) NOT NULL,
  grafico ENUM(
    'barras',
    'colunas',
    'linhas',
    'pizza',
    'area',
    'dispersao',
    'bolhas',
    'pareto',
    'cascata',
    'termometro',
    'radar',
    'caixa'
  ) NOT NULL,
  FOREIGN KEY (formulario_id) REFERENCES formularios(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS imagens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_questao INT,
  caminho VARCHAR(300) NOT NULL,
  FOREIGN KEY (id_questao) REFERENCES questoes(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS opcoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_questao INT,
  texto VARCHAR(255) NOT NULL,
  FOREIGN KEY (id_questao) REFERENCES questoes(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS respostas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_questao INT,
  id_usuario INT,
  resposta TEXT,
  FOREIGN KEY (id_questao) REFERENCES questoes(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS respostas_opcoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_resposta INT,
  id_opcao INT,
  FOREIGN KEY (id_resposta) REFERENCES respostas(id) ON DELETE CASCADE,
  FOREIGN KEY (id_opcao) REFERENCES opcoes(id) ON DELETE CASCADE
);