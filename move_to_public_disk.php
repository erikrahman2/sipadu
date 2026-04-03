<?php
// Move documents from local to public disk

$sourceDir = 'storage/app/public_submissions';
$destDir = 'storage/app/public/public_submissions';

if (!is_dir($sourceDir)) {
    die("Source directory does not exist: $sourceDir\n");
}

// Create destination directory if it doesn't exist
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
    echo "Created directory: $destDir\n";
}

// Recursively copy all files
function copyDir($src, $dest) {
    $count = 0;
    if (!is_dir($dest)) {
        mkdir($dest, 0755,true);
    }
    
    $files = scandir($src);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        
        $srcPath = $src . '/' . $file;
        $destPath = $dest . '/' . $file;
        
        if (is_dir($srcPath)) {
            $count += copyDir($srcPath, $destPath);
        } else {
            if(!file_exists($destPath)) {
                copy($srcPath, $destPath);
                $count++;
            }
        }
    }
    return $count;
}

$copied = copyDir($sourceDir, $destDir);
echo "Copied $copied files to public disk\n";

// Verify
$count = count(array_filter(scandir($destDir . '/1'), fn($f) => $f !== '.' && $f !== '..'));
echo "Files in public/public_submissions/1/: $count\n";
