<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once "../db.php";


// Check ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: therapists.php");
    exit();
}

$therapist_id = intval($_GET['id']);


// Delete therapist
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM therapists
     WHERE therapist_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $therapist_id
);

mysqli_stmt_execute($stmt);


// Return to therapist management
header("Location: therapists.php");
exit();
?>