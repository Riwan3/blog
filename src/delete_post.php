<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_GET['id'])) {
    header('Location: /login.php');
    exit;
}

$post_id = $_GET['id'];
$author_id = $_SESSION['id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Verifikasi kepemilikan post
$sql_verify = "SELECT author_id FROM posts WHERE id = ?";
if ($stmt_verify = mysqli_prepare($link, $sql_verify)) {
    mysqli_stmt_bind_param($stmt_verify, "i", $post_id);
    if(mysqli_stmt_execute($stmt_verify)){
        mysqli_stmt_store_result($stmt_verify);
        if(mysqli_stmt_num_rows($stmt_verify) == 1){
            mysqli_stmt_bind_result($stmt_verify, $db_author_id);
            mysqli_stmt_fetch($stmt_verify);
            if($author_id !== $db_author_id) {
                header("Location: /dashboard.php?page=$page&error=unauthorized_delete");
                exit;
            }
        } else {
             header("Location: /dashboard.php?page=$page&error=notfound");
             exit;
        }
    }
    mysqli_stmt_close($stmt_verify);
}

// Logika untuk menentukan halaman redirect
// Hitung jumlah post oleh user SEBELUM menghapus
$sql_count = "SELECT COUNT(*) FROM posts WHERE author_id = ?";
$stmt_count = mysqli_prepare($link, $sql_count);
mysqli_stmt_bind_param($stmt_count, "i", $author_id);
mysqli_stmt_execute($stmt_count);
$total_posts_before_delete = mysqli_fetch_array(mysqli_stmt_get_result($stmt_count))[0];
$results_per_page = 10; // Harus sama dengan yang di dashboard.php
$total_pages_before_delete = ceil($total_posts_before_delete / $results_per_page);

// Hapus post
$sql_delete = "DELETE FROM posts WHERE id = ? AND author_id = ?";
if ($stmt_delete = mysqli_prepare($link, $sql_delete)) {
    mysqli_stmt_bind_param($stmt_delete, "ii", $post_id, $author_id);
    if (mysqli_stmt_execute($stmt_delete)) {
        // Jika halaman saat ini lebih besar dari total halaman setelah hapus,
        // redirect ke halaman terakhir yang valid.
        $total_posts_after_delete = $total_posts_before_delete - 1;
        $total_pages_after_delete = ceil($total_posts_after_delete / $results_per_page);
        if ($page > $total_pages_after_delete && $total_pages_after_delete > 0) {
            $page = $total_pages_after_delete;
        }
        header("Location: /dashboard.php?page=$page&deleted=true");
        exit();
    } else {
        header("Location: /dashboard.php?page=$page&error=deletefailed");
        exit();
    }
    mysqli_stmt_close($stmt_delete);
}
mysqli_close($link);
?>
