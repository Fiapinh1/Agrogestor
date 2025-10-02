CREATE DATABASE IF NOT EXISTS agrogestor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agrogestor;


-- Tabela principal de colaboradores (campos essenciais + alguns operacionais)
CREATE TABLE IF NOT EXISTS colaboradores (
id INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(150) NOT NULL,
nome_social VARCHAR(150),
data_nascimento DATE NOT NULL,
cpf VARCHAR(14) NOT NULL UNIQUE,
rg VARCHAR(20),
ctps VARCHAR(30),
pis VARCHAR(20),
endereco TEXT,
telefone VARCHAR(20),
email VARCHAR(120),
contato_emergencia VARCHAR(150),


cargo VARCHAR(100) NOT NULL,
setor VARCHAR(100),
frente VARCHAR(100),
regime VARCHAR(50),
admissao DATE,
salario DECIMAL(10,2),
gestor_imediato VARCHAR(150),
situacao VARCHAR(30) DEFAULT 'Ativo',


cnh VARCHAR(20),
validade_cnh DATE,
certificado_piloto VARCHAR(100),
validade_certificado DATE,
aso DATE,
alergias TEXT,
epi TEXT,


contrato_tipo VARCHAR(50),
contrato_termino DATE,


banco VARCHAR(50),
agencia VARCHAR(10),
conta VARCHAR(20),
chave_pix VARCHAR(100),


criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
atualizado_em TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil ENUM('admin','usuario') NOT NULL DEFAULT 'usuario',
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Unidades (usadas no select da OS)
CREATE TABLE IF NOT EXISTS unidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL UNIQUE
);

-- Exemplo de seed (opcional)
INSERT IGNORE INTO unidades (nome) VALUES ('Base Matriz'), ('Base Norte'), ('Base Sul');

-- Ordens de Serviço
CREATE TABLE IF NOT EXISTS os (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unidade_id INT NOT NULL,
  numero_os VARCHAR(40) NOT NULL,
  fazenda VARCHAR(160) NOT NULL,
  area_ha DECIMAL(10,2) NOT NULL,
  objetivo ENUM('aplicacao_total','aplicacao_localizada','mapeamento','cotesia') NOT NULL,
  produto_categoria ENUM('herbicida','inseticida','fungicida','fertilizante','maturador') NOT NULL,
  insumo_nome VARCHAR(160) NOT NULL,

  -- Coordenadas em graus-minutos-segundos + hemisférios
  lat_grau INT NOT NULL,
  lat_min INT NOT NULL,
  lat_seg DECIMAL(6,3) NOT NULL,
  lat_hemi ENUM('N','S') NOT NULL,

  lon_grau INT NOT NULL,
  lon_min INT NOT NULL,
  lon_seg DECIMAL(6,3) NOT NULL,
  lon_hemi ENUM('E','W') NOT NULL,

  -- Coordenadas decimais (para mapa/consulta)
  lat DECIMAL(10,7) NOT NULL,
  lon DECIMAL(10,7) NOT NULL,

  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_os_unidade FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE RESTRICT,
  INDEX (produto_categoria),
  INDEX (insumo_nome),
  INDEX (lat, lon)
);

-- NOVAS COLUNAS NA TABELA OS
ALTER TABLE os
  ADD COLUMN status ENUM('novo','recebido','planejado','em_execucao','pausado','concluido','cancelado') NOT NULL DEFAULT 'novo' AFTER insumo_nome,
  ADD COLUMN prazo_final DATE NULL AFTER status,
  ADD COLUMN recebido_em DATETIME NULL AFTER prazo_final,
  ADD COLUMN iniciado_em DATETIME NULL AFTER recebido_em,
  ADD COLUMN finalizado_em DATETIME NULL AFTER iniciado_em,
  ADD COLUMN piloto_id INT NULL AFTER finalizado_em,
  ADD COLUMN obs TEXT NULL AFTER piloto_id,
  ADD INDEX (status),
  ADD INDEX (prazo_final),
  ADD INDEX (piloto_id);

-- LOG DE STATUS (opcional, mas recomendo MUITO)
CREATE TABLE IF NOT EXISTS os_status_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  os_id INT NOT NULL,
  status ENUM('novo','recebido','planejado','em_execucao','pausado','concluido','cancelado') NOT NULL,
  user_id INT NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (os_id),
  CONSTRAINT fk_oslog_os FOREIGN KEY (os_id) REFERENCES os(id) ON DELETE CASCADE
);

-- FLAG NO COLABORADOR PARA SABER QUEM É PILOTO
ALTER TABLE colaboradores
  ADD COLUMN is_piloto TINYINT(1) NOT NULL DEFAULT 0 AFTER cargo,
  ADD INDEX (is_piloto);


-- 1) adicionar vínculo obrigatório usuário → colaborador
ALTER TABLE usuarios
  ADD COLUMN colaborador_id INT NOT NULL AFTER id,
  ADD CONSTRAINT fk_usuarios_colaborador
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD UNIQUE KEY uq_usuarios_colaborador (colaborador_id);

-- 2) garantir e-mail único (se ainda não for)
ALTER TABLE usuarios
  ADD UNIQUE KEY uq_usuarios_email (email);

-- (opcional) sincronizar e-mails existentes por script antes de ativar UNIQUE
-- atualize manualmente se tiver conflitos
