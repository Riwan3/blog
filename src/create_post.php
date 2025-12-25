<?php
session_start();
require_once "config.php";
require_once "helpers.php";

// Fungsi untuk membuat slug dari judul
function create_slug($title, $link) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $query = "SELECT id FROM posts WHERE slug = ?";
    $stmt = mysqli_prepare($link, $query);
    $i = 2;
    $base_slug = $slug;
    while(true){
        mysqli_stmt_bind_param($stmt, "s", $slug);
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

$title = $content = $image_url = $category = "";
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $content = $_POST["content"];
    $category = trim($_POST["category"]);
    if (empty($title)) $error = "Judul tidak boleh kosong.";
    if (empty($content)) $error = "Konten tidak boleh kosong.";

    // Tentukan status berdasarkan tombol yang diklik dan peran pengguna
    $status = 'draft'; // Default status
    if (isset($_POST['publish'])) {
        if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'editor') {
            $status = 'published';
        } else {
            $status = 'pending';
        }
    } elseif (isset($_POST['draft'])) {
        $status = 'draft';
    } elseif ($_SESSION['role'] === 'kontributor') {
        $status = 'pending';
    }


    // Proses upload gambar jika ada
    if (isset($_FILES["post_image"]) && $_FILES["post_image"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpeg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"];
        $filename = $_FILES["post_image"]["name"];
        $filetype = $_FILES["post_image"]["type"];
        $filesize = $_FILES["post_image"]["size"];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!array_key_exists($ext, $allowed) || !in_array($filetype, $allowed)) {
            $error = "Error: Harap pilih format gambar yang valid (JPG, PNG, GIF).";
        }

        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $error = "Error: Ukuran file terlalu besar (maks 5MB).";
        }

        if(empty($error)){
            $upload_dir = "uploads/";
            $new_filename = uniqid('post_img_', true) . '.' . $ext;
            $destination = $upload_dir . $new_filename;

            if(move_uploaded_file($_FILES["post_image"]["tmp_name"], $destination)){
                $image_url = "/" . $destination;
            } else{
                $error = "Error: Gagal memindahkan file yang di-upload.";
            }
        }
    } elseif (isset($_FILES["post_image"]) && $_FILES["post_image"]["error"] != UPLOAD_ERR_NO_FILE) {
        $error = "Error saat meng-upload file. Kode: " . $_FILES["post_image"]["error"];
    }


    if (empty($error)) {
        $slug = create_slug($title, $link);
        $sql = "INSERT INTO posts (title, slug, content, image_url, category, author_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssis", $title, $slug, $content, $image_url, $category, $_SESSION["id"], $status);
            if (mysqli_stmt_execute($stmt)) {
                // Clear cache
                clear_post_caches();

                // Redirect dengan pesan sesuai role dan status
                if ($_SESSION['role'] === 'kontributor' && $status === 'draft') {
                    header("location: /dashboard.php?message=Postingan kamu sudah terkirim, silahkan tunggu editor untuk review");
                } elseif ($status === 'published') {
                    header("location: /dashboard.php?message=Postingan berhasil dipublikasikan");
                } else {
                    header("location: /dashboard.php?message=Postingan berhasil disimpan");
                }
                exit();
            } else {
                $error = "Gagal menyimpan post.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="id">
<?php $page_title = "Tambah Postingan"; include "includes/head.php"; ?>

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
                                <div>Tambah Postingan Baru
                                    <div class="page-title-subheading">Buat postingan blog baru</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="main-card mb-3 card">
                                <div class="card-header">Form Postingan</div>
                                <div class="card-body">
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?= htmlspecialchars($error) ?>
                                        </div>
                                    <?php endif; ?>

                                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                        <div class="position-relative form-group">
                                            <label for="title">Judul Postingan</label>
                                            <input name="title" id="title" type="text" class="form-control" required value="<?= htmlspecialchars($title); ?>">
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="category">Kategori</label>
                                            <input name="category" id="category" type="text" class="form-control" placeholder="Contoh: Teknologi, Berita" value="<?= htmlspecialchars($category); ?>">
                                        </div>

                                        <div class="position-relative form-group">
                                            <label for="post_image">Gambar Utama</label>
                                            <input name="post_image" id="post_image" type="file" class="form-control-file" accept="image/*">
                                            <small class="form-text text-muted">Format: JPG, PNG, GIF. Maksimal 5MB</small>
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
                                                <button type="submit" name="publish" class="btn btn-primary">
                                                    <i class="pe-7s-paper-plane"></i> Kirim untuk Review
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