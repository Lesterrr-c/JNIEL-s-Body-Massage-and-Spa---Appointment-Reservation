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

include "includes/header.php";
include "includes/sidebar.php";
include "../db.php";

$adminName = $_SESSION['admin_name'] ?? 'Admin';

/*
|--------------------------------------------------------------------------
| DASHBOARD STATISTICS
|--------------------------------------------------------------------------
*/

// Total Services
$totalServices = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM services")
)['total'];

// Total Customers
$totalCustomers = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM customers")
)['total'];

// Total Appointments
$totalAppointments = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments")
)['total'];

// Pending Appointments
$pendingAppointments = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM appointments
         WHERE status='Pending'"
    )
)['total'];

// Approved Appointments
$approvedAppointments = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM appointments
         WHERE status='Approved'"
    )
)['total'];

// Completed Appointments
$completedAppointments = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM appointments
         WHERE status='Completed'"
    )
)['total'];


/*
|--------------------------------------------------------------------------
| TODAY'S APPOINTMENTS
|--------------------------------------------------------------------------
*/

$today = date('Y-m-d');

$todayAppointments = mysqli_query(
    $conn,
    "SELECT 
        appointments.appointment_id,
        appointments.appointment_date,
        appointments.appointment_time,
        appointments.status,
        customers.full_name,
        services.service_name
     FROM appointments
     INNER JOIN customers
        ON appointments.customer_id = customers.customer_id
     INNER JOIN services
        ON appointments.service_id = services.service_id
     WHERE appointments.appointment_date = '$today'
     ORDER BY appointments.appointment_time ASC"
);


/*
|--------------------------------------------------------------------------
| UPCOMING APPOINTMENTS
|--------------------------------------------------------------------------
*/

$upcomingAppointments = mysqli_query(
    $conn,
    "SELECT 
        appointments.appointment_id,
        appointments.appointment_date,
        appointments.appointment_time,
        appointments.status,
        customers.full_name,
        services.service_name
     FROM appointments
     INNER JOIN customers
        ON appointments.customer_id = customers.customer_id
     INNER JOIN services
        ON appointments.service_id = services.service_id
     WHERE appointments.appointment_date > '$today'
     AND appointments.status != 'Cancelled'
     ORDER BY appointments.appointment_date ASC,
              appointments.appointment_time ASC
     LIMIT 5"
);

?>

<div class="main-content">

    <!-- =========================================================
         DASHBOARD HEADER
    ========================================================== -->

    <div class="dashboard-header">

        <div>
            <span class="dashboard-label">ADMIN DASHBOARD</span>

            <h1>
                Good day, <?php echo htmlspecialchars($adminName); ?> 👋
            </h1>

            <p>
                Here's what's happening with JNIEL'S Body Massage & Spa.
            </p>
        </div>

        

        <div class="dashboard-actions">

            <a href="appointments.php" class="dashboard-btn">
                View Appointments →
            </a>

        </div>

    </div>


    <!-- =========================================================
         STATISTICS
    ========================================================== -->

    <div class="dashboard-stats">

        <!-- Total Appointments -->
        <div class="stat-card">

            <div class="stat-icon">
                📅
            </div>

            <div class="stat-info">

                <p>Total Appointments</p>

                <h2>
                    <?php echo $totalAppointments; ?>
                </h2>

            </div>

        </div>


        <!-- Pending -->
        <div class="stat-card">

            <div class="stat-icon">
                🕐
            </div>

            <div class="stat-info">

                <p>Pending</p>

                <h2>
                    <?php echo $pendingAppointments; ?>
                </h2>

            </div>

        </div>


        <!-- Approved -->
        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div class="stat-info">

                <p>Approved</p>

                <h2>
                    <?php echo $approvedAppointments; ?>
                </h2>

            </div>

        </div>


        <!-- Completed -->
        <div class="stat-card">

            <div class="stat-icon">
                ★
            </div>

            <div class="stat-info">

                <p>Completed</p>

                <h2>
                    <?php echo $completedAppointments; ?>
                </h2>

            </div>

        </div>


        <!-- Customers -->
        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div class="stat-info">

                <p>Customers</p>

                <h2>
                    <?php echo $totalCustomers; ?>
                </h2>

            </div>

        </div>


        <!-- Services -->
        <div class="stat-card">

            <div class="stat-icon">
                ✦
            </div>

            <div class="stat-info">

                <p>Services</p>

                <h2>
                    <?php echo $totalServices; ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- =========================================================
         TODAY'S APPOINTMENTS
    ========================================================== -->

    <div class="dashboard-section">

        <div class="section-header">

            <div>

                <span class="section-label">
                    TODAY
                </span>

                <h2>
                    Today's Appointments
                </h2>

            </div>

            <a href="appointments.php">
                View All →
            </a>

        </div>


        <div class="appointment-table">

            <?php if (mysqli_num_rows($todayAppointments) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>Time</th>

                            <th>Customer</th>

                            <th>Service</th>

                            <th>Status</th>

                            <th></th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while ($appointment = mysqli_fetch_assoc($todayAppointments)): ?>

                        <tr>

                            <td>

                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime($appointment['appointment_time'])
                                );
                                ?>

                            </td>

                            <td>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['full_name']
                                    );
                                    ?>
                                </strong>

                            </td>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['service_name']
                                );
                                ?>

                            </td>

                            <td>

                                <span class="status status-<?php
                                    echo strtolower(
                                        $appointment['status']
                                    );
                                ?>">

                                    <?php
                                    echo htmlspecialchars(
                                        $appointment['status']
                                    );
                                    ?>

                                </span>

                            </td>

                            <td>

                                <a
                                    href="appointments.php"
                                    class="view-link"
                                >
                                    View →
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        📅
                    </div>

                    <h3>
                        No appointments today
                    </h3>

                    <p>
                        There are no scheduled appointments for today.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =========================================================
         UPCOMING APPOINTMENTS
    ========================================================== -->

    <div class="dashboard-section">

        <div class="section-header">

            <div>

                <span class="section-label">
                    UPCOMING
                </span>

                <h2>
                    Upcoming Appointments
                </h2>

            </div>

            <a href="appointments.php">
                View All →
            </a>

        </div>


        <div class="upcoming-list">

            <?php if (mysqli_num_rows($upcomingAppointments) > 0): ?>

                <?php while ($appointment = mysqli_fetch_assoc($upcomingAppointments)): ?>

                    <div class="upcoming-item">

                        <div class="appointment-date">

                            <span>
                                <?php
                                echo date(
                                    "M",
                                    strtotime(
                                        $appointment['appointment_date']
                                    )
                                );
                                ?>
                            </span>

                            <strong>
                                <?php
                                echo date(
                                    "d",
                                    strtotime(
                                        $appointment['appointment_date']
                                    )
                                );
                                ?>
                            </strong>

                        </div>


                        <div class="appointment-details">

                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['full_name']
                                );
                                ?>

                            </h3>

                            <p>

                                <?php
                                echo htmlspecialchars(
                                    $appointment['service_name']
                                );
                                ?>

                                •

                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $appointment['appointment_time']
                                    )
                                );
                                ?>

                            </p>

                        </div>


                        <div class="appointment-status">

                            <span class="status status-<?php
                                echo strtolower(
                                    $appointment['status']
                                );
                            ?>">

                                <?php
                                echo htmlspecialchars(
                                    $appointment['status']
                                );
                                ?>

                            </span>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="empty-state">

                    <div class="empty-icon">
                        🗓️
                    </div>

                    <h3>
                        No upcoming appointments
                    </h3>

                    <p>
                        Upcoming customer bookings will appear here.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =========================================================
         QUICK ACTIONS
    ========================================================== -->

    <div class="dashboard-section">

        <div class="section-header">

            <div>

                <span class="section-label">
                    QUICK ACCESS
                </span>

                <h2>
                    Quick Actions
                </h2>

            </div>

        </div>


        <div class="quick-actions">

            <a href="appointments.php" class="quick-action">

                <span class="quick-icon">
                    📅
                </span>

                <div>

                    <h3>
                        Manage Appointments
                    </h3>

                    <p>
                        Review and manage bookings.
                    </p>

                </div>

                <span class="quick-arrow">
                    →
                </span>

            </a>


            <a href="customers.php" class="quick-action">

                <span class="quick-icon">
                    👥
                </span>

                <div>

                    <h3>
                        Customers
                    </h3>

                    <p>
                        View registered customers.
                    </p>

                </div>

                <span class="quick-arrow">
                    →
                </span>

            </a>


            <a href="services.php" class="quick-action">

                <span class="quick-icon">
                    ✦
                </span>

                <div>

                    <h3>
                        Manage Services
                    </h3>

                    <p>
                        Add or update spa services.
                    </p>

                </div>

                <span class="quick-arrow">
                    →
                </span>

            </a>

        </div>

    </div>

</div>

<?php
include "includes/footer.php";
?>