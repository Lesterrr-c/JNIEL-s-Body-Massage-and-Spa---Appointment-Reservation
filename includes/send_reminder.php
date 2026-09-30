<?php

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../config/smtp.php";


function sendAppointmentReminder($email, $customerName, $serviceName, $appointmentDate, $appointmentTime, $reminderType)
{
    // Create mailer using your existing SMTP configuration
    $mail = getMailer();

    $mail->CharSet = "UTF-8";
    $mail->Encoding = "base64";

    $mail->addAddress($email);
    $mail->isHTML(true);

    $logoPath = __DIR__ . "/../images/logo/logo.png";

if (file_exists($logoPath)) {
    $mail->addEmbeddedImage(
        $logoPath,
        "jniels-logo",
        "logo.png"
    );
}

    $formattedDate = date("F j, Y", strtotime($appointmentDate));
    $formattedTime = date("g:i A", strtotime($appointmentTime));

    if ($reminderType === "24h") {

        $subject = "Appointment Reminder | JNIEL'S Spa";

        $title = "Appointment Reminder 🌿";

        $message = "
            <p>
                This is a friendly reminder that you have an appointment
                <strong>tomorrow</strong> at JNIEL'S Body Massage & Spa.
            </p>
        ";

    } else {

        $subject = "Appointment Reminder | JNIEL'S Spa";

        $title = "Appointment Reminder";

        $message = "
            <p>
                This is a friendly reminder about your upcoming appointment at JNIEL'S Body Massage & Spa.
            </p>
        ";
    }

    $mail->Subject = $subject;

    $mail->Body = "
    <div style='
        font-family: Arial, sans-serif;
        background:#f4f4f4;
        padding:30px;
    '>

        <div style='
            max-width:600px;
            margin:auto;
            background:#ffffff;
            border-radius:12px;
            padding:35px;
        '>

            <div style='
    text-align:center;
    margin-bottom:25px;
'>
    <img
        src='cid:jniels-logo'
        alt= 'JNIELS Body Massage and Spa'

        style='
            width:160px;
            max-width:100%;
            height:auto;
            display:block;
            margin:0 auto;
        '
    >
</div>

            <h3 style='
                text-align:center;
                color:#333;
            '>
                $title
            </h3>

            <p>
                Hello <strong>" . htmlspecialchars($customerName) . "</strong>,
            </p>

            $message

            <div style='
                background:#f8f8f8;
                border-radius:10px;
                padding:20px;
                margin:25px 0;
            '>

                <p style='margin:8px 0;'>
                    <strong>Service:</strong> "
                    . htmlspecialchars($serviceName) .
                "
                </p>

                <p style='margin:8px 0;'>
                    <strong>Date:</strong> $formattedDate
                </p>

                <p style='margin:8px 0;'>
                    <strong>Time:</strong> $formattedTime
                </p>

            </div>

            <p>
                Please arrive a few minutes before your scheduled appointment.
            </p>

            <p>
                We look forward to welcoming you. Please arrive a few minutes early and get ready to 
                <strong>Relax, Refresh, and Rejuvenate.</strong>
            </p>

            <hr style='margin:30px 0;'>

            <p style='
                font-size:13px;
                color:#777;
                text-align:center;
            '>
                This is an automated appointment reminder from
                JNIEL'S Body Massage & Spa.
            </p>

        </div>

    </div>";

    try {

        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            "APPOINTMENT REMINDER EMAIL ERROR: " .
            $mail->ErrorInfo
        );

        return false;
    }
}