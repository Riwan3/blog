<?php
session_start();
require_once "config.php";

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
        // ... (logika upload gambar tetap sama) ...
    }

    if (empty($error)) {
        $slug_to_save = ($original_title !== $title) ? create_slug($title, $link, $post_id) : $slug;
        
        $sql_update = "UPDATE posts SET title = ?, slug = ?, content = ?, image_url = ?, category = ?, status = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql_update)) {
            mysqli_stmt_bind_param($stmt, "ssssssi", $title, $slug_to_save, $content, $new_image_path, $category, $new_status, $post_id);
            if (mysqli_stmt_execute($stmt)) {
                header("location: /dashboard.php");
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
<head>
    <meta charset="UTF-8">
    <title>Edit Postingan</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="/auth.css">
    <link rel="icon" href="/assets/default_post_image.jpeg" type="image/jpeg">
    <script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
</head>
<body>
    <div class="main-container">
         <div class="editor-container">
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $post_id; ?>" method="post" class="auth-form editor-form" enctype="multipart/form-data">
                <h2>Edit Postingan</h2>
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
                        <label>Ganti Gambar Utama (Opsional)</label>
                        <input type="file" name="post_image" class="form-control">
                        <?php if (!empty($image_url)): ?>
                            <p>Gambar saat ini:</p>
                            <img src="<?= htmlspecialchars($image_url); ?>" alt="Gambar saat ini" style="max-width: 200px; height: auto; margin-top: 10px;">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'editor'): ?>
                        <input type="submit" name="publish" class="auth-button" value="Publikasikan">
                        <input type="submit" name="draft" class="auth-button" value="Simpan Draf" style="background-color: #6c757d;">
                    <?php else: // Kontributor ?>
                        <input type="submit" name="pending" class="auth-button" value="Kirim untuk Review">
                        <input type="submit" name="draft" class="auth-button" value="Simpan Draf" style="background-color: #6c757d;">
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
