<?php
    session_start();
    date_default_timezone_set('Asia/Manila');
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['customer_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    include "../db.php";

    // ========================================= GET THERAPIST AVAILABILITY BY DATE  ========================================= //

    if (isset($_GET['get_booked_slots']) && isset($_GET['date'])) {

        $selected_date = $_GET['date'];

        $availability = [];

        // Get all active therapists
        $therapist_query = mysqli_query(
            $conn,
            "SELECT therapist_id
            FROM therapists
            WHERE status = 'Active'"
        );

        $active_therapist_ids = [];

        while ($therapist = mysqli_fetch_assoc($therapist_query)) {

            $active_therapist_ids[] =
                (int)$therapist['therapist_id'];
        }

        $total_therapists = count($active_therapist_ids);

        // Get appointments for the selected date
        $appointment_query = mysqli_prepare(
            $conn,

            "SELECT
                a.appointment_id,
                a.appointment_time,
                a.customer_id,
                a.therapist_id,
                a.preferred_therapist_id,
                a.status,
                s.service_id,
                s.service_name,
                s.duration
            FROM appointments a
            INNER JOIN services s
                ON a.service_id = s.service_id
            WHERE a.appointment_date = ?
            AND a.status != 'Cancelled'"
        );

        mysqli_stmt_bind_param(
            $appointment_query,
            "s",
            $selected_date
        );

        mysqli_stmt_execute($appointment_query);

        $appointment_result =
            mysqli_stmt_get_result($appointment_query);

        /*
        * Store every existing appointment.
        * JavaScript will use this information to determine
        * how many therapists are available for each time.
        */

        $appointments = [];

        while ($appointment = mysqli_fetch_assoc($appointment_result)) {

            if ($appointment['therapist_id'] === NULL) {
                continue;
            }

            $appointments[] = [
                'therapist_id' =>
                    (int)$appointment['therapist_id'],

                'start' =>
                    timeToMinutes(
                        $appointment['appointment_time']
                    ),

                'duration' =>
                    (int)$appointment['duration']
            ];
        }

        header("Content-Type: application/json");

        echo json_encode([
            'total_therapists' => $total_therapists,
            'appointments' => $appointments
        ]);

        exit();
    }

    $message = "";

    $show_overlap_confirmation = false;
    $overlap_details = [];

    $confirm_overlap = 
    isset($_POST['confirm_overlap'])
    && $_POST['confirm_overlap'] === '1';



    // ================== APPOINTMENT SCHEDULING HELPERS===========================//

    function timeToMinutes($time)
    {
        list($hours, $minutes) = explode(':', $time);

        return ((int)$hours * 60) + (int)$minutes;
    }

    function hasTimeOverlap($start1, $end1, $start2, $end2)
    {
        return $start1 < $end2 && $end1 > $start2;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $customer_id = $_SESSION['customer_id'];

    /*
     * =========================================================
     * OVERLAPPING BOOKING CONFIRMATION
     * =========================================================
     *
     * If the customer clicked "Yes, Book Anyway",
     * retrieve the original booking details from the session.
     *
     * We do NOT trust hidden form values for the confirmation.
     */

    if ($confirm_overlap) {

        if (
            !isset($_SESSION['pending_booking']) ||
            !is_array($_SESSION['pending_booking'])
        ) {

            $message = "
                <div class='alert error'>
                    <span>⚠</span>
                    <div>
                        <strong>Booking confirmation expired</strong>
                        <p>Please select your appointment details again.</p>
                    </div>
                </div>
            ";

        } else {

            $pending = $_SESSION['pending_booking'];

            /*
             * Confirmation is valid only for a limited time.
             * This prevents an old booking from remaining
             * indefinitely in the session.
             */

            if (
                !isset($pending['created_at']) ||
                (time() - (int)$pending['created_at']) > 600
            ) {

                unset($_SESSION['pending_booking']);

                $message = "
                    <div class='alert error'>
                        <span>⚠</span>
                        <div>
                            <strong>Booking confirmation expired</strong>
                            <p>Please select your appointment details again.</p>
                        </div>
                    </div>
                ";

            } else {

                $service_id =
                    (int)$pending['service_id'];

                $preferred_therapist_id =
                    !empty($pending['preferred_therapist_id'])
                    ? (int)$pending['preferred_therapist_id']
                    : NULL;

                $appointment_date =
                    $pending['appointment_date'];

                $appointment_time =
                    $pending['appointment_time'];
            }
        }

    } else {

        $service_id = isset($_POST['service_id'])
            ? (int)$_POST['service_id']
            : 0;

        // This is the customer's requested/preferred therapist.
        // NULL means No Preference.
        $preferred_therapist_id = !empty($_POST['therapist_id'])
            ? (int)$_POST['therapist_id']
            : NULL;

        $appointment_date =
            $_POST['appointment_date'] ?? '';

        $appointment_time =
            $_POST['appointment_time'] ?? '';
    }

        // =========================================
        // APPOINTMENT TIME VALIDATION
        // =========================================

        $current_date = date('Y-m-d');
        $current_time = date('H:i');

        // Spa operating hours
        $opening_time = '10:00';
        $closing_time = '21:00';

        // Prevent past date
        if ($appointment_date < $current_date) {

            $message = "
                <div class='alert error'>
                    <span>⚠</span>
                    <div>
                        <strong>Invalid date</strong>
                        <p>Please select today or a future date.</p>
                    </div>
                </div>
            ";

        }

        // Prevent past time when booking today
        elseif (
            $appointment_date === $current_date &&
            $appointment_time <= $current_time
        ) {

            $message = "
                <div class='alert error'>
                    <span>⚠</span>
                    <div>
                        <strong>Invalid time</strong>
                        <p>Please select a future appointment time.</p>
                    </div>
                </div>
            ";

        }

        // Prevent appointments outside operating hours
        elseif (
            $appointment_time < $opening_time ||
            $appointment_time >= $closing_time
        ) {

            $message = "
                <div class='alert error'>
                    <span>⚠</span>
                    <div>
                        <strong>Outside operating hours</strong>
                        <p>Our appointment hours are from 10:00 AM to 8:00 PM.</p>
                    </div>
                </div>
            ";

        }

        else {

            // =========================================
            // GET SELECTED SERVICE + DURATION
            // =========================================

            $service_query = mysqli_prepare(
                $conn,
                "SELECT service_id, duration
                FROM services
                WHERE service_id = ?
                AND status = 'Active'
                LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $service_query,
                "i",
                $service_id
            );

            mysqli_stmt_execute($service_query);

            $service_result = mysqli_stmt_get_result($service_query);

            if (mysqli_num_rows($service_result) === 0) {

                $message = "
                    <div class='alert error'>
                        <span>⚠</span>
                        <div>
                            <strong>Invalid service</strong>
                            <p>The selected service is no longer available.</p>
                        </div>
                    </div>
                ";

            } else {

                $service_data = mysqli_fetch_assoc($service_result);

                // Duration is stored as a number:
                // 45, 60, 90, etc.
                $duration = (int)$service_data['duration'];

                if ($duration <= 0) {

                    $message = "
                        <div class='alert error'>
                            <span>⚠</span>
                            <div>
                                <strong>Invalid service duration</strong>
                                <p>This service does not have a valid duration.</p>
                            </div>
                        </div>
                    ";

                } else {

                    // =========================================
                    // CALCULATE START + END TIME
                    // =========================================

                    $appointment_start_minutes =
                        timeToMinutes($appointment_time);

                    $appointment_end_minutes =
                        $appointment_start_minutes + $duration;

                    $closing_minutes =
                        timeToMinutes($closing_time);

                    // Appointment must finish before closing.
                    if ($appointment_end_minutes > $closing_minutes) {

                        $message = "
                            <div class='alert error'>
                                <span>⚠</span>
                                <div>
                                    <strong>Too late for this service</strong>
                                    <p>
                                        This service takes {$duration} minutes
                                        and would finish after our 9:00 PM closing time.
                                        Please choose an earlier time.
                                    </p>
                                </div>
                            </div>
                        ";

                    } else {

                        // =========================================
                        // START DATABASE TRANSACTION
                        // =========================================

                        mysqli_begin_transaction($conn);

                        try {

                            // =========================================
                            // GET ACTIVE THERAPISTS AND LOCK THEIR ROWS
                            // =========================================

                            $therapist_query = mysqli_query(
                                $conn,
                                "SELECT therapist_id, full_name
                                FROM therapists
                                WHERE status = 'Active'
                                ORDER BY full_name ASC
                                FOR UPDATE"
                            );

                            if (!$therapist_query) {
                                throw new Exception(
                                    "Unable to load therapists."
                                );
                            }

                            $active_therapists = [];
                            $therapist_names = [];

                            while ($therapist = mysqli_fetch_assoc($therapist_query)) {

                                $tid = (int)$therapist['therapist_id'];

                                $active_therapists[] = $tid;
                                $therapist_names[$tid] =
                                    $therapist['full_name'];
                            }

                            if (count($active_therapists) === 0) {

                                throw new Exception(
                                    "There are currently no active therapists available."
                                );
                            }

                            // =========================================
                            // VALIDATE PREFERRED THERAPIST
                            // =========================================

                            if (
                                $preferred_therapist_id !== NULL &&
                                !in_array(
                                    $preferred_therapist_id,
                                    $active_therapists,
                                    true
                                )
                            ) {

                                throw new Exception(
                                    "The selected therapist is no longer available."
                                );
                            }

                            // ========================================= GET ALL APPOINTMENTS FOR THIS DATE
                            // AND LOCK THEM
                            // =========================================

                            $appointment_query = mysqli_prepare(
                                $conn,
                                "SELECT
                                     a.appointment_id,
                                     a.appointment_time,
                                     a.customer_id,
                                     a.therapist_id,
                                     a.preferred_therapist_id,
                                     a.status,
                                     s.service_id,
                                     s.service_name,
                                     s.duration
                                FROM appointments a
                                INNER JOIN services s
                                    ON a.service_id = s.service_id
                                WHERE a.appointment_date = ?
                                AND a.status != 'Cancelled'"
                            );

                            if (!$appointment_query) {
                                throw new Exception(
                                    "Unable to check appointment availability."
                                );
                            }

                            mysqli_stmt_bind_param(
                                $appointment_query,
                                "s",
                                $appointment_date
                            );

                            mysqli_stmt_execute($appointment_query);

                            $appointment_result =
                                mysqli_stmt_get_result(
                                    $appointment_query
                                );

                            // =========================================
                            // BUILD THERAPIST SCHEDULE
                            // =========================================

                            $schedules = [];

                            foreach ($active_therapists as $tid) {
                                $schedules[$tid] = [];
                            }

                           

                            while (
                                    $existing =
                                    mysqli_fetch_assoc($appointment_result)
                                    ) {

                                    /*
     * =========================================================
     * CHECK IF THIS CUSTOMER ALREADY HAS AN OVERLAPPING
     * APPOINTMENT
     * =========================================================
     *
     * This does NOT automatically reject the booking.
     *
     * If an overlap exists, the customer will be asked
     * whether they really want to continue.
     */

    if (
        !$confirm_overlap &&
        (int)$existing['customer_id'] === (int)$customer_id
    ) {

        $existing_customer_start =
            timeToMinutes(
                $existing['appointment_time']
            );

        $existing_customer_duration =
            (int)$existing['duration'];

        $existing_customer_end =
            $existing_customer_start +
            $existing_customer_duration;

        if (
            hasTimeOverlap(
                $appointment_start_minutes,
                $appointment_end_minutes,
                $existing_customer_start,
                $existing_customer_end
            )
        ) {

            /*
             * Save the requested booking in the session.
             * The actual appointment is NOT inserted yet.
             */

            $_SESSION['pending_booking'] = [
                'service_id' =>
                    $service_id,

                'preferred_therapist_id' =>
                    $preferred_therapist_id,

                'appointment_date' =>
                    $appointment_date,

                'appointment_time' =>
                    $appointment_time,

                'created_at' =>
                    time()
            ];

            /*
             * Save details of the conflicting appointment
             * for the confirmation modal.
             */

            $overlap_details[] = [
                'appointment_id' =>
                    (int)$existing['appointment_id'],

                'service_name' =>
                    $existing['service_name'],

                'appointment_time' =>
                    $existing['appointment_time'],

                'duration' =>
                    $existing_customer_duration,

                'therapist_name' =>
                    $existing['therapist_name']
                    ?: 'Not assigned',

                'status' =>
                    $existing['status']
            ];

            /*
             * Stop checking once we have found an overlap.
             */

            break;
        }
    }

        

        // =========================================
        // EXISTING THERAPIST SCHEDULE
        // =========================================

        $existing_therapist =
            $existing['therapist_id'] !== NULL
            ? (int)$existing['therapist_id']
            : NULL;

                                // An appointment without an assigned
                                // therapist does not occupy anyone.
                                if ($existing_therapist === NULL) {
                                    continue;
                                }

                                // Ignore inactive therapist assignments
                                if (
                                    !isset(
                                        $schedules[$existing_therapist]
                                    )
                                ) {
                                    continue;
                                }

                                $existing_start =
                                    timeToMinutes(
                                        $existing['appointment_time']
                                    );

                                $existing_duration =
                                    (int)$existing['duration'];

                                $existing_end =
                                    $existing_start +
                                    $existing_duration;

                                $schedules[$existing_therapist][] = [
                                    'appointment_id' =>
                                        (int)$existing['appointment_id'],

                                    'start' =>
                                        $existing_start,

                                    'end' =>
                                        $existing_end,

                                    'status' =>
                                        $existing['status'],

                                    'preferred_therapist_id' =>
                                        $existing['preferred_therapist_id']
                                        !== NULL
                                        ? (int)$existing[
                                            'preferred_therapist_id'
                                        ]
                                        : NULL
                                ];
                            }

                           

                            /** =========================================================
 * CUSTOMER OVERLAP FOUND
 * =========================================================
 *
 * Roll back the transaction because we are only asking
 * for confirmation. No appointment should be inserted yet.
 */

if (
    !$confirm_overlap &&
    !empty($overlap_details)
) {

    mysqli_rollback($conn);

    $show_overlap_confirmation = true;

    /*
     * Skip the rest of the booking process.
     *
     * We use a special exception so the existing try/catch
     * structure remains intact.
     */

    throw new Exception('__OVERLAP_CONFIRMATION_REQUIRED__');
}



                            // =========================================
                            // FUNCTION TO CHECK THERAPIST AVAILABILITY
                            // =========================================

                            $isTherapistAvailable =
                                function (
                                    $therapist_id,
                                    $start,
                                    $end
                                ) use (&$schedules) {

                                    if (
                                        !isset(
                                            $schedules[$therapist_id]
                                        )
                                    ) {
                                        return false;
                                    }

                                    foreach (
                                        $schedules[$therapist_id]
                                        as $slot
                                    ) {

                                        if (
                                            hasTimeOverlap(
                                                $start,
                                                $end,
                                                $slot['start'],
                                                $slot['end']
                                            )
                                        ) {
                                            return false;
                                        }
                                    }

                                    return true;
                                };

                            // =========================================
                            // DETERMINE ACTUAL THERAPIST
                            // =========================================

                            $assigned_therapist_id = NULL;

                            // =========================================
                            // CASE 1:
                            // CUSTOMER HAS A PREFERRED THERAPIST
                            // =========================================

                            if (
                                $preferred_therapist_id !== NULL
                            ) {

                                $target_therapist =
                                    $preferred_therapist_id;

                                $target_conflicts = [];

                                if (
                                    isset(
                                        $schedules[$target_therapist]
                                    )
                                ) {

                                    foreach (
                                        $schedules[$target_therapist]
                                        as $slot
                                    ) {

                                        if (
                                            hasTimeOverlap(
                                                $appointment_start_minutes,
                                                $appointment_end_minutes,
                                                $slot['start'],
                                                $slot['end']
                                            )
                                        ) {

                                            $target_conflicts[] = $slot;
                                        }
                                    }
                                }

                                // -----------------------------------------
                                // Preferred therapist is completely free
                                // -----------------------------------------

                                if (
                                    count($target_conflicts) === 0
                                ) {

                                    $assigned_therapist_id =
                                        $target_therapist;

                                }
    // -----------------------------------------
    // Preferred therapist is occupied.
    // Do NOT move or reassign existing
    // appointments.
    // -----------------------------------------

    else {

        throw new Exception(
            "Your preferred therapist is unavailable for this time. Please choose another therapist or select No Preference."
        );
    }
                            }

                            // =========================================
                            // CASE 2:
                            // NO PREFERENCE
                            // =========================================

                            else {

                                /*
                                * Choose the LAST available therapist.
                                *
                                * The active therapists are loaded
                                * alphabetically, so we search from
                                * the last therapist backward.
                                *
                                * This keeps preferred therapists
                                * available when possible.
                                */

                                for (
                                    $i = count($active_therapists) - 1;
                                    $i >= 0;
                                    $i--
                                ) {

                                    $candidate_id =
                                        $active_therapists[$i];

                                    if (
                                        $isTherapistAvailable(
                                            $candidate_id,
                                            $appointment_start_minutes,
                                            $appointment_end_minutes
                                        )
                                    ) {

                                        $assigned_therapist_id =
                                            $candidate_id;

                                        break;
                                    }
                                }

                                if (
                                    $assigned_therapist_id === NULL
                                ) {

                                    throw new Exception(
                                        "All therapists are already booked for this time. Please choose another time."
                                    );
                                }
                            }

                            // =========================================
                            // FINAL SAFETY CHECK
                            // =========================================

                            if (
                                $assigned_therapist_id === NULL
                            ) {

                                throw new Exception(
                                    "No therapist is available for the selected time."
                                );
                            }

                            // =========================================
                            // INSERT NEW APPOINTMENT
                            // =========================================

                            $insert = mysqli_prepare(
                                $conn,
                                "INSERT INTO appointments
                                (
                                    customer_id,
                                    service_id,
                                    therapist_id,
                                    preferred_therapist_id,
                                    appointment_date,
                                    appointment_time,
                                    status
                                )
                                VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
                            );

                            if (!$insert) {

                                throw new Exception(
                                    "Unable to prepare appointment."
                                );
                            }

                            mysqli_stmt_bind_param(
                                $insert,
                                "iiiiss",
                                $customer_id,
                                $service_id,
                                $assigned_therapist_id,
                                $preferred_therapist_id,
                                $appointment_date,
                                $appointment_time
                            );

                            if (
                                !mysqli_stmt_execute($insert)
                            ) {

                                throw new Exception(
                                    "Unable to save appointment."
                                );
                            }

                        

                            // =========================================
    // EVERYTHING SUCCESSFUL
    // =========================================

    mysqli_commit($conn);

    unset($_SESSION['pending_booking']);

    if ($preferred_therapist_id !== NULL) {

        $message = "
            <div class='alert success'>
                <span>✓</span>
                <div>
                    <strong>Appointment submitted!</strong>
                    <p>Your appointment is now pending approval.</p>
                </div>
            </div>
        ";

    } else {

        $message = "
            <div class='alert success'>
                <span>✓</span>
                <div>
                    <strong>Appointment submitted!</strong>
                    <p>Your appointment is now pending approval.</p>
                </div>
            </div>
        ";
    }

                        } catch (Exception $e) {

    // =========================================
    // CUSTOMER OVERLAP CONFIRMATION
    // =========================================

    if (
        $e->getMessage() ===
        '__OVERLAP_CONFIRMATION_REQUIRED__'
    ) {

        /*
         * The transaction was already rolled back
         * before this exception was thrown.
         *
         * Do not show an error message.
         * The confirmation modal will be displayed instead.
         */

        $show_overlap_confirmation = true;

    } else {

        // =========================================
        // ROLLBACK EVERYTHING IF SOMETHING FAILED
        // =========================================

        mysqli_rollback($conn);

        $message = "
            <div class='alert error'>
                <span>⚠</span>
                <div>
                    <strong>Schedule unavailable</strong>
                    <p>" .
                    htmlspecialchars(
                        $e->getMessage()
                    ) .
                    "</p>
                </div>
            </div>
        ";
    }
}
                    }
                }
            }
        }
    }

    // Get active services
    $services = mysqli_query(
        $conn,
        "SELECT * 
        FROM services 
        WHERE status='Active'"
    );

    $therapists = mysqli_query(
        $conn, 
        "SELECT * 
        FROM therapists 
        WHERE status = 'Active'
        ORDER BY full_name ASC"
    );

    if (!$therapists) {
        die("THERAPIST QUERY ERROR: " .
        mysqli_error($conn));
    }

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Book Appointment | JNIEL'S Body Massage & Spa</title>

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



    <div class="booking-page">

        <div class="booking-wrapper">

            <!-- LEFT SIDE -->

            <section class="booking-info">

                <div class="brand-small">
                    JNIEL'S Body Massage & Spa
                </div>

                <h1>
                    Make time<br>
                    for yourself.
                </h1>

                <p class="description">
                    Reserve your preferred spa service and schedule
                    a relaxing experience designed around your time.
                </p>

                <div class="booking-tips">

                    <div class="tip">

                        <div class="tip-icon">✓</div>

                        <div>
                            <strong>Choose your service</strong>
                            <span>Select the treatment that suits you.</span>
                        </div>

                    </div>

                    <div class="tip">

                        <div class="tip-icon">◷</div>

                        <div>
                            <strong>Select your schedule</strong>
                            <span>Choose an available date and time.</span>
                        </div>

                    </div>

                    <div class="tip">

                        <div class="tip-icon">✦</div>

                        <div>
                            <strong>Wait for confirmation</strong>
                            <span>Your appointment will be reviewed by our staff.</span>
                        </div>

                    </div>

                </div>

            </section>


            <!-- RIGHT SIDE -->

            <section class="booking-form">

                <a href="dashboard.php" class="back-button">
                    ← Back to Dashboard
                </a>

                <div class="form-heading">

                    <div class="eyebrow">
                        Appointment
                    </div>

                    <h2>Book a Session</h2>

                    <p>
                        Tell us when you'd like to visit.
                    </p>

                </div>

                <?php echo $message; ?>

                <?php if (
    $show_overlap_confirmation &&
    !empty($overlap_details)
): ?>

<div class="booking-modal-overlay" id="overlapModal">

    <div class="booking-modal">

        <button
            type="button"
            class="booking-modal-close"
            onclick="closeOverlapModal()"
            aria-label="Close"
        >
            ×
        </button>

        <div class="booking-modal-icon">
            !
        </div>

        <div class="booking-modal-heading">

            <span class="booking-modal-eyebrow">
                Appointment notice
            </span>

            <h3>
                You already have an appointment
            </h3>

            <p>
                You already have an appointment that overlaps
                with the schedule you selected.
            </p>

        </div>


        <div class="overlap-appointment-list">

            <?php foreach ($overlap_details as $overlap): ?>

                <div class="overlap-appointment-card">

                    <div class="overlap-card-row">

                        <span class="overlap-label">
                            Service
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $overlap['service_name']
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="overlap-card-row">

                        <span class="overlap-label">
                            Time
                        </span>

                        <strong>
                            <?php
                            echo date(
                                'g:i A',
                                strtotime(
                                    $overlap['appointment_time']
                                )
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="overlap-card-row">

                        <span class="overlap-label">
                            Duration
                        </span>

                        <strong>
                            <?php
                            echo (int)$overlap['duration'];
                            ?> minutes
                        </strong>

                    </div>


                    <div class="overlap-card-row">

                        <span class="overlap-label">
                            Therapist
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $overlap['therapist_name']
                            );
                            ?>
                        </strong>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>


        <div class="booking-modal-warning">

            <span>⚠</span>

            <p>
                You can continue if you intentionally want
                to make another appointment during the same
                time. Therapist availability will still be
                checked before the new booking is created.
            </p>

        </div>


        <div class="booking-modal-actions">

            <button
                type="button"
                class="modal-back-button"
                onclick="closeOverlapModal()"
            >
                Go Back
            </button>


            <form method="POST">

                <input
                    type="hidden"
                    name="confirm_overlap"
                    value="1"
                >

                <button
                    type="submit"
                    class="modal-confirm-button"
                >
                    Yes, Book Anyway
                </button>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


                <form method="POST">


                    <!-- SERVICE -->

                    <div class="form-group">

                        <label for="service_id">
                            Spa Service
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">✦</span>

                            <select
                                id="service_id"
                                name="service_id"
                                class="form-control with-icon"
                                required
                            >

                                <option value="">
                                    Select a service
                                </option>

                                <?php while ($service = mysqli_fetch_assoc($services)) { ?>

                                    <option value="<?php echo $service['service_id']; ?>">

                                        <?php
                                        echo htmlspecialchars($service['service_name']);
                                        echo " — ₱";
                                        echo number_format($service['price'], 2);
                                        ?>

                                    </option>

                                <?php } ?>

                            </select>

                        </div>

                    </div>

        
                            
            


                    <!-- DATE -->

                    <div class="form-group">

                        <label for="appointment_date">
                            Preferred Date
                        </label>

                        <div class="input-wrapper">

                            <span class="input-icon">▣</span>

                            <input
                                type="date"
                                id="appointment_date"
                                name="appointment_date"
                                class="form-control with-icon"
                                min="<?php echo date('Y-m-d'); ?>"
                                required
                            >

                        </div>

                    </div>


                    <!-- TIME -->

                    <div class="form-group">

        <label for="appointment_time">
            Preferred Time
        </label>

        <div class="input-wrapper">

            <span class="input-icon">◷</span>

            <select
                id="appointment_time"
                name="appointment_time"
                class="form-control with-icon"
                required
            >

                <option value="">
                    Select a date first
                </option>

            </select>

        </div>

        <small class="time-help">
            Appointments are available from 10:00 AM to 8:00 PM.
        </small>

    </div>

                    <!-- THERAPISTS -->

                    <div class="form-group">

                        <label for="therapist_id">
                            Preferred Therapist (optional)
                        </label>
                        
                        <div class="input-wrapper">

                            <span class="input-icon">✦</span>

                            <select 
                            name="therapist_id"
                            id="therapist_id" 
                            class="form-control with-icon">

                            <option value="">
                                No Preference 
                            </option>
                            
                            <?php while ($therapist = mysqli_fetch_assoc($therapists)) { ?>
                            
                            <option value="<?php echo $therapist['therapist_id']; ?>">

                            <?php 
                            echo htmlspecialchars($therapist['full_name']);
                            ?>
                            
                            </option>

                            <?php }?>

                            </select>
                    </div>
                </div>


                    <button type="submit" class="submit-button">
                        Confirm Appointment →
                    </button>

                    <p class="form-note">
                        Your appointment will remain pending until approved by the spa.
                    </p>

                </form>

            </section>

        </div>

    </div>

    

    <script>

    const dateInput = document.getElementById("appointment_date");
    const timeInput = document.getElementById("appointment_time");


    /*
    =========================================================
    SPA OPERATING HOURS
    =========================================================
    */

    const openingHour = 10;
    const closingHour = 21;


    /*
    =========================================================
    CREATE TIME SLOTS
    =========================================================
    */

    function generateTimeSlots() {

        timeInput.innerHTML = "";

        const selectedDate = dateInput.value;

        if (!selectedDate) {

            const option = document.createElement("option");

            option.value = "";
            option.textContent = "Select a date first";

            timeInput.appendChild(option);

            return;
        }


        /*
        =====================================================
        SHOW LOADING
        =====================================================
        */

        const loadingOption = document.createElement("option");

        loadingOption.value = "";
        loadingOption.textContent = "Checking availability...";

        timeInput.appendChild(loadingOption);


    fetch(
        "book_appointment.php?get_booked_slots=1&date=" +
        encodeURIComponent(selectedDate)
    )

    .then(response => response.json())

    .then(data => {

        timeInput.innerHTML = "";

        const totalTherapists =
            data.total_therapists;

        const appointments =
            data.appointments;


        /*
        * =================================================
        * CURRENT DATE AND TIME
        * =================================================
        */

        const now = new Date();

        const today =
            now.getFullYear() +
            "-" +
            String(now.getMonth() + 1).padStart(2, "0") +
            "-" +
            String(now.getDate()).padStart(2, "0");


        let availableCount = 0;


        /*
        * =================================================
        * CREATE HOURLY SLOTS
        * =================================================
        */

        for (
            let hour = openingHour;
            hour < closingHour;
            hour++
        ) {

            const timeValue =
                String(hour).padStart(2, "0") + ":00";


            const slotStart =
                hour * 60;


            /*
            * ---------------------------------------------
            * PREVENT PAST TIMES TODAY
            * ---------------------------------------------
            */

            if (
                selectedDate === today &&
                hour <= now.getHours()
            ) {

                continue;
            }


            /*
            * ---------------------------------------------
            * CREATE OPTION
            * ---------------------------------------------
            */

            const option =
                document.createElement("option");

            option.value = timeValue;


            /*
            * ---------------------------------------------
            * FORMAT TIME
            * ---------------------------------------------
            */

            let displayHour = hour;

            let period = "AM";

            if (hour >= 12) {
                period = "PM";
            }

            if (hour > 12) {
                displayHour = hour - 12;
            }

            const displayTime =
                displayHour + ":00 " + period;


            /*
            * ---------------------------------------------
            * COUNT AVAILABLE THERAPISTS
            * ---------------------------------------------
            *
            * IMPORTANT:
            * This frontend calculation is only for displaying
            * availability.
            *
            * The PHP backend remains the final authority.
            */

            let occupiedTherapists = 0;


            /*
            * Track each therapist only once.
            */

            const occupiedIds = new Set();


            appointments.forEach(appointment => {

                const existingStart =
                    appointment.start;

                const existingEnd =
                    existingStart +
                    appointment.duration;


                /*
                * Current frontend slot is one hour.
                *
                * We check whether the existing appointment
                * overlaps this time slot.
                */

                const slotEnd =
                    slotStart + 60;


                const overlaps =
                    slotStart < existingEnd &&
                    slotEnd > existingStart;


                if (overlaps) {

                    occupiedIds.add(
                        appointment.therapist_id
                    );
                }

            });


            occupiedTherapists =
                occupiedIds.size;


            const availableTherapists =
                totalTherapists -
                occupiedTherapists;


            /*
            * ---------------------------------------------
            * DISPLAY AVAILABILITY
            * ---------------------------------------------
            */

            if (availableTherapists <= 0) {

                option.textContent =
                    displayTime +
                    " — Fully Booked";

                option.disabled = true;

            }

            else {

                option.textContent =
                    displayTime +
                    " — " +
                    availableTherapists +
                    " therapist" +
                    (
                        availableTherapists > 1
                            ? "s"
                            : ""
                    ) +
                    " available";

                availableCount++;
            }


            timeInput.appendChild(option);

        }


        /*
        =================================================
            NO AVAILABLE TIMES
        =================================================
        */

        if (availableCount === 0) {

            timeInput.innerHTML = "";

            const option =
                document.createElement("option");

            option.value = "";

            option.textContent =
                "No available times for this date";

            timeInput.appendChild(option);
        }

    })

    .catch(error => {

        console.error(
            "Error checking appointment availability:",
            error
        );

        timeInput.innerHTML = "";

        const option =
            document.createElement("option");

        option.value = "";

        option.textContent =
            "Unable to check availability";

        timeInput.appendChild(option);

    });

    }


    /*
    =========================================================
    WHEN DATE CHANGES
    =========================================================
    */

    dateInput.addEventListener(
        "change",
        generateTimeSlots
    );


    /*
    =========================================================
    INITIALIZE
    =========================================================
    */

    generateTimeSlots();

    function closeOverlapModal() {

    const modal =
        document.getElementById("overlapModal");

    if (modal) {
        modal.style.display = "none";
    }

}

    </script>

    

    </body>

    </html>
