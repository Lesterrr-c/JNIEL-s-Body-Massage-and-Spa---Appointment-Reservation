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

include "../db.php";
include "includes/header.php";
include "includes/sidebar.php";


// =========================================================
// SEARCH & FILTER
// =========================================================

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';


// =========================================================
// BUILD CUSTOMER QUERY
// =========================================================

$sql = "SELECT 
            customer_id,
            full_name,
            email,
            phone_number,
            created_at,
            is_verified
        FROM customers
        WHERE 1=1";


// Search
if ($search !== '') {

    $searchSafe = mysqli_real_escape_string($conn, $search);

    $sql .= " AND (
                full_name LIKE '%$searchSafe%'
                OR email LIKE '%$searchSafe%'
                OR phone_number LIKE '%$searchSafe%'
            )";
}


// Verification filter
if ($statusFilter === 'verified') {

    $sql .= " AND is_verified = 1";

} elseif ($statusFilter === 'unverified') {

    $sql .= " AND is_verified = 0";
}


$sql .= " ORDER BY created_at DESC";


$customers = mysqli_query($conn, $sql);


// =========================================================
// CUSTOMER COUNTS
// =========================================================

$totalCustomers = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total FROM customers"
    )
)['total'];


$verifiedCustomers = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM customers
         WHERE is_verified = 1"
    )
)['total'];


$unverifiedCustomers = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM customers
         WHERE is_verified = 0"
    )
)['total'];

?>

<div class="main-content">

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="dashboard-header">

        <div>

            <span class="dashboard-label">
                CUSTOMER MANAGEMENT
            </span>

            <h1>
                Customers
            </h1>

            <p>
                View and manage registered customers.
            </p>

        </div>

    </div>


    <!-- =====================================================
         CUSTOMER STATISTICS
    ====================================================== -->

    <div class="dashboard-stats">

        <!-- TOTAL -->

        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div class="stat-info">

                <p>Total Customers</p>

                <h2>
                    <?php echo $totalCustomers; ?>
                </h2>

            </div>

        </div>


        <!-- VERIFIED -->

        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div class="stat-info">

                <p>Verified</p>

                <h2>
                    <?php echo $verifiedCustomers; ?>
                </h2>

            </div>

        </div>


        <!-- UNVERIFIED -->

        <div class="stat-card">

            <div class="stat-icon">
                !
            </div>

            <div class="stat-info">

                <p>Unverified</p>

                <h2>
                    <?php echo $unverifiedCustomers; ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- =====================================================
         CUSTOMER LIST
    ====================================================== -->

    <div class="dashboard-section">

        <div class="section-header">

            <div>

                <span class="section-label">
                    REGISTERED CUSTOMERS
                </span>

                <h2>
                    Customer List
                </h2>

            </div>

        </div>


        <!-- =================================================
             SEARCH & FILTER
        ================================================== -->

        <form
            method="GET"
            class="customer-filters"
        >

            <div class="customer-search">

                <span>
                    🔍
                </span>

                <input
                    type="text"
                    name="search"
                    placeholder="Search by name, email or phone..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >

            </div>


            <select
                name="status"
                onchange="this.form.submit()"
            >

                <option
                    value="all"
                    <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>
                >
                    All Customers
                </option>

                <option
                    value="verified"
                    <?php echo $statusFilter === 'verified' ? 'selected' : ''; ?>
                >
                    Verified
                </option>

                <option
                    value="unverified"
                    <?php echo $statusFilter === 'unverified' ? 'selected' : ''; ?>
                >
                    Unverified
                </option>

            </select>


            <button
                type="submit"
                class="customer-search-btn"
            >
                Search
            </button>


            <?php if ($search !== '' || $statusFilter !== 'all'): ?>

                <a
                    href="customers.php"
                    class="customer-clear-btn"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>


        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="appointment-table">

            <?php if ($customers && mysqli_num_rows($customers) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th>Customer</th>

                            <th>Email</th>

                            <th>Phone</th>

                            <th>Registered</th>

                            <th>Status</th>

                            <th></th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php while ($customer = mysqli_fetch_assoc($customers)): ?>

                        <tr>

                            <!-- CUSTOMER -->

                            <td>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $customer['full_name']
                                    );
                                    ?>
                                </strong>

                            </td>


                            <!-- EMAIL -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $customer['email']
                                );
                                ?>

                            </td>


                            <!-- PHONE -->

                            <td>

                                <?php

                                echo !empty($customer['phone_number'])
                                    ? htmlspecialchars($customer['phone_number'])
                                    : '—';

                                ?>

                            </td>


                            <!-- REGISTERED -->

                            <td>

                                <?php

                                echo date(
                                    "M d, Y",
                                    strtotime($customer['created_at'])
                                );

                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php if ($customer['is_verified'] == 1): ?>

                                    <span class="status status-completed">
                                        Verified
                                    </span>

                                <?php else: ?>

                                    <span class="status status-pending">
                                        Unverified
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <a
                                    href="customer_view.php?id=<?php echo $customer['customer_id']; ?>"
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
                        👥
                    </div>

                    <h3>
                        No customers found
                    </h3>

                    <p>
                        No registered customers match your search.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<?php

include "includes/footer.php";

?>