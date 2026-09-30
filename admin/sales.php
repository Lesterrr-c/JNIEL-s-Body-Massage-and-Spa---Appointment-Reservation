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


// =========================
// SALES SUMMARY
// =========================

// Today's sales
$today_sales_query = mysqli_query($conn, "
    SELECT COALESCE(SUM(services.price), 0) AS total
    FROM appointments
    INNER JOIN services
        ON appointments.service_id = services.service_id
    WHERE appointments.status = 'Completed'
    AND appointments.appointment_date = CURDATE()
");

$today_sales = mysqli_fetch_assoc($today_sales_query)['total'];


// This month's sales
$month_sales_query = mysqli_query($conn, "
    SELECT COALESCE(SUM(services.price), 0) AS total
    FROM appointments
    INNER JOIN services
        ON appointments.service_id = services.service_id
    WHERE appointments.status = 'Completed'
    AND MONTH(appointments.appointment_date) = MONTH(CURDATE())
    AND YEAR(appointments.appointment_date) = YEAR(CURDATE())
");

$month_sales = mysqli_fetch_assoc($month_sales_query)['total'];


// Total sales
$total_sales_query = mysqli_query($conn, "
    SELECT COALESCE(SUM(services.price), 0) AS total
    FROM appointments
    INNER JOIN services
        ON appointments.service_id = services.service_id
    WHERE appointments.status = 'Completed'
");

$total_sales = mysqli_fetch_assoc($total_sales_query)['total'];


// Completed transactions
$completed_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE status = 'Completed'
");

$completed_transactions = mysqli_fetch_assoc($completed_query)['total'];


// =========================
// FILTER VALUES
// =========================

$search = trim($_GET['search'] ?? '');
$filter_date = $_GET['date'] ?? '';

$where = "WHERE appointments.status = 'Completed'";


// Search customer
if ($search !== '') {

    $search_safe = mysqli_real_escape_string($conn, $search);

    $where .= "
        AND customers.full_name LIKE '%$search_safe%'
    ";
}


// Filter date
if ($filter_date !== '') {

    $date_safe = mysqli_real_escape_string($conn, $filter_date);

    $where .= "
        AND appointments.appointment_date = '$date_safe'
    ";
}


// =========================
// SALES TRANSACTIONS
// =========================

$sales_query = mysqli_query($conn, "
    SELECT
        appointments.appointment_id,
        appointments.appointment_date,
        appointments.appointment_time,

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

    $where

    ORDER BY
        appointments.appointment_date DESC,
        appointments.appointment_time DESC
");

// =========================
// SALES CHART DATA
// =========================

// Monthly Sales
$monthly_sales_query = mysqli_query($conn, "
    SELECT
        MONTH(appointments.appointment_date) AS month_number,
        MONTHNAME(appointments.appointment_date) AS month_name,
        COALESCE(SUM(services.price), 0) AS total
    FROM appointments
    INNER JOIN services
        ON appointments.service_id = services.service_id
    WHERE appointments.status = 'Completed'
    GROUP BY
        MONTH(appointments.appointment_date),
        MONTHNAME(appointments.appointment_date)
    ORDER BY month_number
");

$monthly_labels = [];
$monthly_values = [];

while ($row = mysqli_fetch_assoc($monthly_sales_query)) {

    $monthly_labels[] = $row['month_name'];
    $monthly_values[] = (float)$row['total'];

}


// Sales by Service
$service_sales_query = mysqli_query($conn, "
    SELECT
        services.service_name,
        COALESCE(SUM(services.price), 0) AS total
    FROM appointments
    INNER JOIN services
        ON appointments.service_id = services.service_id
    WHERE appointments.status = 'Completed'
    GROUP BY services.service_id, services.service_name
    ORDER BY total DESC
");

$service_labels = [];
$service_values = [];

while ($row = mysqli_fetch_assoc($service_sales_query)) {

    $service_labels[] = $row['service_name'];
    $service_values[] = (float)$row['total'];

}


// =========================
// HEADER & SIDEBAR
// =========================

include "includes/header.php";
include "includes/sidebar.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="styleshhet" href="../css.admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>
    
<div class="main-content sales-page">

    <!-- =========================
         PAGE HEADER
    ========================= -->

    <div class="sales-page-header">

        <div>
            <h1>Sales</h1>

            <p>
                Monitor completed appointments and sales transactions.
            </p>
        </div>

    </div>


    <!-- =========================
         SALES SUMMARY
    ========================= -->

    <div class="sales-summary">

        <!-- TODAY -->
        <div class="sales-card">

            <div class="sales-card-icon">
                ₱
            </div>

            <div class="sales-card-content">

                <span>Today's Sales</span>

                <h2>
                    ₱<?php echo number_format($today_sales, 2); ?>
                </h2>

                <small>
                    Completed today
                </small>

            </div>

        </div>


        <!-- MONTH -->
        <div class="sales-card">

            <div class="sales-card-icon">
                ◷
            </div>

            <div class="sales-card-content">

                <span>This Month</span>

                <h2>
                    ₱<?php echo number_format($month_sales, 2); ?>
                </h2>

                <small>
                    Current month
                </small>

            </div>

        </div>


        <!-- TOTAL -->
        <div class="sales-card">

            <div class="sales-card-icon">
                ◆
            </div>

            <div class="sales-card-content">

                <span>Total Sales</span>

                <h2>
                    ₱<?php echo number_format($total_sales, 2); ?>
                </h2>

                <small>
                    All completed sales
                </small>

            </div>

        </div>


        <!-- COMPLETED -->
        <div class="sales-card">

            <div class="sales-card-icon">
                ✓
            </div>

            <div class="sales-card-content">

                <span>Completed</span>

                <h2>
                    <?php echo number_format($completed_transactions); ?>
                </h2>

                <small>
                    Completed appointments
                </small>

            </div>

        </div>

    </div>


    <div class="sales-charts">

    <!-- Monthly Sales -->
    <div class="chart-card">

        <div class="chart-header">
            <h2>Monthly Sales</h2>
            <p>Completed sales throughout the year.</p>
        </div>

        <div class="chart-container">
            <canvas id="monthlySalesChart"></canvas>
        </div>

    </div>


    <!-- Sales by Service -->
    <div class="chart-card">

        <div class="chart-header">
            <h2>Sales by Service</h2>
            <p>Total completed sales for each service.</p>
        </div>

        <div class="chart-container">
            <canvas id="serviceSalesChart"></canvas>
        </div>

    </div>

</div>


    <!-- =========================
         FILTERS
    ========================= -->

    <div class="sales-filter-card">

        <div class="sales-filter-header">

            <div>
                <h2>Transaction Filters</h2>

                <p>
                    Search and filter completed sales.
                </p>
            </div>

        </div>


        <form method="GET" class="sales-filter-form">

            <!-- CUSTOMER SEARCH -->

            <div class="sales-filter-group">

                <label for="search">
                    Customer
                </label>

                <div class="sales-input-wrapper">

                    <span class="sales-input-icon">
                        🔍
                    </span>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Search customer..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>

            </div>


            <!-- DATE -->

            <div class="sales-filter-group">

                <label for="date">
                    Date
                </label>

                <div class="sales-input-wrapper">

                    <span class="sales-input-icon">
                        📅
                    </span>

                    <input
                        type="date"
                        id="date"
                        name="date"
                        value="<?php echo htmlspecialchars($filter_date); ?>"
                    >

                </div>

            </div>


            <!-- BUTTONS -->

            <div class="sales-filter-actions">

                <button
                    type="submit"
                    class="btn-sales-filter"
                >
                    Filter
                </button>

                <a
                    href="sales.php"
                    class="btn-sales-reset"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    <!-- =========================
         SALES TABLE
    ========================= -->

    <div class="sales-table-card">

        <div class="sales-table-header">

            <div>

                <h2>
                    Sales Transactions
                </h2>

                <p>
                    Completed appointments and their corresponding sales.
                </p>

            </div>

            <div class="transaction-count">

                <?php echo mysqli_num_rows($sales_query); ?>

                transaction<?php echo mysqli_num_rows($sales_query) != 1 ? 's' : ''; ?>

            </div>

        </div>


        <div class="sales-table-container">

            <table class="sales-table">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Date & Time</th>

                        <th>Customer</th>

                        <th>Service</th>

                        <th>Therapist</th>

                        <th>Amount</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (mysqli_num_rows($sales_query) > 0): ?>

                    <?php while ($sale = mysqli_fetch_assoc($sales_query)): ?>

                        <tr>

                            <!-- ID -->

                            <td>

                                <span class="transaction-id">

                                    #<?php echo $sale['appointment_id']; ?>

                                </span>

                            </td>


                            <!-- DATE -->

                            <td>

                                <div class="sale-date">

                                    <strong>

                                        <?php
                                        echo date(
                                            "M d, Y",
                                            strtotime($sale['appointment_date'])
                                        );
                                        ?>

                                    </strong>

                                    <span>

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($sale['appointment_time'])
                                        );
                                        ?>

                                    </span>

                                </div>

                            </td>


                            <!-- CUSTOMER -->

                            <td>

                                <div class="customer-name">

                                    <span class="customer-avatar">

                                        <?php
                                        echo strtoupper(
                                            substr(
                                                $sale['customer_name'],
                                                0,
                                                1
                                            )
                                        );
                                        ?>

                                    </span>

                                    <span>

                                        <?php
                                        echo htmlspecialchars(
                                            $sale['customer_name']
                                        );
                                        ?>

                                    </span>

                                </div>

                            </td>


                            <!-- SERVICE -->

                            <td>

                                <span class="service-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $sale['service_name']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- THERAPIST -->

                            <td>

                                <?php

                                if (!empty($sale['therapist_name'])) {

                                    echo htmlspecialchars(
                                        $sale['therapist_name']
                                    );

                                } else {

                                    echo '
                                        <span class="no-therapist">
                                            No Preference
                                        </span>
                                    ';
                                }

                                ?>

                            </td>


                            <!-- AMOUNT -->

                            <td>

                                <span class="sale-amount">

                                    ₱<?php
                                    echo number_format(
                                        $sale['price'],
                                        2
                                    );
                                    ?>

                                </span>

                            </td>

                        </tr>

                    <?php endwhile; ?>


                <?php else: ?>

                    <tr>

                        <td
                            colspan="6"
                            class="empty-sales"
                        >

                            <div class="empty-sales-content">

                                <div class="empty-sales-icon">
                                    ₱
                                </div>

                                <h3>
                                    No transactions found
                                </h3>

                                <p>
                                    Try changing your search or date filter.
                                </p>

                                <a href="sales.php">
                                    Clear Filters
                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>


// =========================
// MONTHLY SALES CHART
// =========================

const monthlySalesCtx =
    document.getElementById('monthlySalesChart');

new Chart(monthlySalesCtx, {

    type: 'line',

    data: {

        labels: <?php echo json_encode($monthly_labels); ?>,

        datasets: [{

            label: 'Sales',

            data: <?php echo json_encode($monthly_values); ?>,

            tension: 0.4,

            fill: true,

            borderWidth: 2,

            pointRadius: 4

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            }

        },

        scales: {

            y: {

                beginAtZero: true,

                ticks: {

                    callback: function(value) {
                        return '₱' +
                            value.toLocaleString();
                    }

                }

            }

        }

    }

});


// =========================
// SALES BY SERVICE CHART
// =========================

const serviceSalesCtx =
    document.getElementById('serviceSalesChart');

new Chart(serviceSalesCtx, {

    type: 'bar',

    data: {

        labels: <?php echo json_encode($service_labels); ?>,

        datasets: [{

            label: 'Sales',

            data: <?php echo json_encode($service_values); ?>,

            borderWidth: 1

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                display: false
            }

        },

        scales: {

            y: {

                beginAtZero: true,

                ticks: {

                    callback: function(value) {
                        return '₱' +
                            value.toLocaleString();
                    }

                }

            }

        }

    }

});

</script>

</body>
</html>