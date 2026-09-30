<?php

session_start();

require_once "../db.php";

if(isset($_GET['id'])){

$id=$_GET['id'];

$stmt=mysqli_prepare($conn,
"UPDATE appointments
SET status='Cancelled'
WHERE appointment_id=?");

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

}

header("Location: appointments.php");
exit();