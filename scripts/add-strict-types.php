<?php

declare(strict_types=1);

/**
 * One-off: add declare(strict_types=1); to project PHP files missing it.
 * Excludes vendor, node_modules, and Blade views.
 */

$root = dirname(__DIR__);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
);

$updated = 0;
$skipped = 0;

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();

    if (str_contains($path, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)
        || str_contains($path, DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR)
        || str_contains($path, DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR)
        || str_contains($path, DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'add-strict-types.php')) {
        continue;
    }

    $content = file_get_contents($path);

    if ($content === false || ! str_starts_with($content, '<?php')) {
        $skipped++;

        continue;
    }

    if (preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $content) === 1) {
        $skipped++;

        continue;
    }

    $replacement = preg_replace(
        '/^<\?php\r?\n/',
        "<?php\n\ndeclare(strict_types=1);\n\n",
        $content,
        1,
        $count,
    );

    if ($count !== 1 || $replacement === null) {
        fwrite(STDERR, "Could not patch: {$path}\n");
        $skipped++;

        continue;
    }

    file_put_contents($path, $replacement);
    $updated++;
}

echo "Updated: {$updated}, skipped: {$skipped}\n";
