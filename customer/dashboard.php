<?php
session_start();
require_once "../db.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// Total Appointments
$total = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total
FROM appointments
WHERE customer_id='$customer_id'"))['total'];

// Pending
$pending = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total
FROM appointments
WHERE customer_id='$customer_id'
AND status='Pending'"))['total'];

// Approved
$approved = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total
FROM appointments
WHERE customer_id='$customer_id'
AND status='Approved'"))['total'];

// Completed
$completed = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total
FROM appointments
WHERE customer_id='$customer_id'
AND status='Completed'"))['total'];


// Get next upcoming appointment
$nextAppointment = mysqli_query($conn,
"SELECT *
FROM appointments
WHERE customer_id='$customer_id'
AND appointment_date >= CURDATE()
AND status IN ('Pending', 'Approved')
ORDER BY appointment_date ASC, appointment_time ASC
LIMIT 1");

$next = mysqli_fetch_assoc($nextAppointment);

?>



<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Dashboard | JNIEL'S Spa</title>

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


<!-- =========================
     MAIN CONTENT
========================= -->

<main class="dashboard-container">


    <!-- Welcome -->

    <section class="welcome-section">

        <div>

            <span class="welcome-label">
                CUSTOMER DASHBOARD
            </span>

            <h1>
                Welcome back,
                <?php echo htmlspecialchars($customer_name); ?>!
                <span class="wave">👋</span>
            </h1>

            <p>
                Ready for your next relaxing spa experience?
            </p>

        </div>


        <a href="book_appointment.php" class="primary-button">

            <span>＋</span>

            Book Appointment

        </a>

    </section>


    <!-- =========================
         STATISTICS
    ========================= -->

    <section class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon total-icon">
                ◉
            </div>

            <div class="stat-info">

                <span>Total Appointments</span>

                <strong>
                    <?php echo $total; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon pending-icon">
                ◷
            </div>

            <div class="stat-info">

                <span>Pending</span>

                <strong>
                    <?php echo $pending; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon approved-icon">
                ✓
            </div>

            <div class="stat-info">

                <span>Approved</span>

                <strong>
                    <?php echo $approved; ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon completed-icon">
                ★
            </div>

            <div class="stat-info">

                <span>Completed</span>

                <strong>
                    <?php echo $completed; ?>
                </strong>

            </div>

        </div>

    </section>


    <!-- =========================
         NEXT APPOINTMENT
    ========================= -->

    <section class="next-appointment">

        <div class="section-heading">

            <div>

                <span class="section-label">
                    YOUR SCHEDULE
                </span>

                <h2>Next Appointment</h2>

            </div>

            <a href="my_appointments.php">
                View All →
            </a>

        </div>


        <?php if ($next): ?>

            <div class="appointment-card">

                <div class="appointment-date">

                    <span class="month">
                        <?php
                        echo strtoupper(
                            date("M", strtotime($next['appointment_date']))
                        );
                        ?>
                    </span>

                    <strong>
                        <?php
                        echo date(
                            "d",
                            strtotime($next['appointment_date'])
                        );
                        ?>
                    </strong>

                </div>


                <div class="appointment-details">

                    <h3>
                        Spa Appointment
                    </h3>

                    <p>
                        <span>📅</span>

                        <?php
                        echo date(
                            "F d, Y",
                            strtotime($next['appointment_date'])
                        );
                        ?>
                    </p>

                    <p>
                        <span>🕐</span>

                        <?php
                        echo date(
                            "h:i A",
                            strtotime($next['appointment_time'])
                        );
                        ?>
                    </p>

                </div>


                <div class="appointment-status">

                    <span class="status <?php echo strtolower($next['status']); ?>">

                        <?php echo htmlspecialchars($next['status']); ?>

                    </span>

                    <a href="my_appointments.php">
                        View Details →
                    </a>

                </div>

            </div>

        <?php else: ?>

            <div class="empty-appointment">

                <div class="empty-icon">
                    ♨
                </div>

                <div>

                    <h3>
                        No upcoming appointments
                    </h3>

                    <p>
                        Take some time for yourself and book your next spa session.
                    </p>

                </div>

                <a href="book_appointment.php" class="secondary-button">
                    Book Now →
                </a>

            </div>

        <?php endif; ?>

    </section>


    <!-- =========================
         QUICK ACTIONS
    ========================= -->

    <section class="quick-actions">

        <div class="section-heading">

            <div>

                <span class="section-label">
                    QUICK ACCESS
                </span>

                <h2>What would you like to do?</h2>

            </div>

        </div>


        <div class="action-grid">


            <a href="book_appointment.php" class="action-card featured">

                <div class="action-icon">
                    ＋
                </div>

                <div>

                    <h3>
                        Book an Appointment
                    </h3>

                    <p>
                        Reserve your next relaxing spa session.
                    </p>

                </div>

                <span class="action-arrow">
                    →
                </span>

            </a>


            <a href="my_appointments.php" class="action-card">

                <div class="action-icon">
                    ▣
                </div>

                <div>

                    <h3>
                        My Appointments
                    </h3>

                    <p>
                        View and manage your bookings.
                    </p>

                </div>

                <span class="action-arrow">
                    →
                </span>

            </a>


            <a href="profile.php" class="action-card">

                <div class="action-icon">
                    ♙
                </div>

                <div>

                    <h3>
                        My Profile
                    </h3>

                    <p>
                        Manage your account information.
                    </p>

                </div>

                <span class="action-arrow">
                    →
                </span>

            </a>


        </div>

    </section>


</main>


<footer>

    <p>
        © <?php echo date("Y"); ?> JNIEL'S Body Massage & Spa.
        All rights reserved.
    </p>

</footer>




</body>



</html>
