<?php

/**
 * PostToolUse hook: validates Blade views against docs/REPOSITORY_PATTERN.md
 * (rule 10 — Blade templates only bind variables, no PHP logic) after they
 * are written or edited.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it is a *.blade.php file, and prints a
 * hook-JSON response with any violations found.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! str_ends_with($filePath, '.blade.php') || ! is_file($filePath)) {
    exit(0);
}

$code = file_get_contents($filePath);

$violations = validateBlade($code);

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Blade pattern check (docs/REPOSITORY_PATTERN.md, rule 10) found issues in {$filePath}:\n{$list}";

echo json_encode([
    'systemMessage' => $message,
    'hookSpecificOutput' => [
        'hookEventName' => 'PostToolUse',
        'additionalContext' => $message,
    ],
]);

exit(0);

/**
 * @return array<int, string>
 */
function validateBlade(string $code): array
{
    $violations = [];

    if (preg_match_all('/@php(.*?)@endphp/s', $code, $matches)) {
        foreach ($matches[1] as $block) {
            $trimmed = trim($block);
            if ($trimmed !== '') {
                $violations[] = '`@php ... @endphp` block contains logic; Blade must only bind pre-computed variables, not compute them.';
                break;
            }
        }
    }

    if (preg_match('/<\?php(?!\s*\/\/\s*no-op)/', $code)) {
        $violations[] = 'Raw `<?php ... ?>` tag found; Blade must only bind pre-computed variables, not run PHP logic.';
    }

    foreach (directModelCallsInBlade($code) as [$class, $method]) {
        $violations[] = "Blade calls `{$class}::{$method}(...)` directly; queries/model access must happen in the Livewire component or Service, not the view.";
    }

    return array_unique($violations);
}

/**
 * @return array<int, array{0: string, 1: string}>
 */
function directModelCallsInBlade(string $code): array
{
    $queryMethods = ['query', 'where', 'whereHas', 'create', 'find', 'findOrFail', 'update', 'delete', 'destroy', 'with', 'all', 'firstOrFail', 'first', 'table', 'sum', 'count', 'get'];

    preg_match_all('/\b([A-Z][A-Za-z0-9_]*)::(' . implode('|', $queryMethods) . ')\s*\(/', $code, $matches, PREG_SET_ORDER);

    $calls = [];
    foreach ($matches as $match) {
        $class = $match[1];
        if (in_array($class, ['self', 'static', 'parent', 'Str', 'Arr', 'Auth', 'Route', 'Storage', 'Carbon', 'Cache', 'Session'], true)) {
            continue;
        }
        $calls[] = [$class, $match[2]];
    }

    return $calls;
}
