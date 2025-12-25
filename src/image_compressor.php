<?php
/**
 * Image Compressor - Ultra Compress to ~1KB
 *
 * Fungsi ini akan mengkompress gambar menjadi sangat kecil (~1KB)
 * dengan cara:
 * 1. Resize ke ukuran kecil
 * 2. Convert ke WebP dengan quality rendah
 * 3. Progressive compression sampai mencapai target size
 */

class ImageCompressor {

    // Target size dalam bytes (default 30KB)
    private $target_size = 30720; // 30KB

    // Ukuran maksimal gambar (width)
    private $max_width = 600;

    // Quality awal
    private $initial_quality = 85;

    /**
     * Compress image to target size
     *
     * @param string $source_path Path ke file gambar asli
     * @param string $destination_path Path untuk menyimpan hasil kompresi
     * @param int $target_kb Target size dalam KB (default 1KB)
     * @return array ['success' => bool, 'final_size' => int, 'message' => string]
     */
    public function compress($source_path, $destination_path, $target_kb = 1) {
        $this->target_size = $target_kb * 1024;

        // Cek apakah file exists
        if (!file_exists($source_path)) {
            return [
                'success' => false,
                'final_size' => 0,
                'message' => 'File sumber tidak ditemukan'
            ];
        }

        // Deteksi tipe gambar
        $image_info = getimagesize($source_path);
        if ($image_info === false) {
            return [
                'success' => false,
                'final_size' => 0,
                'message' => 'File bukan gambar yang valid'
            ];
        }

        $mime_type = $image_info['mime'];

        // Load gambar berdasarkan tipe
        $source_image = $this->loadImage($source_path, $mime_type);
        if ($source_image === false) {
            return [
                'success' => false,
                'final_size' => 0,
                'message' => 'Gagal memuat gambar'
            ];
        }

        // Resize gambar
        $resized_image = $this->resizeImage($source_image, $image_info[0], $image_info[1]);

        // Compress secara progresif sampai mencapai target
        $result = $this->progressiveCompress($resized_image, $destination_path);

        // Cleanup
        imagedestroy($source_image);
        imagedestroy($resized_image);

        return $result;
    }

    /**
     * Load image berdasarkan mime type
     */
    private function loadImage($path, $mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return false;
        }
    }

    /**
     * Resize image ke ukuran kecil
     */
    private function resizeImage($source_image, $original_width, $original_height) {
        // Hitung dimensi baru (maintain aspect ratio)
        $ratio = $original_width / $original_height;

        if ($original_width > $this->max_width) {
            $new_width = $this->max_width;
            $new_height = floor($this->max_width / $ratio);
        } else {
            $new_width = $original_width;
            $new_height = $original_height;
        }

        // Create new image
        $resized = imagecreatetruecolor($new_width, $new_height);

        // Preserve transparency untuk PNG
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        // Resize
        imagecopyresampled(
            $resized,
            $source_image,
            0, 0, 0, 0,
            $new_width, $new_height,
            $original_width, $original_height
        );

        return $resized;
    }

    /**
     * Progressive compression sampai mencapai target size
     */
    private function progressiveCompress($image, $destination_path) {
        $quality = $this->initial_quality;
        $temp_path = $destination_path . '.tmp';

        // Coba compress dengan quality yang semakin rendah
        for ($q = $quality; $q >= 10; $q -= 5) {
            // Save dengan quality tertentu
            if (function_exists('imagewebp')) {
                // Gunakan WebP untuk kompresi terbaik
                imagewebp($image, $temp_path, $q);
            } else {
                // Fallback ke JPEG
                imagejpeg($image, $temp_path, $q);
            }

            $file_size = filesize($temp_path);

            // Jika sudah mencapai atau di bawah target, stop
            if ($file_size <= $this->target_size) {
                rename($temp_path, $destination_path);

                return [
                    'success' => true,
                    'final_size' => $file_size,
                    'final_size_kb' => round($file_size / 1024, 2),
                    'quality' => $q,
                    'message' => 'Kompresi berhasil'
                ];
            }
        }

        // Jika masih belum mencapai target, coba resize lebih kecil lagi
        $smaller_width = floor($this->max_width * 0.7);
        $new_height = floor(imagesy($image) * 0.7);

        $smaller_image = imagecreatetruecolor($smaller_width, $new_height);
        imagealphablending($smaller_image, false);
        imagesavealpha($smaller_image, true);

        imagecopyresampled(
            $smaller_image,
            $image,
            0, 0, 0, 0,
            $smaller_width, $new_height,
            imagesx($image), imagesy($image)
        );

        // Coba lagi dengan gambar yang lebih kecil
        if (function_exists('imagewebp')) {
            imagewebp($smaller_image, $temp_path, 10);
        } else {
            imagejpeg($smaller_image, $temp_path, 10);
        }

        imagedestroy($smaller_image);

        $file_size = filesize($temp_path);
        rename($temp_path, $destination_path);

        return [
            'success' => true,
            'final_size' => $file_size,
            'final_size_kb' => round($file_size / 1024, 2),
            'quality' => 10,
            'message' => 'Kompresi maksimal (resize + quality minimum)'
        ];
    }

    /**
     * Set target size
     */
    public function setTargetSize($kb) {
        $this->target_size = $kb * 1024;
        return $this;
    }

    /**
     * Set max width
     */
    public function setMaxWidth($width) {
        $this->max_width = $width;
        return $this;
    }

    /**
     * Set initial quality
     */
    public function setInitialQuality($quality) {
        $this->initial_quality = $quality;
        return $this;
    }
}

/**
 * Helper function untuk kompresi cepat
 *
 * @param string $source Path ke file sumber
 * @param string $destination Path ke file tujuan
 * @param int $target_kb Target size dalam KB
 * @return array Result info
 */
function compress_image($source, $destination, $target_kb = 1) {
    $compressor = new ImageCompressor();
    return $compressor->compress($source, $destination, $target_kb);
}

/**
 * Helper untuk compress dan return info
 */
function compress_uploaded_image($uploaded_file, $upload_dir, $target_kb = 1) {
    $compressor = new ImageCompressor();

    // Generate unique filename
    $ext = function_exists('imagewebp') ? 'webp' : 'jpg';
    $filename = uniqid('img_', true) . '.' . $ext;
    $destination = $upload_dir . $filename;

    // Compress
    $result = $compressor->compress($uploaded_file['tmp_name'], $destination, $target_kb);

    if ($result['success']) {
        $result['filename'] = $filename;
        $result['path'] = $destination;
    }

    return $result;
}
?>
