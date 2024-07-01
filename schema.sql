CREATE DATABASE cron_dash;
USE cron_dash;
CREATE TABLE tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    comando TEXT NOT NULL,
    intervalo VARCHAR(100) DEFAULT '0 * * * *',
    descricao TEXT,
    ativo TINYINT DEFAULT 1,
    ultima_exec DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarefa_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'ok',
    saida TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tarefa_id) REFERENCES tarefas(id)
);
