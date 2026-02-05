<?php
// File: cleanup_google.php
// Script ini memaksa penghapusan layanan Google yang tidak dipakai

$vendorDir = __DIR__ . '/vendor/google/apiclient-services/src/Google/Service';
$keep = ['Sheets', 'Drive']; // HANYA simpan folder Sheets dan Drive

if (!is_dir($vendorDir)) {
    echo "Folder Google Service tidak ditemukan. Skip cleanup.\n";
    exit(0);
}

echo "Memulai pembersihan Google Services...\n";
$items = scandir($vendorDir);

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;

    // Jika folder ini TIDAK ada di daftar simpan, HAPUS!
    if (!in_array($item, $keep)) {
        $path = $vendorDir . '/' . $item;
        if (is_dir($path)) {
            // Hapus folder beserta isinya secara rekursif
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($path);
        }
    }
}

echo "Pembersihan Selesai. Ukuran vendor telah menyusut.\n";