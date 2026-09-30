<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once "../db.php";

/* =========================================
   APPOINTMENT COUNTS
========================================= */

$pending_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Pending'
");

$pending = mysqli_fetch_assoc($pending_query)['total'];


$approved_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Approved'
");

$approved = mysqli_fetch_assoc($approved_query)['total'];


$completed_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Completed'
");

$completed = mysqli_fetch_assoc($completed_query)['total'];


$cancelled_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Cancelled'
");

$cancelled = mysqli_fetch_assoc($cancelled_query)['total'];


/* =========================================
   TODAY'S APPOINTMENTS
========================================= */

$today_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE appointment_date = CURDATE()
    AND status != 'Cancelled'
");

$today = mysqli_fetch_assoc($today_query)['total'];


/* =========================================
   APPOINTMENTS
========================================= */

$query = "
    SELECT
        a.appointment_id,
        c.full_name AS customer_name,
        s.service_name,
        a.appointment_date,
        a.appointment_time,
        a.status

    FROM appointments a

    INNER JOIN customers c
        ON a.customer_id = c.customer_id

    INNER JOIN services s
        ON a.service_id = s.service_id

    ORDER BY
        a.appointment_date ASC,
        a.appointment_time ASC
";

$result = mysqli_query($conn, $query);

include "includes/header.php";
include "includes/sidebar.php";
?>


<div class="main-content">

    <!-- =====================================
         PAGE HEADER
    ====================================== -->

    <div class="appointment-page-header">

        <div>

            <span class="page-label">
                MANAGEMENT
            </span>

            <h1>
                Appointment Management
            </h1>

            <p>
                Manage and monitor customer appointments.
            </p>

        </div>

    </div>


    <!-- =====================================
         SUMMARY CARDS
    ====================================== -->

    <div class="appointment-summary">

        <div class="appointment-card">

            <div class="appointment-card-icon today">
                ◷
            </div>

            <div>

                <span>
                    Today's Appointments
                </span>

                <strong>
                    <?php echo $today; ?>
                </strong>

            </div>

        </div>


        <div class="appointment-card">

            <div class="appointment-card-icon pending">
                !
            </div>

            <div>

                <span>
                    Pending
                </span>

                <strong>
                    <?php echo $pending; ?>
                </strong>

            </div>

        </div>


        <div class="appointment-card">

            <div class="appointment-card-icon approved">
                ✓
            </div>

            <div>

                <span>
                    Approved
                </span>

                <strong>
                    <?php echo $approved; ?>
                </strong>

            </div>

        </div>


        <div class="appointment-card">

            <div class="appointment-card-icon completed">
                ◆
            </div>

            <div>

                <span>
                    Completed
                </span>

                <strong>
                    <?php echo $completed; ?>
                </strong>

            </div>

        </div>


        <div class="appointment-card">

            <div class="appointment-card-icon cancelled">
                ×
            </div>

            <div>

                <span>
                    Cancelled
                </span>

                <strong>
                    <?php echo $cancelled; ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- =====================================
         APPOINTMENT TABLE
    ====================================== -->

    <div class="appointment-table-card">

        <div class="appointment-table-header">

            <div>

                <span class="page-label">
                    SCHEDULE
                </span>

                <h2>
                    All Appointments
                </h2>

            </div>

        </div>


        <div class="appointment-table-wrapper">

            <table class="appointment-table">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Customer</th>

                        <th>Service</th>

                        <th>Date</th>

                        <th>Time</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (mysqli_num_rows($result) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($result)): ?>

                        <tr>

                            <td class="appointment-id">
                                #<?php echo $row['appointment_id']; ?>
                            </td>


                            <td>

                                <div class="customer-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $row['customer_name']
                                    );
                                    ?>

                                </div>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $row['service_name']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    "M d, Y",
                                    strtotime($row['appointment_date'])
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime($row['appointment_time'])
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                $status_class = strtolower(
                                    $row['status']
                                );

                                ?>

                                <span class="
                                    appointment-status
                                    <?php echo $status_class; ?>
                                ">

                                    <?php
                                    echo htmlspecialchars(
                                        $row['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <div class="appointment-actions">

                                    <?php if ($row['status'] === 'Pending'): ?>

                                        <a
                                            href="approve.php?id=<?php echo $row['appointment_id']; ?>"
                                            class="action-approve"
                                        >
                                            Approve
                                        </a>

                                        <a
                                            href="cancel.php?id=<?php echo $row['appointment_id']; ?>"
                                            class="action-cancel"
                                        >
                                            Cancel
                                        </a>

                                    <?php elseif ($row['status'] === 'Approved'): ?>

                                        <a
                                            href="complete.php?id=<?php echo $row['appointment_id']; ?>"
                                            class="action-complete"
                                        >
                                            Complete
                                        </a>

                                        <a
                                            href="cancel.php?id=<?php echo $row['appointment_id']; ?>"
                                            class="action-cancel"
                                        >
                                            Cancel
                                        </a>

                                    <?php elseif ($row['status'] === 'Completed'): ?>

                                        <span class="action-disabled">
                                            Completed
                                        </span>

                                    <?php elseif ($row['status'] === 'Cancelled'): ?>

                                        <span class="action-disabled">
                                            Cancelled
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            class="empty-appointments"
                        >
                            No appointments found.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<?php
include "includes/footer.php";
?>