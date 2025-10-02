<?php
// auth.php
// Helpers de sessão, flash messages e guardas de acesso (login/roles).

declare(strict_types=1);

// Inicia a sessão apenas uma vez
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* ===================== FLASH MESSAGES ===================== */
// Tornamos as funções idempotentes para evitar "Cannot redeclare"
if (!function_exists('flash')) {
  function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
  }
}
if (!function_exists('get_flashes')) {
  function get_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
  }
}

/* ===================== PATH HELPER ===================== */
if (!function_exists('auth_root_prefix')) {
  // Retorna "../" quando estamos dentro de /usuarios/ ou /clientes/, senão "".
  function auth_root_prefix(): string {
    $path = $_SERVER['PHP_SELF'] ?? '';
    return (
      str_contains($path, '/usuarios/') ||
      str_contains($path, '/clientes/') ||
      str_contains($path, '/os/')
    ) ? '../' : '';
  }
}

/* ===================== USUÁRIO ATUAL ===================== */
if (!function_exists('user')) {
  function user(): ?array {
    // Esperado: ['id'=>..., 'nome'=>..., 'email'=>..., 'perfil'=>'admin'|'usuario', 'ativo'=>1|0]
    return $_SESSION['user'] ?? null;
  }
}
if (!function_exists('isLogged')) {
  function isLogged(): bool {
    $u = user();
    // Se o campo 'ativo' existir, respeita; senão assume true
    return is_array($u) && (!isset($u['ativo']) || (bool)$u['ativo'] === true);
  }
}

/* ===================== GUARDA DE ROTAS ===================== */
if (!function_exists('requireLogin')) {
  function requireLogin(): void {
    if (!isLogged()) {
      $root = auth_root_prefix();
      $return = urlencode($_SERVER['REQUEST_URI'] ?? '');
      header("Location: {$root}login.php?r={$return}");
      exit;
    }
  }
}

if (!function_exists('hasRole')) {
  function hasRole(string|array $roles): bool {
    $u = user();
    if (!$u) return false;
    $perfil = $u['perfil'] ?? 'usuario';
    $roles = (array)$roles;
    return in_array($perfil, $roles, true);
  }
}

if (!function_exists('requireRole')) {
  function requireRole(string|array $roles): void {
    if (!isLogged()) { requireLogin(); } // redireciona para login
    if (!hasRole($roles)) {
      http_response_code(403);
      echo 'Acesso negado.';
      exit;
    }
  }
}

/* ===================== UTILITÁRIOS DE LOGIN/LOGOUT ===================== */
if (!function_exists('loginUser')) {
  // Use no seu login.php após validar o usuário no banco
  function loginUser(array $u): void {
    $_SESSION['user'] = $u;
  }
}

if (!function_exists('logoutUser')) {
  // Use no logout.php
  function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
      $p = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
  }
}
