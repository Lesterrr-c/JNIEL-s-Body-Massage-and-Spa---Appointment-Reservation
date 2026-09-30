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

$message = "";


/*
|--------------------------------------------------------------------------
| ADD THERAPIST
|--------------------------------------------------------------------------
*/

if (isset($_POST['add_therapist'])) {

    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $status = $_POST['status'];

    if ($full_name == "") {

        $message = "❌ Therapist name is required.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO therapists
            (full_name, phone, specialization, status)
            VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {

            $message = "❌ Database error: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $full_name,
                $phone,
                $specialization,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {

                $message = "✓ Therapist added successfully.";

            } else {

                $message = "❌ Failed to add therapist: "
                    . mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET THERAPISTS
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM therapists
     ORDER BY therapist_id DESC"
);

?>

<div class="main-content">

    <h1>Therapist Management</h1>

    <p>
        Manage the therapists available for customer appointments.
    </p>


    <?php if ($message != ""): ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <div class="management-container">


        <!-- =====================================================
             ADD THERAPIST
        ====================================================== -->

        <div class="form-card">

            <h2>Add New Therapist</h2>

            <form method="POST">

                <div class="form-group">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        placeholder="Example: Maria Santos"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        placeholder="09XXXXXXXXX"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Specialization
                    </label>

                    <input
                        type="text"
                        name="specialization"
                        placeholder="Example: Swedish Massage"
                    >

                </div>


                <div class="form-group">

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
                    name="add_therapist"
                >
                    ➕ Add Therapist
                </button>

            </form>

        </div>


        <!-- =====================================================
             THERAPIST LIST
        ====================================================== -->

        <div class="table-card">

            <h2>Therapists</h2>

            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Full Name</th>

                            <th>Phone</th>

                            <th>Specialization</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (mysqli_num_rows($result) > 0): ?>

                        <?php while ($row = mysqli_fetch_assoc($result)): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo $row['therapist_id'];
                                    ?>
                                </td>


                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['full_name']
                                        );
                                        ?>
                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['phone']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $row['specialization']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php if ($row['status'] == "Active"): ?>

                                        <span class="badge-active">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="badge-inactive">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <a
                                        href="edit_therapists.php?id=<?php echo $row['therapist_id']; ?>"
                                        class="btn-edit"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        href="delete_therapists.php?id=<?php echo $row['therapist_id']; ?>"
                                        class="btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this therapist?');"
                                    >
                                        Delete
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="6">
                                No therapists found.
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