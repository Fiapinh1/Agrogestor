CREATE TABLE IF NOT EXISTS clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo_cliente ENUM('usina','produtor','cliente') NOT NULL,
  tipo_pessoa ENUM('juridica','fisica') NOT NULL,
  razao_social VARCHAR(160) NOT NULL,
  nome_fantasia VARCHAR(160),
  cpf_cnpj VARCHAR(20) NOT NULL UNIQUE,
  ie_rg VARCHAR(30),
  ie_isento TINYINT(1) NOT NULL DEFAULT 0,
  im VARCHAR(30),

  responsavel VARCHAR(120),
  telefone VARCHAR(20),
  whatsapp VARCHAR(20),
  email VARCHAR(160),
  site VARCHAR(160),

  cep CHAR(9),
  endereco VARCHAR(160),
  numero VARCHAR(20),
  complemento VARCHAR(60),
  bairro VARCHAR(80),
  cidade VARCHAR(80),
  uf CHAR(2),

  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),

  area_total_ha DECIMAL(10,2),
  culturas VARCHAR(200),
  safra VARCHAR(20),

  cond_pagto VARCHAR(60),
  limite_credito DECIMAL(12,2),
  dia_faturamento TINYINT,
  email_nfe VARCHAR(160),

  obs TEXT,
  status ENUM('ativo','suspenso','inativo') NOT NULL DEFAULT 'ativo',

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  criado_por INT NULL,
  atualizado_por INT NULL,
  CONSTRAINT fk_cli_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_cli_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS cliente_contatos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  nome VARCHAR(120) NOT NULL,
  cargo VARCHAR(80),
  tipo ENUM('operacional','financeiro','comercial','outro') DEFAULT 'outro',
  telefone VARCHAR(20),
  email VARCHAR(160),
  whatsapp VARCHAR(20),
  obs VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ctt_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  INDEX (cliente_id)
);


CREATE TABLE IF NOT EXISTS propriedades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  nome VARCHAR(120) NOT NULL,
  area_ha DECIMAL(10,2),
  municipio VARCHAR(80),
  uf CHAR(2),
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  obs VARCHAR(255),
  CONSTRAINT fk_prop_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  INDEX (cliente_id)
);


CREATE TABLE IF NOT EXISTS cliente_documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  tipo ENUM('contrato','licenca_ambiental','art','outro') NOT NULL,
  arquivo VARCHAR(255),
  validade DATE,
  obs VARCHAR(255),
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cdoc_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
  INDEX (cliente_id)
);
