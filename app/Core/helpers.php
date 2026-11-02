<?php
/* Small global helpers used by controllers and views. */

function config(string $key)
{
    static $cfg = null;
    $cfg ??= require ROOT . '/config/config.php';
    $value = $cfg;
    foreach (explode('.', $key) as $part) {
        $value = $value[$part] ?? null;
    }
    return $value;
}

/** Escape text for HTML output (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Folder the site runs from, e.g. "/cinema/public" under XAMPP, or "" on its own server. */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
    return $base;
}

function url(string $path = '/', array $query = []): string
{
    $u = base_path() . '/' . ltrim($path, '/');
    return $query ? $u . '?' . http_build_query($query) : $u;
}

function asset(string $path): string
{
    $file = ROOT . '/public/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $v;
}

function poster_url(?string $poster): string
{
    if (!$poster) {
        return asset('assets/img/no-poster.svg');
    }
    return preg_match('#^https?://#', $poster) ? $poster : url('uploads/posters/' . $poster);
}

function redirect(string $path): void
{
    // $path is either an app path ("/movies") or a full request URI that already has the base folder.
    $alreadyFull = base_path() !== '' && str_starts_with($path, base_path() . '/');
    header('Location: ' . ($alreadyFull ? $path : url($path)));
    exit;
}

function flash(string $type, string $message = null)
{
    if ($message !== null) {
        $_SESSION['_flash'][$type] = $message;
        return null;
    }
    $msg = $_SESSION['_flash'][$type] ?? null;
    unset($_SESSION['_flash'][$type]);
    return $msg;
}

/** Previous form value after a failed submit. */
function old(string $field, $default = '')
{
    return $_SESSION['_old'][$field] ?? $default;
}

function money($amount): string
{
    return number_format((float) $amount, 2) . ' ' . config('app.currency');
}

function fmt_date(string $date): string
{
    return date('D, j M Y', strtotime($date));
}

function fmt_time(string $time): string
{
    return date('g:i A', strtotime($time));
}

function abort(int $code, string $message = null): void
{
    http_response_code($code);
    $titles = [403 => 'Access denied', 404 => 'Page not found', 405 => 'Method not allowed', 419 => 'Session expired', 500 => 'Something went wrong'];
    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    if ($wantsJson) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $message ?? ($titles[$code] ?? 'Error')]);
        exit;
    }
    $title = $titles[$code] ?? 'Error';
    $content = '<section class="container narrow center error-page"><h1>' . $code . '</h1><h2>' . e($title) . '</h2>'
        . ($message ? '<p>' . e($message) . '</p>' : '')
        . '<a class="btn" href="' . e(url('/')) . '">Back to home</a></section>';
    $pageTitle = $title;
    require ROOT . '/app/Views/layouts/main.php';
    exit;
}

/** Adds class="invalid" and an error line for a form field. */
function field_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<small class="field-error">' . e($errors[$field]) . '</small>' : '';
}

function is_active(string $prefix): string
{
    $path = substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen(base_path())) ?: '/';
    if ($prefix === '/') {
        return $path === '/' ? 'active' : '';
    }
    return str_starts_with($path, $prefix) ? 'active' : '';
}

/** Small inline SVG icons (stroke style). */
function icon(string $name, int $size = 18): string
{
    $paths = [
        'film'     => '<rect x="2" y="3" width="20" height="18" rx="2"/><path d="M7 3v18M17 3v18M2 8h5M2 13h5M2 18h5M17 8h5M17 13h5M17 18h5"/>',
        'ticket'   => '<path d="M3 8a2 2 0 0 0 0 4v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0 0-4V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/><path d="M13 5v2M13 11v2M13 17v2"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'    => '<circle cx="9" cy="8" r="4"/><path d="M1 21a8 8 0 0 1 16 0"/><path d="M17 4a4 4 0 0 1 0 8M23 21a8 8 0 0 0-5-7.4"/>',
        'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'login'    => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'star'     => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
        'pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'edit'     => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'trash'    => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v6M14 11v6"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'eye'      => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>',
        'x'        => '<path d="M18 6 6 18M6 6l12 12"/>',
        'check'    => '<path d="M20 6 9 17l-5-5"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
        'chart'    => '<path d="M3 3v18h18"/><path d="M7 16v-5M12 16V8M17 16v-8"/>',
        'door'     => '<path d="M3 21h18M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17"/><circle cx="15" cy="12" r="1"/>',
        'card'     => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
        'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'home'     => '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'arrow'    => '<path d="M5 12h14M13 5l7 7-7 7"/>',
        'back'     => '<path d="M19 12H5M11 19l-7-7 7-7"/>',
        'printer'  => '<path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'refresh'  => '<path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/>',
    ];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? '') . '</svg>';
}

function status_badge(string $status): string
{
    $map = [
        'Confirmed' => 'green', 'Paid' => 'green', 'Now Showing' => 'green',
        'Pending' => 'amber', 'Unpaid' => 'amber', 'Coming Soon' => 'blue',
        'Cancelled' => 'red', 'Refunded' => 'grey', 'Ended' => 'grey', 'Admin' => 'purple', 'Customer' => 'grey',
    ];
    return '<span class="badge badge-' . ($map[$status] ?? 'grey') . '">' . e($status) . '</span>';
}
