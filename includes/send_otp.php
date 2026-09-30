<?php

require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../config/smtp.php";

function sendOTP($email)
{
    global $conn;

    // Generate a random 6-digit OTP
    $otp = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);

    // OTP expires in 5 minutes
    $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    // Save OTP to customer record
    mysqli_query($conn,
        "UPDATE customers
         SET otp_code='$otp',
             otp_expiry='$expiry'
         WHERE email='$email'");

    // Create the mailer
    $mail = getMailer();

    $mail->addAddress($email);

    $mail->isHTML(true);

    $mail->Subject = "Verify Your Email - JNIEL'S Body Massage & Spa";

    $mail->Body = "
    <div style='font-family:Arial,sans-serif;padding:30px;background:#f4f4f4;'>

        <div style='max-width:600px;background:#fff;margin:auto;border-radius:10px;padding:30px;'>

            <h2 style='color:#2E8B57;text-align:center;'>
                 JNIEL'S Body Massage & Spa
            </h2>

            <p>Hello,</p>

            <p>
                Thank you for registering.
                Please use the verification code below:
            </p>

            <div style='
                text-align:center;
                font-size:38px;
                font-weight:bold;
                letter-spacing:10px;
                color:#2E8B57;
                margin:30px 0;
            '>
                $otp
            </div>

            <p>
                This code will expire in
                <strong>5 minutes</strong>.
            </p>

            <hr>

            <p style='font-size:13px;color:#777;'>
                If you did not register,
                you can safely ignore this email.
            </p>

        </div>

    </div>";

    try{
        $mail->send();
        return true;

    }catch(Exception $e){
        die("OTP EMAIL ERROR: " .
        $mail->ErrorInfo);
    }
    
    
}