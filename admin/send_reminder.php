<?php

date_default_timezone_set("Asia/Manila");

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../includes/send_reminder.php";


// Current Philippine time
$now = new DateTime("now", new DateTimeZone("Asia/Manila"));


// =====================================================
// 24-HOUR REMINDERS
// =====================================================

// Find appointments approximately 24 hours away.
// 23h 55m to 24h 05m gives a 10-minute window.
$query24 = "
    SELECT
        a.appointment_id,
        a.customer_id,
        a.appointment_date,
        a.appointment_time,
        c.full_name,
        c.email,
        s.service_name
    FROM appointments a

    INNER JOIN customers c
        ON a.customer_id = c.customer_id

    INNER JOIN services s
        ON a.service_id = s.service_id

    WHERE a.status = 'Approved'
    AND a.reminder_24h_sent = 0

    AND TIMESTAMP(a.appointment_date, a.appointment_time)
        BETWEEN
            DATE_ADD(NOW(), INTERVAL 23 HOUR 55 MINUTE)
            AND
            DATE_ADD(NOW(), INTERVAL 24 HOUR 5 MINUTE)
";

$result24 = mysqli_query($conn, $query24);

if ($result24) {

    while ($appointment = mysqli_fetch_assoc($result24)) {

        $sent = sendAppointmentReminder(
            $appointment['email'],
            $appointment['full_name'],
            $appointment['service_name'],
            $appointment['appointment_date'],
            $appointment['appointment_time'],
            "24h"
        );

        if ($sent) {

            $appointmentId = (int)$appointment['appointment_id'];

            mysqli_query(
                $conn,
                "UPDATE appointments
                 SET reminder_24h_sent = 1
                 WHERE appointment_id = $appointmentId"
            );
        }
    }
}


// =====================================================
// 1-HOUR REMINDERS
// =====================================================

// Find appointments approximately 1 hour away.
// 55 to 65 minutes gives a 10-minute window.
$query1 = "
    SELECT
        a.appointment_id,
        a.customer_id,
        a.appointment_date,
        a.appointment_time,
        c.full_name,
        c.email,
        s.service_name
    FROM appointments a

    INNER JOIN customers c
        ON a.customer_id = c.customer_id

    INNER JOIN services s
        ON a.service_id = s.service_id

    WHERE a.status = 'Approved'
    AND a.reminder_1h_sent = 0

    AND TIMESTAMP(a.appointment_date, a.appointment_time)
        BETWEEN
            DATE_ADD(NOW(), INTERVAL 55 MINUTE)
            AND
            DATE_ADD(NOW(), INTERVAL 65 MINUTE)
";

$result1 = mysqli_query($conn, $query1);

if ($result1) {

    while ($appointment = mysqli_fetch_assoc($result1)) {

        $sent = sendAppointmentReminder(
            $appointment['email'],
            $appointment['full_name'],
            $appointment['service_name'],
            $appointment['appointment_date'],
            $appointment['appointment_time'],
            "1h"
        );

        if ($sent) {

            $appointmentId = (int)$appointment['appointment_id'];

            mysqli_query(
                $conn,
                "UPDATE appointments
                 SET reminder_1h_sent = 1
                 WHERE appointment_id = $appointmentId"
            );
        }
    }
}


echo "Reminder check completed.";