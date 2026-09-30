<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once "../db.php";


// Make sure customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}


$customer_id = (int)$_SESSION['customer_id'];


// Get appointment ID
if (!isset($_GET['appointment_id'])) {
    header("Location: my_appointments.php");
    exit();
}

$appointment_id = (int)$_GET['appointment_id'];


// =====================================================
// GET APPOINTMENT
// =====================================================

$stmt = mysqli_prepare($conn, "

    SELECT
        appointment_id,
        appointment_date,
        appointment_time,
        status

    FROM appointments

    WHERE appointment_id = ?
    AND customer_id = ?

    LIMIT 1

");


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $appointment_id,
    $customer_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$appointment = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// Appointment doesn't exist
if (!$appointment) {

    $_SESSION['appointment_message'] =
        "Appointment not found.";

    header("Location: my_appointments.php");
    exit();
}


// =====================================================
// CHECK STATUS
// =====================================================

if (
    $appointment['status'] !== 'Pending' &&
    $appointment['status'] !== 'Approved'
) {

    $_SESSION['appointment_message'] =
        "This appointment cannot be cancelled.";

    header("Location: my_appointments.php");
    exit();
}


// =====================================================
// CHECK TIME
// =====================================================

date_default_timezone_set("Asia/Manila");

$appointmentDateTime = new DateTime(
    $appointment['appointment_date'] . " " .
    $appointment['appointment_time'],
    new DateTimeZone("Asia/Manila")
);

$now = new DateTime(
    "now",
    new DateTimeZone("Asia/Manila")
);


// Do not allow cancellation within 1 hour
$minimumCancellationTime = clone $now;
$minimumCancellationTime->modify("+1 hour");


if ($appointmentDateTime <= $minimumCancellationTime) {

    $_SESSION['appointment_message'] =
        "Appointments can only be cancelled at least 1 hour before the scheduled time.";

    header("Location: my_appointments.php");
    exit();
}


// =====================================================
// CANCEL APPOINTMENT
// =====================================================

$stmt = mysqli_prepare($conn, "

    UPDATE appointments

    SET status = 'Cancelled'

    WHERE appointment_id = ?
    AND customer_id = ?

    AND (
        status = 'Pending'
        OR status = 'Approved'
    )

");


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $appointment_id,
    $customer_id
);


if (mysqli_stmt_execute($stmt)) {

    $_SESSION['appointment_message'] =
        "Your appointment has been cancelled successfully.";

} else {

    $_SESSION['appointment_message'] =
        "Unable to cancel your appointment. Please try again.";
}


mysqli_stmt_close($stmt);


// Return to appointments page
header("Location: my_appointments.php");
exit();

?>