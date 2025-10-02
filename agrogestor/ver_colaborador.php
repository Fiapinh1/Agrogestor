<?php
// ver_colaborador.php
// Endpoint que retorna os dados de um colaborador em JSON para o modal "Ver"

require_once 'auth.php'; 
requireLogin();

require_once 'conexao.php';

header('Content-Type: application/json; charset=utf-8');

// Validação do ID
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'id inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Busca os dados do colaborador
$sql = "
  SELECT
    nome,
    cargo,
    email,
    telefone,
    admissao,
    setor,
    frente,
    situacao
  FROM colaboradores
  WHERE id = :id
  LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  http_response_code(404);
  echo json_encode(['error' => 'não encontrado'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Função para rotular status de maneira consistente
function labelStatus($s) {
  $s = strtolower((string)$s);
  if ($s === 'ativo')   return 'ativo';
  if ($s === 'inativo') return 'inativo';
  return $s ?: '—';
}

// Monta a resposta
$out = [
  'nome'           => $row['nome']      ?? null,
  'cargo'          => $row['cargo']     ?? null,
  'email'          => $row['email']     ?? null,
  'telefone'       => $row['telefone']  ?? null,

  // Data formatada: dd/mm/yyyy (ou null se vazio)
  'admissao_label' => !empty($row['admissao']) ? date('d/m/Y', strtotime($row['admissao'])) : null,

  // Junta setor e frente no mesmo campo, com " / " quando ambos existem
  'setor_frente'   => trim(
                        (string)($row['setor'] ?? '') .
                        (empty($row['frente']) ? '' : ' / ' . $row['frente'])
                      ),

  // Status amigável
  'status_label'   => labelStatus($row['situacao'] ?? null),
];

// Retorna JSON
echo json_encode($out, JSON_UNESCAPED_UNICODE);
