<?php
function validasi_upload_gambar($file)
{
    if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['status' => 'kosong'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => 'error', 'pesan' => 'Gagal mengunggah file. Silakan coba lagi.'];
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return ['status' => 'error', 'pesan' => 'Ukuran file maksimal 2MB.'];
    }

    $tipe_diizinkan = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($tipe_diizinkan[$mime])) {
        return ['status' => 'error', 'pesan' => 'File harus berupa gambar JPG, PNG, atau WEBP.'];
    }

    if (@getimagesize($file['tmp_name']) === false) {
        return ['status' => 'error', 'pesan' => 'File gambar tidak valid atau rusak.'];
    }

    return ['status' => 'ok', 'ext' => $tipe_diizinkan[$mime]];
}

function simpan_upload_gambar($file, $ext, $upload_dir)
{
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $nama_file = bin2hex(random_bytes(8)) . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $nama_file)) {
        return $nama_file;
    }

    return null;
}
