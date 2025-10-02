<?php
// clientes/ver_cliente.php
require '../auth.php'; requireLogin();
require '../conexao.php'; require '../utils.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // valida id
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    // busque apenas campos “não sigilosos”
    $sql = "SELECT id, tipo_cliente, tipo_pessoa, razao_social, nome_fantasia,
                   cpf_cnpj, email, telefone, cidade, uf, status
            FROM clientes
            WHERE id = :id
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([':id' => $id]);
    $r = $st->fetch(PDO::FETCH_ASSOC);

    if (!$r) {
        http_response_code(404);
        echo json_encode(['error' => 'Cliente não encontrado']);
        exit;
    }

    // Helpers para rotulagem/máscara
    $tipoPessoa = ($r['tipo_pessoa'] === 'juridica' ? 'PJ' : 'PF');
    $tipoLabel  = ucfirst((string)$r['tipo_cliente']) . " ({$tipoPessoa})";

    // máscara básica CPF/CNPJ
    $doc = preg_replace('/\D+/', '', (string)$r['cpf_cnpj']);
    if (strlen($doc) === 11) {
        // CPF 000.000.000-00
        $docMask = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
    } elseif (strlen($doc) === 14) {
        // CNPJ 00.000.000/0000-00
        $docMask = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
    } else {
        $docMask = $r['cpf_cnpj'];
    }

    // status amigável (se quiser, pode mapear cores no front)
    $statusLabel = $r['status'];

    // monte o payload do modal (nomes batem com o JS do listar_clientes.php)
    $out = [
        'id'             => (int)$r['id'],
        'razao_social'   => $r['razao_social'],
        'fantasia'       => $r['nome_fantasia'],
        'tipo_label'     => $tipoLabel,
        'documento_mask' => $docMask,
        'cidade'         => $r['cidade'],
        'uf'             => $r['uf'],
        'status_label'   => $statusLabel,
        'contato'        => null,                  // adapte se tiver campo na tabela (ex.: $r['contato'])
        'email'          => $r['email'],
        'telefone'       => $r['telefone'],
    ];

    echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno', 'msg' => $e->getMessage()]);
}
