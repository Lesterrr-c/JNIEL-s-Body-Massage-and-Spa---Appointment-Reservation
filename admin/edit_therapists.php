<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once "../db.php";


// Get therapist ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: therapists.php");
    exit();
}

$therapist_id = intval($_GET['id']);


// Get therapist
$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM therapists
     WHERE therapist_id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $therapist_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: therapists.php");
    exit();
}

$therapist = mysqli_fetch_assoc($result);

$message = "";


// Update therapist
if (isset($_POST['update_therapist'])) {

    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $status = $_POST['status'];

    $update = mysqli_prepare(
        $conn,
        "UPDATE therapists
         SET full_name = ?,
             phone = ?,
             specialization = ?,
             status = ?
         WHERE therapist_id = ?"
    );

    mysqli_stmt_bind_param(
        $update,
        "ssssi",
        $full_name,
        $phone,
        $specialization,
        $status,
        $therapist_id
    );

    if (mysqli_stmt_execute($update)) {

        header("Location: therapists.php");
        exit();

    } else {

        $message = "Failed to update therapist.";
    }
}

include "includes/header.php";
include "includes/sidebar.php";

?>

<div class="main-content">

    <h1>Edit Therapist</h1>

    <p>
        Update therapist information and availability.
    </p>


    <?php if ($message != ""): ?>

        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <div class="form-card">

        <h2>Therapist Information</h2>

        <form method="POST">

            <div class="form-group">

                <label>
                    Full Name
                </label>

                <input
                    type="text"
                    name="full_name"
                    value="<?php echo htmlspecialchars($therapist['full_name']); ?>"
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
                    value="<?php echo htmlspecialchars($therapist['phone']); ?>"
                >

            </div>


            <div class="form-group">

                <label>
                    Specialization
                </label>

                <input
                    type="text"
                    name="specialization"
                    value="<?php echo htmlspecialchars($therapist['specialization']); ?>"
                    placeholder="Example: Swedish Massage"
                >

            </div>


            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option
                        value="Active"
                        <?php
                        if ($therapist['status'] == 'Active') {
                            echo 'selected';
                        }
                        ?>
                    >
                        Active
                    </option>

                    <option
                        value="Inactive"
                        <?php
                        if ($therapist['status'] == 'Inactive') {
                            echo 'selected';
                        }
                        ?>
                    >
                        Inactive
                    </option>

                </select>

            </div>


            <button
                type="submit"
                name="update_therapist"
            >
                Save Changes
            </button>


            <a
                href="therapists.php"
                style="margin-left:10px;"
            >
                Cancel
            </a>

        </form>

    </div>

</div>


<?php
include "includes/footer.php";
?>