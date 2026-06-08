<?php
/**
 * Tiny admin helpers reused across master-data pages.
 */
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/** Render a paginator (?page=N). */
function paginator(int $total, int $perPage, int $current, string $baseQs = ''): string
{
    $pages = max(1, (int)ceil($total / $perPage));
    if ($pages <= 1) return '';
    $sep = $baseQs !== '' ? '&' : '';
    $out = '<div class="between mt-4"><span class="text-sm text-muted">'
         . sprintf('Halaman %d dari %d · %d data', $current, $pages, $total)
         . '</span><div class="row" style="gap: var(--sp-1); flex: 0 0 auto">';
    $mk = function (int $p, string $label, bool $disabled = false, bool $active = false) use ($baseQs, $sep) {
        if ($disabled) return '<span class="btn btn-secondary btn-sm" style="opacity:.4">' . esc($label) . '</span>';
        $cls = $active ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm';
        return '<a class="' . $cls . '" href="?' . esc($baseQs . $sep . 'page=' . $p) . '">' . esc($label) . '</a>';
    };
    $out .= $mk(max(1, $current - 1), '‹', $current <= 1);
    $start = max(1, $current - 2);
    $end   = min($pages, $current + 2);
    for ($i = $start; $i <= $end; $i++) {
        $out .= $mk($i, (string)$i, false, $i === $current);
    }
    $out .= $mk(min($pages, $current + 1), '›', $current >= $pages);
    $out .= '</div></div>';
    return $out;
}

/** Build a query string from current GET params, optionally overriding/removing keys. */
function qs(array $overrides = []): string
{
    $q = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($q[$k]); else $q[$k] = $v;
    }
    unset($q['page']); // pagination always rebuilds
    return http_build_query($q);
}

/** Filter input integer or null. */
function int_or_null($v): ?int
{
    if ($v === null || $v === '' || !is_numeric($v)) return null;
    return (int)$v;
}

/** Check if a NIY is unique (for create/edit). */
function niy_unique(string $niy, ?int $exceptId = null): bool
{
    $sql = "SELECT id FROM users WHERE niy = :n";
    $params = ['n' => $niy];
    if ($exceptId) { $sql .= " AND id <> :id"; $params['id'] = $exceptId; }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() === false;
}
