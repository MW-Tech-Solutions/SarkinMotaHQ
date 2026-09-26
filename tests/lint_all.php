<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/..');
$iterator = new RecursiveIteratorIterator($dir);
$errors = 0;
$checked = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $checked++;
        $path = $file->getPathname();
        $cmd = '"C:\\xampp\\php\\php.exe" -l ' . escapeshellarg($path);
        exec($cmd, $output, $return);
        if ($return !== 0) {
            echo "SYNTAX ERROR in: {$path}\n";
            $errors++;
        }
    }
}

echo "Linted {$checked} PHP files. Errors: {$errors}.\n";
if ($errors > 0) exit(1);

