<?php

/*
|--------------------------------------------------------------------------
| JNIEL'S BODY MASSAGE & SPA
| APPOINTMENT REMINDER CHECKER
|--------------------------------------------------------------------------
|
| This file is executed automatically by Windows Task Scheduler.
|
| Reminder rules:
|
| 24-HOUR REMINDER
| - Appointment is more than 1 hour away
| - Appointment is within 24 hours
| - reminder_24h_sent = 0
|
| 1-HOUR REMINDER
| - Appointment is within 1 hour
| - Appointment has not started yet
| - reminder_1h_sent = 0
|
| The database flags prevent duplicate emails.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| TIMEZONE
|--------------------------------------------------------------------------
*/

date_default_timezone_set("Asia/Manila");

$timezone = new DateTimeZone("Asia/Manila");

$now = new DateTime("now", $timezone);


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../db.php";


/*
|--------------------------------------------------------------------------
| EXISTING REMINDER MAILER
|--------------------------------------------------------------------------
|
| IMPORTANT:
| We are using your existing send_reminder.php.
| We are NOT creating another mailer.
|
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/send_reminder.php";


/*
|--------------------------------------------------------------------------
| LOG FILE
|--------------------------------------------------------------------------
|
| This helps us confirm that Windows Task Scheduler is actually
| executing this PHP file.
|
|--------------------------------------------------------------------------
*/

$logFile = __DIR__ . "/reminder_check.log";


function reminderLog($message)
{
    global $logFile;

    $timestamp = date("Y-m-d H:i:s");

    file_put_contents(
        $logFile,
        "[" . $timestamp . "] " . $message . PHP_EOL,
        FILE_APPEND
    );
}


/*
|--------------------------------------------------------------------------
| START LOG
|--------------------------------------------------------------------------
*/

reminderLog("========================================");
reminderLog("Reminder checker started.");
reminderLog(
    "Current time: " .
    $now->format("Y-m-d H:i:s")
);


/*
|--------------------------------------------------------------------------
| GET UPCOMING APPROVED APPOINTMENTS
|--------------------------------------------------------------------------
*/

$query = mysqli_prepare(
    $conn,

    "SELECT
        a.appointment_id,
        a.customer_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        a.reminder_24h_sent,
        a.reminder_1h_sent,

        c.full_name AS customer_name,
        c.email AS customer_email,

        s.service_name

    FROM appointments a

    INNER JOIN customers c
        ON a.customer_id = c.customer_id

    INNER JOIN services s
        ON a.service_id = s.service_id

    WHERE a.status = 'Approved'

    AND a.appointment_date >= CURDATE()

    ORDER BY
        a.appointment_date ASC,
        a.appointment_time ASC"
);


/*
|--------------------------------------------------------------------------
| CHECK QUERY
|--------------------------------------------------------------------------
*/

if (!$query) {

    reminderLog(
        "ERROR: Could not prepare appointment query. " .
        mysqli_error($conn)
    );

    echo "Reminder checker failed.";
    mysqli_close($conn);

    exit;
}


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

if (!mysqli_stmt_execute($query)) {

    reminderLog(
        "ERROR: Could not execute appointment query. " .
        mysqli_stmt_error($query)
    );

    mysqli_stmt_close($query);
    mysqli_close($conn);

    echo "Reminder checker failed.";

    exit;
}


$result = mysqli_stmt_get_result($query);


/*
|--------------------------------------------------------------------------
| CHECK RESULT
|--------------------------------------------------------------------------
*/

if (!$result) {

    reminderLog(
        "ERROR: Could not get appointment result. " .
        mysqli_error($conn)
    );

    mysqli_stmt_close($query);
    mysqli_close($conn);

    echo "Reminder checker failed.";

    exit;
}


/*
|--------------------------------------------------------------------------
| COUNT
|--------------------------------------------------------------------------
*/

$checkedAppointments = 0;
$sent24Hour = 0;
$sent1Hour = 0;


/*
|--------------------------------------------------------------------------
| PROCESS APPOINTMENTS
|--------------------------------------------------------------------------
*/

while ($appointment = mysqli_fetch_assoc($result)) {

    $checkedAppointments++;


    /*
    |--------------------------------------------------------------------------
    | BUILD APPOINTMENT DATE/TIME
    |--------------------------------------------------------------------------
    */

    $appointmentDateTime = new DateTime(
        $appointment['appointment_date'] .
        ' ' .
        $appointment['appointment_time'],
        $timezone
    );


    /*
    |--------------------------------------------------------------------------
    | CALCULATE TIME UNTIL APPOINTMENT
    |--------------------------------------------------------------------------
    */

    $differenceSeconds =
        $appointmentDateTime->getTimestamp()
        -
        $now->getTimestamp();


    /*
    |--------------------------------------------------------------------------
    | APPOINTMENT ALREADY STARTED
    |--------------------------------------------------------------------------
    */

    if ($differenceSeconds <= 0) {

        reminderLog(
            "SKIPPED Appointment ID " .
            $appointment['appointment_id'] .
            " - appointment has already started."
        );

        continue;
    }


    /*
    |--------------------------------------------------------------------------
    | CONVERT TO MINUTES
    |--------------------------------------------------------------------------
    */

    $minutesUntilAppointment =
        $differenceSeconds / 60;


    reminderLog(
        "Checking Appointment ID " .
        $appointment['appointment_id'] .
        " - " .
        round($minutesUntilAppointment, 2) .
        " minutes remaining."
    );


    /*
    |--------------------------------------------------------------------------
    | 24-HOUR REMINDER
    |--------------------------------------------------------------------------
    |
    | Send when:
    |
    | > 1 hour away
    | <= 24 hours away
    | reminder_24h_sent = 0
    |
    | This removes the old 1439-1441 minute restriction.
    |
    |--------------------------------------------------------------------------
    */

    if (
        (int)$appointment['reminder_24h_sent'] === 0 &&

        $minutesUntilAppointment <= 1440 &&

        $minutesUntilAppointment > 60
    ) {

        reminderLog(
            "24-HOUR REMINDER READY - Appointment ID " .
            $appointment['appointment_id']
        );


        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL
        |--------------------------------------------------------------------------
        */

        $sent = sendAppointmentReminder(
            $appointment['customer_email'],
            $appointment['customer_name'],
            $appointment['service_name'],
            $appointment['appointment_date'],
            $appointment['appointment_time'],
            "24h"
        );


        /*
        |--------------------------------------------------------------------------
        | EMAIL SUCCESS
        |--------------------------------------------------------------------------
        */

        if ($sent) {

            $update = mysqli_prepare(
                $conn,

                "UPDATE appointments
                 SET reminder_24h_sent = 1
                 WHERE appointment_id = ?"
            );


            if ($update) {

                mysqli_stmt_bind_param(
                    $update,
                    "i",
                    $appointment['appointment_id']
                );


                if (mysqli_stmt_execute($update)) {

                    $sent24Hour++;

                    reminderLog(
                        "SUCCESS: 24-HOUR REMINDER SENT - Appointment ID " .
                        $appointment['appointment_id']
                    );

                } else {

                    reminderLog(
                        "ERROR: Email sent but could not update " .
                        "reminder_24h_sent for Appointment ID " .
                        $appointment['appointment_id'] .
                        ". " .
                        mysqli_stmt_error($update)
                    );
                }


                mysqli_stmt_close($update);

            } else {

                reminderLog(
                    "ERROR: Could not prepare 24-hour update for " .
                    "Appointment ID " .
                    $appointment['appointment_id'] .
                    ". " .
                    mysqli_error($conn)
                );
            }

        } else {

            reminderLog(
                "FAILED: 24-HOUR REMINDER could not be sent - " .
                "Appointment ID " .
                $appointment['appointment_id']
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | 1-HOUR REMINDER
    |--------------------------------------------------------------------------
    |
    | Send when:
    |
    | <= 1 hour away
    | > 0 minutes away
    | reminder_1h_sent = 0
    |
    |--------------------------------------------------------------------------
    */

    if (
        (int)$appointment['reminder_1h_sent'] === 0 &&

        $minutesUntilAppointment <= 60 &&

        $minutesUntilAppointment > 0
    ) {

        reminderLog(
            "1-HOUR REMINDER READY - Appointment ID " .
            $appointment['appointment_id']
        );


        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL
        |--------------------------------------------------------------------------
        */

        $sent = sendAppointmentReminder(
            $appointment['customer_email'],
            $appointment['customer_name'],
            $appointment['service_name'],
            $appointment['appointment_date'],
            $appointment['appointment_time'],
            "1h"
        );


        /*
        |--------------------------------------------------------------------------
        | EMAIL SUCCESS
        |--------------------------------------------------------------------------
        */

        if ($sent) {

            $update = mysqli_prepare(
                $conn,

                "UPDATE appointments
                 SET reminder_1h_sent = 1
                 WHERE appointment_id = ?"
            );


            if ($update) {

                mysqli_stmt_bind_param(
                    $update,
                    "i",
                    $appointment['appointment_id']
                );


                if (mysqli_stmt_execute($update)) {

                    $sent1Hour++;

                    reminderLog(
                        "SUCCESS: 1-HOUR REMINDER SENT - Appointment ID " .
                        $appointment['appointment_id']
                    );

                } else {

                    reminderLog(
                        "ERROR: Email sent but could not update " .
                        "reminder_1h_sent for Appointment ID " .
                        $appointment['appointment_id'] .
                        ". " .
                        mysqli_stmt_error($update)
                    );
                }


                mysqli_stmt_close($update);

            } else {

                reminderLog(
                    "ERROR: Could not prepare 1-hour update for " .
                    "Appointment ID " .
                    $appointment['appointment_id'] .
                    ". " .
                    mysqli_error($conn)
                );
            }

        } else {

            reminderLog(
                "FAILED: 1-HOUR REMINDER could not be sent - " .
                "Appointment ID " .
                $appointment['appointment_id']
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| CLOSE DATABASE QUERY
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($query);


/*
|--------------------------------------------------------------------------
| FINAL LOG
|--------------------------------------------------------------------------
*/

reminderLog(
    "Reminder checker completed."
);

reminderLog(
    "Appointments checked: " .
    $checkedAppointments
);

reminderLog(
    "24-hour reminders sent: " .
    $sent24Hour
);

reminderLog(
    "1-hour reminders sent: " .
    $sent1Hour
);

reminderLog("========================================");


/*
|--------------------------------------------------------------------------
| CLOSE DATABASE
|--------------------------------------------------------------------------
*/

mysqli_close($conn);


/*
|--------------------------------------------------------------------------
| OUTPUT
|--------------------------------------------------------------------------
*/

echo "Reminder check completed." . PHP_EOL;

echo "Appointments checked: " .
     $checkedAppointments .
     PHP_EOL;

echo "24-hour reminders sent: " .
     $sent24Hour .
     PHP_EOL;

echo "1-hour reminders sent: " .
     $sent1Hour .
     PHP_EOL;

?>