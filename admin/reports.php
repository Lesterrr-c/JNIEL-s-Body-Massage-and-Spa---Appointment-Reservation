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

include "includes/header.php";
include "includes/sidebar.php";

date_default_timezone_set("Asia/Manila");





/* =========================
   DATE FILTER
========================= */

$from_date = isset($_GET['from_date']) && !empty($_GET['from_date'])
    ? $_GET['from_date']
    : date("Y-m-01");

$to_date = isset($_GET['to_date']) && !empty($_GET['to_date'])
    ? $_GET['to_date']
    : date("Y-m-d");

/* =========================
   REPORT TYPE
========================= */

$report_type = isset($_GET['report_type'])
    ? $_GET['report_type']
    : 'reservations';


/* =====================================================
   RESERVATION REPORT
===================================================== */

$reservation_query = mysqli_query($conn, "
    SELECT
        appointments.appointment_id,
        appointments.appointment_date,
        appointments.appointment_time,
        appointments.status,
        customers.full_name AS customer_name,
        services.service_name,
        services.price,
        therapists.full_name AS therapist_name
    FROM appointments

    INNER JOIN customers
        ON appointments.customer_id = customers.customer_id

    INNER JOIN services
        ON appointments.service_id = services.service_id

    LEFT JOIN therapists
        ON appointments.therapist_id = therapists.therapist_id

    WHERE appointments.appointment_date
    BETWEEN '$from_date' AND '$to_date'

    ORDER BY appointments.appointment_date ASC,
             appointments.appointment_time ASC
");

if (!$reservation_query) {
    die("RESERVATION REPORT ERROR: " . mysqli_error($conn));
}


/* =====================================================
   RESERVATION SUMMARY
===================================================== */

$total_reservations = 0;
$pending_reservations = 0;
$approved_reservations = 0;
$completed_reservations = 0;
$cancelled_reservations = 0;

$reservation_rows = [];

while ($row = mysqli_fetch_assoc($reservation_query)) {

    $reservation_rows[] = $row;

    $total_reservations++;

    switch ($row['status']) {

        case 'Pending':
            $pending_reservations++;
            break;

        case 'Approved':
            $approved_reservations++;
            break;

        case 'Completed':
            $completed_reservations++;
            break;

        case 'Cancelled':
            $cancelled_reservations++;
            break;
    }
}


/* =====================================================
   CUSTOMER REPORT
===================================================== */

$customer_query = mysqli_query($conn, "
    SELECT
        customers.customer_id,
        customers.full_name,
        customers.email,
        customers.phone_number,

        COUNT(appointments.appointment_id) AS reservation_count,

        SUM(
            CASE
                WHEN appointments.status = 'Completed'
                THEN 1
                ELSE 0
            END
        ) AS completed_count

    FROM customers

    LEFT JOIN appointments
        ON customers.customer_id = appointments.customer_id
        AND appointments.appointment_date
        BETWEEN '$from_date' AND '$to_date'

    GROUP BY
        customers.customer_id,
        customers.full_name,
        customers.email,
        customers.phone_number

    ORDER BY reservation_count DESC,
             customers.full_name ASC
");

if (!$customer_query) {
    die("CUSTOMER REPORT ERROR: " . mysqli_error($conn));
}

$customer_rows = [];

while ($row = mysqli_fetch_assoc($customer_query)) {
    $customer_rows[] = $row;
}


/* =====================================================
   CUSTOMER SUMMARY
===================================================== */

$total_customers = 0;
$active_customers = 0;

foreach ($customer_rows as $customer) {

    $total_customers++;

    if ((int)$customer['reservation_count'] > 0) {
        $active_customers++;
    }
}


/* =====================================================
   SALES REPORT
===================================================== */

$sales_query = mysqli_query($conn, "
    SELECT
        appointments.appointment_id,
        appointments.appointment_date,
        customers.full_name AS customer_name,
        services.service_name,
        services.price

    FROM appointments

    INNER JOIN customers
        ON appointments.customer_id = customers.customer_id

    INNER JOIN services
        ON appointments.service_id = services.service_id

    WHERE appointments.status = 'Completed'

    AND appointments.appointment_date
        BETWEEN '$from_date' AND '$to_date'

    ORDER BY appointments.appointment_date ASC
");

if (!$sales_query) {
    die("SALES REPORT ERROR: " . mysqli_error($conn));
}

$sales_rows = [];

$total_sales = 0;

while ($row = mysqli_fetch_assoc($sales_query)) {

    $sales_rows[] = $row;

    $total_sales += (float)$row['price'];
}


/* =====================================================
   REPORT TITLE
===================================================== */

if ($report_type === 'customers') {

    $report_title = "Customer Report";

} elseif ($report_type === 'sales') {

    $report_title = "Sales Report";

} else {

    $report_title = "Reservation Report";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    <?php echo $report_title; ?> | JNIEL'S Admin
</title>

<link rel="stylesheet" href="../css/admin.css">



</head>


<body>



<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main-content">

    <div class="admin-reports-page">


        <!-- HEADER -->

        <div class="report-header">

            <div>

                <h1>Reports</h1>

                <p>
                    Generate reservation, customer, and sales reports.
                </p>

            </div>

            <button
                type="button"
                class="report-print-btn"
                onclick="window.print()">

                Print Report

            </button>

        </div>


        <!-- FILTER -->

        <div class="report-filter">

            <form method="GET">

                <div class="report-field">

                    <label>
                        REPORT TYPE
                    </label>

                    <select name="report_type">

                        <option
                            value="reservations"
                            <?php echo $report_type === 'reservations' ? 'selected' : ''; ?>>

                            Reservations

                        </option>

                        <option
                            value="customers"
                            <?php echo $report_type === 'customers' ? 'selected' : ''; ?>>

                            Customers

                        </option>

                        <option
                            value="sales"
                            <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>

                            Sales

                        </option>

                    </select>

                </div>


                <div class="report-field">

                    <label>
                        FROM
                    </label>

                    <input
                        type="date"
                        name="from_date"
                        value="<?php echo htmlspecialchars($from_date); ?>">

                </div>


                <div class="report-field">

                    <label>
                        TO
                    </label>

                    <input
                        type="date"
                        name="to_date"
                        value="<?php echo htmlspecialchars($to_date); ?>">

                </div>


                <button
                    type="submit"
                    class="report-generate-btn">

                    Generate Report

                </button>

            </form>

        </div>


        <?php if ($report_type === 'reservations'): ?>


        <!-- =========================
             RESERVATION SUMMARY
        ========================= -->

        <div class="report-summary">

            <div class="report-summary-card">

                <span>
                    TOTAL RESERVATIONS
                </span>

                <strong>
                    <?php echo $total_reservations; ?>
                </strong>

            </div>


            <div class="report-summary-card">

                <span>
                    PENDING
                </span>

                <strong>
                    <?php echo $pending_reservations; ?>
                </strong>

            </div>


            <div class="report-summary-card">

                <span>
                    APPROVED
                </span>

                <strong>
                    <?php echo $approved_reservations; ?>
                </strong>

            </div>


            <div class="report-summary-card">

                <span>
                    COMPLETED
                </span>

                <strong>
                    <?php echo $completed_reservations; ?>
                </strong>

            </div>

        </div>


        <!-- RESERVATION TABLE -->

        <div class="report-table-container">

            <div class="report-table-header">

                <h2>
                    Reservation Report
                </h2>

            </div>


            <?php if (count($reservation_rows) === 0): ?>

                <div class="report-empty">

                    No reservations found for the selected date range.

                </div>

            <?php else: ?>

                <table class="report-table">

                    <thead>

                        <tr>

                            <th>
                                DATE
                            </th>

                            <th>
                                CUSTOMER
                            </th>

                            <th>
                                SERVICE
                            </th>

                            <th>
                                THERAPIST
                            </th>

                            <th>
                                TIME
                            </th>

                            <th>
                                STATUS
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($reservation_rows as $row): ?>

                        <tr>

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
                                echo htmlspecialchars(
                                    $row['customer_name']
                                );
                                ?>
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

                                echo !empty($row['therapist_name'])
                                    ? htmlspecialchars($row['therapist_name'])
                                    : "No Preference";

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

                                <span class="report-status">

                                    <?php
                                    echo htmlspecialchars(
                                        $row['status']
                                    );
                                    ?>

                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>


        <?php elseif ($report_type === 'customers'): ?>


        <!-- =========================
             CUSTOMER SUMMARY
        ========================= -->

        <div class="report-summary">

            <div class="report-summary-card">

                <span>
                    TOTAL CUSTOMERS
                </span>

                <strong>
                    <?php echo $total_customers; ?>
                </strong>

            </div>


            <div class="report-summary-card">

                <span>
                    ACTIVE CUSTOMERS
                </span>

                <strong>
                    <?php echo $active_customers; ?>
                </strong>

            </div>

        </div>


        <!-- CUSTOMER TABLE -->

        <div class="report-table-container">

            <div class="report-table-header">

                <h2>
                    Customer Report
                </h2>

            </div>


            <?php if (count($customer_rows) === 0): ?>

                <div class="report-empty">

                    No customers found.

                </div>

            <?php else: ?>

                <table class="report-table">

                    <thead>

                        <tr>

                            <th>
                                CUSTOMER
                            </th>

                            <th>
                                EMAIL
                            </th>

                            <th>
                                PHONE
                            </th>

                            <th>
                                RESERVATIONS
                            </th>

                            <th>
                                COMPLETED
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($customer_rows as $row): ?>

                        <tr>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['full_name']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['email']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['phone_number']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo (int)$row['reservation_count'];
                                ?>
                            </td>

                            <td>
                                <?php
                                echo (int)$row['completed_count'];
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>


        <?php else: ?>


        <!-- =========================
             SALES SUMMARY
        ========================= -->

        <div class="report-summary">

            <div class="report-summary-card">

                <span>
                    COMPLETED SALES
                </span>

                <strong>
                    <?php echo count($sales_rows); ?>
                </strong>

            </div>


            <div class="report-summary-card">

                <span>
                    TOTAL SALES
                </span>

                <strong>
                    ₱<?php echo number_format($total_sales, 2); ?>
                </strong>

            </div>

        </div>


        <!-- SALES TABLE -->

        <div class="report-table-container">

            <div class="report-table-header">

                <h2>
                    Sales Report
                </h2>

            </div>


            <?php if (count($sales_rows) === 0): ?>

                <div class="report-empty">

                    No completed sales found for the selected date range.

                </div>

            <?php else: ?>

                <table class="report-table">

                    <thead>

                        <tr>

                            <th>
                                DATE
                            </th>

                            <th>
                                CUSTOMER
                            </th>

                            <th>
                                SERVICE
                            </th>

                            <th>
                                PRICE
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($sales_rows as $row): ?>

                        <tr>

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
                                echo htmlspecialchars(
                                    $row['customer_name']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $row['service_name']
                                );
                                ?>
                            </td>

                            <td>
                                ₱<?php
                                echo number_format(
                                    $row['price'],
                                    2
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>


                <div class="sales-total">

                    <span>
                        TOTAL SALES:
                    </span>

                    <strong>
                        ₱<?php
                        echo number_format(
                            $total_sales,
                            2
                        );
                        ?>
                    </strong>

                </div>

            <?php endif; ?>

        </div>


        <?php endif; ?>


    </div>

</div>


</body>

</html>