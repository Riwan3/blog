<?php
session_start();
require_once "config.php";
require_once "helpers.php";
require_once "image_compressor.php";

function create_slug($title, $link, $post_id = null) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $query = "SELECT id FROM posts WHERE slug = ? AND id != ?";
    $stmt = mysqli_prepare($link, $query);
    $i = 2;
    $base_slug = $slug;
    while(true){
        mysqli_stmt_bind_param($stmt, "si", $slug, $post_id);
        mysqli_stmt_execute($stmt);
        if(mysqli_stmt_get_result($stmt)->num_rows == 0) break;
        $slug = $base_slug . '-' . $i++;
    }
    return $slug;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /login.php');
    exit;
}

$title = $content = $image_url = $slug = $category = $original_title = $status = "";
$error = '';
$post_id = $_GET['id'];

// Fetch existing post
$sql_select = "SELECT title, slug, content, image_url, category, author_id, status FROM posts WHERE id = ?";
if ($stmt_select = mysqli_prepare($link, $sql_select)) {
    mysqli_stmt_bind_param($stmt_select, "i", $post_id);
    if (mysqli_stmt_execute($stmt_select)) {
        $result = mysqli_stmt_get_result($stmt_select);
        if ($result->num_rows == 1) {
            $post = $result->fetch_assoc();
            // Otorisasi: Admin/Editor bisa edit semua, Kontributor hanya miliknya & belum publish
            if (($_SESSION['role'] === 'kontributor' && $_SESSION['id'] !== $post['author_id']) || ($_SESSION['role'] === 'kontributor' && $post['status'] === 'published')) {
                header("Location: /dashboard.php?error=unauthorized");
                exit;
            }
            $title = $original_title = $post['title'];
            $slug = $post['slug'];
            $content = $post['content'];
            $image_url = $post['image_url'];
            $category = $post['category'];
            $status = $post['status'];
        } else {
            $error = "Postingan tidak ditemukan.";
        }
    } else {
        $error = "Gagal mengambil data.";
    }
    mysqli_stmt_close($stmt_select);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = $_POST["content"];
    $category = trim($_POST["category"]);
    if (empty($title)) $error = "Judul tidak boleh kosong.";
    if (empty($content)) $error = "Konten tidak boleh kosong.";

    // Tentukan status baru berdasarkan tombol yang diklik
    $new_status = $status;
    if (isset($_POST['publish']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'editor')) {
        $new_status = 'published';
    } elseif (isset($_POST['draft'])) {
        $new_status = 'draft';
    } elseif (isset($_POST['pending']) || ($_SESSION['role'] === 'kontributor' && !isset($_POST['draft']))) {
        $new_status = 'pending';
    }

    $new_image_path = $image_url;
    if (isset($_FILES["post_image"]) && $_FILES["post_image"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpeg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png", "webp" => "image/webp"];
        $filename = $_FILES["post_image"]["name"];
        $filetype = $_FILES["post_image"]["type"];
        $filesize = $_FILES["post_image"]["size"];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!array_key_exists($ext, $allowed) || !in_array($filetype, $allowed)) {
            $error = "Error: Harap pilih format gambar yang valid (JPG, PNG, GIF, WebP).";
        }

        $maxsize = 10 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $error = "Error: Ukuran file terlalu besar (maks 10MB).";
        }

        if(empty($error)){
            $upload_dir = "uploads/";

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Hapus gambar lama jika ada
            if (!empty($image_url) && file_exists(ltrim($image_url, '/'))) {
                unlink(ltrim($image_url, '/'));
            }

            // Compress gambar ke ~30KB (kualitas lebih baik)
            $compress_result = compress_uploaded_image($_FILES["post_image"], $upload_dir, 30);

            if ($compress_result['success']) {
                $new_image_path = "/" . $compress_result['path'];
            } else {
                $error = "Error: " . $compress_result['message'];
            }
        }
    }

    if (empty($error)) {
        $slug_to_save = ($original_title !== $title) ? create_slug($title, $link, $post_id) : $slug;
        
        $sql_update = "UPDATE posts SET title = ?, slug = ?, content = ?, image_url = ?, category = ?, status = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt, "ssssssi", $title, $slug_to_save, $content, $new_image_path, $category, $new_status, $post_id);
            if (mysqli_stmt_execute($stmt)) {
                // Clear cache
                clear_post_caches();

                // Redirect dengan pesan sesuai role dan status
                if ($_SESSION['role'] === 'kontributor' && $new_status === 'draft') {
                    header("location: /dashboard.php?message=Postingan kamu sudah diupdate, silahkan tunggu editor untuk review");
                } elseif ($new_status === 'published') {
                    header("location: /dashboard.php?message=Postingan berhasil dipublikasikan");
                } else {
                    header("location: /dashboard.php?message=Postingan berhasil diperbarui");
                }
                exit();
            } else {
                $error = "Gagal memperbarui post.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="id">
<?php $page_title = "Edit Postingan"; include "includes/head.php"; ?>

<body>
    <div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header closed-sidebar">
        <?php include "includes/header.php" ?>

        <div class="app-main">
            <?php include "includes/sidebar.php" ?>

            <div class="app-main__outer">
                <div class="app-main__inner">
                    <div class="app-page-title">
                        <div class="page-title-wrapper">
                            <div class="page-title-heading">
                                <div class="page-title-icon">
                                    <i class="pe-7s-note2 icon-gradient bg-mean-fruit"></i>
                                </div>
                                <div>Edit Postingan
                                    <div class="page-title-subheading">Perbarui postingan yang sudah ada</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="main-card mb-3 card">
                                <div class="card-header">Form Edit Postingan</div>
                                <div class="card-body">
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?= htmlspecialchars($error) ?>
                                        </div>
                                    <?php endif; ?>

                                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $post_id; ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                        <div class="position-relative form-group">
                                            <label for="title">Judul Postingan</label>
                                            <input name="title" id="title" type="text" class="form-control" required value="<?= htmlspecialchars($title); ?>">
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="category">Kategori</label>
                                            <input name="category" id="category" type="text" class="form-control" placeholder="Contoh: Teknologi, Berita" value="<?= htmlspecialchars($category); ?>">
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="post_image">Ganti Gambar Utama (Opsional)</label>
                                            <input name="post_image" id="post_image" type="file" class="form-control-file" accept="image/*">
                                            <small class="form-text text-muted">Format: JPG, PNG, GIF. Maksimal 5MB</small>
                                            <?php if (!empty($image_url)): ?>
                                                <div class="mt-2">
                                                    <img src="<?= htmlspecialchars($image_url); ?>" alt="Gambar saat ini" style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px;">
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="editor">Konten</label>
                                            <textarea name="content" id="editor" class="form-control" rows="10" required><?= htmlspecialchars($content); ?></textarea>
                                        </div>

                                        <div class="mt-4">
                                            <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'editor'): ?>
                                                <button type="submit" name="publish" class="btn btn-success">
                                                    <i class="pe-7s-check"></i> Publikasikan
                                                </button>
                                                <button type="submit" name="draft" class="btn btn-secondary">
                                                    <i class="pe-7s-diskette"></i> Simpan Draf
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="pending" class="btn btn-primary">
                                                    <i class="pe-7s-paper-plane"></i> Kirim untuk Review
                                                </button>
                                                <button type="submit" name="draft" class="btn btn-secondary">
                                                    <i class="pe-7s-diskette"></i> Simpan Draf
                                                </button>
                                            <?php endif; ?>
                                            <a href="/dashboard.php" class="btn btn-danger">
                                                <i class="pe-7s-close"></i> Batal
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php" ?>

    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
    <script>
        CKEDITOR.stylesSet.add('my_styles', [
            { name: 'Indent Paragraf', element: 'p', styles: { 'text-indent': '40px' } }
        ]);
        CKEDITOR.replace('editor', {
            stylesSet: 'my_styles',
            extraCss: 'p { text-indent: 40px; }',
            height: 400
        });
    </script>

<!--
  Duta Damai Kalimantan Selatan - Blog System
  © 2025 Duta Damai Kalimantan Selatan

  Menggunakan komponen dari CMS Jawara
  © 2020 Djadjoel (MIT License)
  Repository: https://github.com/djadjoel/cmsjawara
-->
</body>
</html>
