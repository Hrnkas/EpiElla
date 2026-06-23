<?php

/**
 * PHP built-in server router for local native dev.
 * Usage: php -S HOST:PORT -t api/public api/public/router.php
 *
 * - /api/*     → Epiphany (index.php)
 * - /*.html    → web/dist/production/*.html
 * - /js|assets|images/*, /sw.js, /site.webmanifest → web/dist/
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$distRoot = $projectRoot . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'dist';
$productionRoot = $distRoot . DIRECTORY_SEPARATOR . 'production';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// --- API (mirror nginx: /api/foo → __route__=/foo) ---
if (preg_match('#^/api/(.*)$#', $uri, $m)) {
    $_GET['__route__'] = '/' . $m[1];
    require __DIR__ . '/index.php';
    return true;
}

if (!is_dir($distRoot)) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Frontend not built. Run: cd web && npm install && npm run build\n";
    return true;
}

// --- Root → index.html if present in production build ---
if ($uri === '/' || $uri === '') {
    $index = $productionRoot . DIRECTORY_SEPARATOR . 'index.html';
    if (is_file($index)) {
        serveFile($index);
        return true;
    }
}

// --- HTML pages at /pagename.html (from dist/production/) ---
if (preg_match('#^/([A-Za-z0-9_-]+\.html)$#', $uri, $m)) {
    $candidate = $productionRoot . DIRECTORY_SEPARATOR . $m[1];
    if (is_file($candidate)) {
        serveFile($candidate);
        return true;
    }
}

// --- Static assets from dist root ---
$staticPrefixes = ['js', 'assets', 'images'];
foreach ($staticPrefixes as $prefix) {
    $needle = '/' . $prefix . '/';
    if (str_starts_with($uri, $needle)) {
        $file = safeDistFile($distRoot, $uri);
        if ($file !== null) {
            serveFile($file);
            return true;
        }
    }
}

$rootAssets = ['/sw.js', '/site.webmanifest'];
if (in_array($uri, $rootAssets, true)) {
    $file = safeDistFile($distRoot, $uri);
    if ($file !== null) {
        serveFile($file);
        return true;
    }
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Not found: {$uri}\n";
return true;

function safeDistFile(string $distRoot, string $uriPath): ?string
{
    $relative = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $uriPath), DIRECTORY_SEPARATOR);
    $full = $distRoot . DIRECTORY_SEPARATOR . $relative;
    $realDist = realpath($distRoot);
    $realFile = realpath($full);

    if ($realDist === false || $realFile === false || !is_file($realFile)) {
        return null;
    }

    if (!str_starts_with($realFile, $realDist . DIRECTORY_SEPARATOR)) {
        return null;
    }

    return $realFile;
}

function serveFile(string $path): void
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = [
        'html' => 'text/html; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'webmanifest' => 'application/manifest+json',
    ];

    header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
    readfile($path);
}
