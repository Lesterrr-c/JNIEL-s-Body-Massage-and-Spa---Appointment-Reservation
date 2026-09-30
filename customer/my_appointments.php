<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once "../db.php";





if (!isset($_SESSION['customer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

$query = mysqli_query($conn, "
SELECT
    appointments.*,
    services.service_name,
    services.price,
    therapists.full_name AS therapist_name
FROM appointments
INNER JOIN services
ON appointments.service_id = services.service_id

LEFT JOIN therapists 
ON appointments.therapist_id = therapists.therapist_id

WHERE appointments.customer_id = '$customer_id'
ORDER BY appointments.appointment_date DESC,
         appointments.appointment_time DESC
");

if (!$query ){
    die("APPOINTMENT QUERY ERROR:". 
    mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>My Appointments</title>

<link rel="stylesheet" href="../css/customer.css">

</head>

<body>
    
    <!-- =========================
     NAVBAR
========================= -->

<nav class="navbar">

    <div class="brand">

        <div class="brand-logo">
            <img src="../images/logo/logo.png" alt="JNIEL'S Logo">
            
        </div>

        <div class="brand-text">
            <h2>JNIEL'S</h2>
            <span>Body Massage & Spa</span>
        </div>

    </div>


    <ul class="nav-links">

        <li>
            <a href="dashboard.php" class="active">
                <span>⌂</span>
                Dashboard
            </a>
        </li>

        <li>
            <a href="book_appointment.php" class="active">
                <span>＋</span>
                Book Appointment
            </a>
        </li>

        <li>
            <a href="my_appointments.php" class="active">
                <span>▣</span>
                My Appointments
            </a>
        </li>

        <li>
            <a href="profile.php" class="active">
                <span>♙</span>
                Profile
            </a>
        </li>

        <li>
            <a href="../auth/logout.php" class="logout">
                Logout
            </a>
        </li>

    </ul>

</nav>


<div class="appointments-page">

    <div class="appointments-header">

        <div>
            <span class="appointments-eyebrow">
                YOUR WELLNESS JOURNEY
            </span>

            <h1>My Appointments</h1>

            <p>
                View and keep track of your spa appointments.
            </p>
        </div>

        <a href="book_appointment.php" class="new-appointment-btn">
            ＋ Book Appointment
        </a>

    </div>

    <?php if
    (isset($_SESSION['appointment_message'])): ?>

       <div class="appointment_message">

          <?php
        echo htmlspecialchars(
            $_SESSION['appointment_message']
        );
        unset($_SESSION['appointment_message']);
        ?>

       </div>

    <?php endif; ?>


    <div class="appointments-list">

        <?php if(mysqli_num_rows($query) == 0): ?>

            <div class="appointments-empty">

                <div class="empty-appointment-icon">
                    ▣
                </div>

                <h2>No appointments yet</h2>

                <p>
                    You haven't made any spa appointments.
                    Start your wellness journey today.
                </p>

                <a href="book_appointment.php">
                    Book Your First Appointment →
                </a>

            </div>

        <?php endif; ?>


        <?php while($row=mysqli_fetch_assoc($query)){

            $statusClass = strtolower($row['status']);

        ?>

        <div class="appointment-card">


            <!-- CARD HEADER -->

            <div class="appointment-card-header">

                <div>

                    <span class="appointment-label">
                        SPA SERVICE
                    </span>

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $row['service_name']
                        );
                        ?>
                    </h2>

                </div>


                <span class="status <?php echo $statusClass; ?>">

                    <?php
                    echo htmlspecialchars(
                        $row['status']
                    );
                    ?>

                </span>

            </div>


            <!-- APPOINTMENT DETAILS -->

            <div class="appointment-details">


                <div class="appointment-detail">

                    <div class="detail-icon">
                        ₱
                    </div>

                    <div>

                        <span>
                            PRICE
                        </span>

                        <strong>
                            ₱<?php
                            echo number_format(
                                $row['price'],
                                2
                            );
                            ?>
                        </strong>

                    </div>

                </div>



                <div class="appointment-detail">

                    <div class="detail-icon">
                        ✦
                    </div>

                    <div>

                        <span>
                            THERAPIST
                        </span>

                        <strong>
                            <?php
                            if (!empty($row['therapist_name'])){
                                echo htmlspecialchars(
                                    $row['therapist_name']
                                );
                            }else{
                                echo "No Preference";
                            }
                             
                            ?>
                        </strong>

                    </div>

                </div>



                <div class="appointment-detail">

                    <div class="detail-icon">
                        ▣
                    </div>

                    <div>

                        <span>
                            DATE
                        </span>

                        <strong>
                            <?php
                            echo date(
                                "F d, Y",
                                strtotime(
                                    $row['appointment_date']
                                )
                            );
                            ?>
                        </strong>

                    </div>

                </div>


                <div class="appointment-detail">

                    <div class="detail-icon">
                        ◷
                    </div>

                    <div>

                        <span>
                            TIME
                        </span>

                        <strong>
                            <?php
                            echo date(
                                "h:i A",
                                strtotime(
                                    $row['appointment_time']
                                )
                            );
                            ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- NOTES -->

            <?php if(!empty($row['notes'])): ?>

                <div class="appointment-notes">

                    <span class="notes-icon">
                        ✦
                    </span>

                    <div>

                        <span>
                            SPA NOTE
                        </span>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $row['notes']
                            );
                            ?>
                        </p>

                    </div>

                </div>

            <?php endif; ?>


<!-- =========================
     APPOINTMENT ACTIONS
========================= -->

<?php if (
    $row['status'] === 'Pending' ||
    $row['status'] === 'Approved'
): ?>

    <?php

    date_default_timezone_set("Asia/Manila");

    $appointmentDateTime = new DateTime(
        $row['appointment_date'] . " " .
        $row['appointment_time'],
        new DateTimeZone("Asia/Manila")
    );

    $now = new DateTime(
        "now",
        new DateTimeZone("Asia/Manila")
    );

    $minimumCancellationTime = clone $now;
    $minimumCancellationTime->modify("+1 hour");

    $canCancel =
        $appointmentDateTime > $minimumCancellationTime;

    ?>

    <div class="appointment-actions">

        <?php if ($canCancel): ?>

            <a
                href="cancel_appointment.php?appointment_id=<?php echo (int)$row['appointment_id']; ?>"
                class="cancel-appointment-btn"
                onclick="return confirm(
                    'Are you sure you want to cancel this appointment?'
                );"
            >
                Cancel Appointment
            </a>

        <?php else: ?>

            <span class="cancel-disabled">
                Cancellation unavailable
            </span>

        <?php endif; ?>

    </div>

<?php endif; ?>


</div>

<?php } ?>

        

        

    </div>

</div>

</body>

</html>