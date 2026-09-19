-- {{EMPRESA}} Painel Comercial — schema MySQL para Hostinger (hospedagem compartilhada)
-- Importe este arquivo em hPanel > Bancos de dados > phpMyAdmin, na base já criada.

CREATE TABLE IF NOT EXISTS contacts (
  id VARCHAR(40) PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  empresa VARCHAR(255) DEFAULT '',
  cargo VARCHAR(255) DEFAULT '',
  telefone VARCHAR(60) NOT NULL,
  email VARCHAR(255) DEFAULT '',
  origem VARCHAR(60) NOT NULL,
  disc VARCHAR(10) DEFAULT '',
  vendedor VARCHAR(120) NOT NULL,
  tags TEXT,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  api_token VARCHAR(64) NOT NULL UNIQUE,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Conta inicial de administrador — NÃO rode este INSERT com estes valores de
-- exemplo. Gere o hash da senha e um token aleatório antes (veja README.md,
-- seção "Login individual por pessoa") e substitua os placeholders abaixo.
-- Depois de logar, use "Gerenciar equipe" dentro do próprio painel para
-- cadastrar o restante da equipe — não precisa editar SQL de novo.
INSERT IGNORE INTO users (nome, email, password_hash, api_token, is_admin, created_at) VALUES
  ('SEU NOME', 'seu-email@exemplo.com.br', 'COLE_AQUI_O_HASH_BCRYPT_DA_SENHA', 'COLE_AQUI_UM_TOKEN_ALEATORIO', 1, NOW());

CREATE TABLE IF NOT EXISTS access_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(64) DEFAULT '',
  ocorrido_em DATETIME NOT NULL,
  INDEX idx_access_log_data (ocorrido_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sellers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Substitua pelos nomes reais da equipe de vendas antes de rodar este script
-- (mesma lista de nomes que vai virar contas de usuário mais abaixo).
INSERT IGNORE INTO sellers (nome) VALUES
  {{VENDEDORES_SQL_VALUES}};

CREATE TABLE IF NOT EXISTS interactions (
  id VARCHAR(40) PRIMARY KEY,
  contact_id VARCHAR(40) NOT NULL,
  data_hora DATETIME NOT NULL,
  canal VARCHAR(60) NOT NULL,
  resumo TEXT NOT NULL,
  temperatura VARCHAR(20) NOT NULL,
  proxima_acao VARCHAR(255) DEFAULT '',
  data_followup DATE NULL,
  pilar VARCHAR(60) NOT NULL,
  produto VARCHAR(255) NOT NULL,
  valor DECIMAL(12,2) DEFAULT 0,
  estagio VARCHAR(30) NOT NULL,
  vendedor_registro VARCHAR(120) DEFAULT '',
  created_at DATETIME NOT NULL,
  followup_email_sent_at DATETIME NULL,
  CONSTRAINT fk_interactions_contact FOREIGN KEY (contact_id)
    REFERENCES contacts(id) ON DELETE CASCADE,
  INDEX idx_interactions_contact (contact_id),
  INDEX idx_interactions_followup (data_followup)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS prospects (
  id VARCHAR(40) PRIMARY KEY,
  place_id VARCHAR(120) NOT NULL UNIQUE,
  nome VARCHAR(255) NOT NULL,
  endereco VARCHAR(500) DEFAULT '',
  cidade VARCHAR(120) DEFAULT '',
  uf VARCHAR(2) DEFAULT '',
  ramo VARCHAR(255) DEFAULT '',
  telefone VARCHAR(60) DEFAULT '',
  email VARCHAR(255) DEFAULT '',
  site VARCHAR(255) DEFAULT '',
  rating DECIMAL(2,1) DEFAULT NULL,
  fonte VARCHAR(30) NOT NULL DEFAULT 'google_maps',
  cnpj VARCHAR(20) DEFAULT '',
  razao_social VARCHAR(255) DEFAULT '',
  data_abertura DATE NULL,
  capital_social DECIMAL(14,2) NULL,
  situacao_cadastral VARCHAR(30) DEFAULT '',
  porte_empresa VARCHAR(120) DEFAULT '',
  socios TEXT,
  vendedor VARCHAR(120) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'novo',
  resposta TEXT,
  mensagem_sugerida TEXT,
  contact_id VARCHAR(40) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_prospects_vendedor (vendedor),
  INDEX idx_prospects_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migração para bancos que já tinham a tabela prospects antes destes campos:
-- ALTER TABLE prospects
--   ADD COLUMN uf VARCHAR(2) DEFAULT '',
--   ADD COLUMN fonte VARCHAR(30) NOT NULL DEFAULT 'google_maps',
--   ADD COLUMN cnpj VARCHAR(20) DEFAULT '',
--   ADD COLUMN razao_social VARCHAR(255) DEFAULT '',
--   ADD COLUMN data_abertura DATE NULL,
--   ADD COLUMN capital_social DECIMAL(14,2) NULL,
--   ADD COLUMN situacao_cadastral VARCHAR(30) DEFAULT '',
--   ADD COLUMN porte_empresa VARCHAR(120) DEFAULT '',
--   ADD COLUMN socios TEXT;
