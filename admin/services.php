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

include "includes/sidebar.php";
require_once "../db.php";

$message = "";

// ADD SERVICE
if (isset($_POST['add_service'])) {

    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $duration = $_POST['duration'];
    $status = $_POST['status'];

    $stmt = mysqli_prepare($conn,
        "INSERT INTO services(service_name, description, price, duration, status)
         VALUES (?, ?, ?, ?, ?)");

    mysqli_stmt_bind_param(
        $stmt,
        "ssdss",
        $service_name,
        $description,
        $price,
        $duration,
        $status
    );

    if (mysqli_stmt_execute($stmt)) {
        $message = "Service added successfully!";
    } else {
        $message = "Failed to add service.";
    }
}

// DISPLAY SERVICES
$services = mysqli_query($conn, "SELECT * FROM services ORDER BY service_id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Service Management</title>

    <link rel="stylesheet" href="../css/admin.css">

</head>

<body>

<div class="main-content services-page">

    <!-- PAGE HEADER -->
    <div class="page-header">

        <div>
            <span class="page-eyebrow">
                SPA MANAGEMENT
            </span>

            <h1>
                Service Management
            </h1>

            <p>
                Manage the spa services available for customer appointments.
            </p>
        </div>

    </div>


    <!-- CONTENT -->
    <div class="services-layout">


        <!-- ADD SERVICE -->
        <div class="service-form-card">

            <div class="card-heading">

                <div class="card-icon">
                    ✦
                </div>

                <div>
                    <h2>
                        Add New Service
                    </h2>

                    <p>
                        Create a service for customers to book.
                    </p>
                </div>

            </div>


            <?php if ($message != ""): ?>

                <div class="service-message">
                    ✓
                    <span>
                        <?php echo htmlspecialchars($message); ?>
                    </span>
                </div>

            <?php endif; ?>


            <form method="POST">


                <!-- SERVICE NAME -->
                <div class="service-form-group">

                    <label>
                        Service Name
                    </label>

                    <input
                        type="text"
                        name="service_name"
                        placeholder="Example: Swedish Massage"
                        required
                    >

                </div>


                <!-- DESCRIPTION -->
                <div class="service-form-group">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="4"
                        placeholder="Describe the service..."
                        required
                    ></textarea>

                </div>


                <!-- PRICE + DURATION -->
                <div class="service-form-row">

                    <div class="service-form-group">

                        <label>
                            Price
                        </label>

                        <div class="service-input-prefix">

                            <span>₱</span>

                            <input
                                type="number"
                                step="0.01"
                                name="price"
                                placeholder="0.00"
                                required
                            >

                        </div>

                    </div>


                    <div class="service-form-group">

                        <label>
                            Duration
                        </label>

                        <div class="service-input-suffix">

                            <input
                                type="number"
                                name="duration"
                                placeholder="60"
                                required
                            >

                            <span>min</span>

                        </div>

                    </div>

                </div>


                <!-- STATUS -->
                <div class="service-form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option value="Active">
                            Active
                        </option>

                        <option value="Inactive">
                            Inactive
                        </option>

                    </select>

                </div>


                <button
                    type="submit"
                    name="add_service"
                    class="service-submit"
                >
                    <span>＋</span>
                    Add Service
                </button>

            </form>

        </div>


        <!-- SERVICES LIST -->
        <div class="services-list-card">

            <div class="card-heading">

                <div class="card-icon">
                    ✦
                </div>

                <div>
                    <h2>
                        Services
                    </h2>

                    <p>
                        Available spa treatments and services.
                    </p>
                </div>

            </div>


            <div class="services-table-wrapper">

                <table class="services-table">

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Service</th>

                            <th>Price</th>

                            <th>Duration</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (mysqli_num_rows($services) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($services)): ?>

                            <tr>

                                <td>
                                    <span class="service-id">
                                        #<?php echo $row['service_id']; ?>
                                    </span>
                                </td>


                                <td>

                                    <div class="service-name-cell">

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['service_name']
                                            );
                                            ?>
                                        </strong>

                                        <span>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['description']
                                            );
                                            ?>
                                        </span>

                                    </div>

                                </td>


                                <td>

                                    <strong class="service-price">
                                        ₱<?php
                                        echo number_format(
                                            $row['price'],
                                            2
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <td>

                                    <span class="duration-badge">
                                        <?php echo $row['duration']; ?> min
                                    </span>

                                </td>


                                <td>

                                    <?php if ($row['status'] == 'Active'): ?>

                                        <span class="service-status active">
                                            <span>●</span>
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="service-status inactive">
                                            <span>●</span>
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <div class="service-actions">

                                        <a
                                            href="edit_service.php?id=<?php echo $row['service_id']; ?>"
                                            class="service-edit"
                                        >
                                            Edit
                                        </a>


                                        <a
                                            href="delete_service.php?id=<?php echo $row['service_id']; ?>"
                                            class="service-delete"
                                            onclick="return confirm('Are you sure you want to delete this service?');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="6"
                                class="services-empty"
                            >

                                <div>
                                    ✦
                                </div>

                                <strong>
                                    No services found
                                </strong>

                                <p>
                                    Add your first spa service using the form.
                                </p>

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<?php
include "includes/footer.php";
?>

</body>
</html>