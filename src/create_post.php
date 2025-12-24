<?php
session_start();
require_once "config.php";

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
                header("location: /dashboard.php");
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
<head>
    <meta charset="UTF-8">
    <title>Tambah Postingan Baru</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/auth.css">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
</head>
<body>
    <div class="main-container">
         <div class="editor-container">
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="auth-form editor-form" enctype="multipart/form-data">
                <h2>Tambah Postingan Baru</h2>
                <?php if ($error): ?><p class="error-message"><?= htmlspecialchars($error) ?></p><?php endif; ?>
                <div class="tab-nav">
                    <button type="button" class="tab-button active" data-tab="tab-konten">Konten</button>
                    <button type="button" class="tab-button" data-tab="tab-pengaturan">Pengaturan</button>
                </div>
                <div id="tab-konten" class="tab-content active">
                    <div class="form-group">
                        <label>Judul</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($title); ?>">
                    </div>    
                    <div class="form-group">
                        <label>Konten</label>
                        <textarea name="content" id="editor" class="form-control" rows="10"><?= htmlspecialchars($content); ?></textarea>
                    </div>
                </div>
                <div id="tab-pengaturan" class="tab-content">
                    <div class="form-group">
                        <label>Kategori</label>
                        <input type="text" name="category" class="form-control" placeholder="Contoh: Teknologi, Berita" value="<?= htmlspecialchars($category); ?>">
                    </div>
                    <div class="form-group">
                        <label>Gambar Utama (Upload)</label>
                        <input type="file" name="post_image" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'editor'): ?>
                        <input type="submit" name="publish" class="auth-button" value="Publikasikan">
                        <input type="submit" name="draft" class="auth-button" value="Simpan Draf" style="background-color: #6c757d;">
                    <?php else: ?>
                        <input type="submit" name="publish" class="auth-button" value="Kirim untuk Review">
                    <?php endif; ?>
                    <a href="/dashboard.php" class="auth-button" style="background-color: #f44336; text-align: center; display: block; margin-top: 10px;">Batal</a>
                </div>
            </form>
        </div>
    </div>
<script>
    CKEDITOR.stylesSet.add('my_styles', [
        { name: 'Indent Paragraf', element: 'p', styles: { 'text-indent': '40px' } }
    ]);
    CKEDITOR.replace( 'editor', {
        stylesSet: 'my_styles',
        extraCss: 'p { text-indent: 40px; }'
    });
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
    });
</script>
</body>
</html>