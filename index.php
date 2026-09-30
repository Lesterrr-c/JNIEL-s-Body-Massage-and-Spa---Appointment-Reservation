<?php
include "db.php";
include 'includes/header.php';

?>

<main>

<section id="home" class="hero">

<div class="hero-overlay">

<h1>

Relax. Refresh.<br>

Rejuvenate.

</h1>

<p>

Experience premium massage therapy and spa services

designed to relax your body,

refresh your mind,

and restore your well-being.

</p>

<a href="customer/book_appointment.php" class="hero-button">

Book Appointment

</a>

</div>

</section>

<!-- =========================
         ABOUT JNIEL'S
    ========================= -->

    <section id="about" class="about-section">

        <div class="about-container">

            <!-- IMAGE -->
            <div class="about-image">

                <img
                    src="images/logo/about.jpg"
                    alt="JNIEL's Body Massage and Spa"
                >

            </div>


            <!-- CONTENT -->
            <div class="about-content">

                <span class="about-label">
                    ABOUT JNIEL'S
                </span>

                <h2>
                    A Place to Pause,<br>
                    Relax & Reconnect.
                </h2>

                <div class="about-line"></div>

                <p>
                    At JNIEL's Body Massage and Spa, we believe that
                    relaxation is an essential part of taking care of
                    yourself.
                </p>

                <p>
                    Our spa provides a calm and welcoming environment
                    where you can step away from the stresses of
                    everyday life and give your body and mind the care
                    they deserve.
                </p>

                <p>
                    From soothing massage treatments to personalized
                    spa experiences, our goal is to help every guest
                    leave feeling refreshed, relaxed, and renewed.
                </p>

                <a href="customer/book_appointment.php"
                   class="about-button">
                    Book an Appointment
                </a>

            </div>

        </div>

    </section>

    <!-- =========================
     SERVICES SECTION
========================= -->

<section id="services" class="services-section">

    <div class="services-heading">

        <span class="services-label">
            OUR SERVICES
        </span>

        <h2>
            Treatments Designed<br>
            for Your Well-Being
        </h2>

        <p>
            Discover our selection of relaxing massage treatments
            and spa experiences, carefully designed to help you
            relax, restore, and feel your best.
        </p>

    </div>


    <div class="services-grid">

        <?php

        $services_query = mysqli_query($conn, "
            SELECT service_id, service_name, description, price
            FROM services
            WHERE status = 'Active'
            ORDER BY service_id ASC
            LIMIT 6
        ");

        if (mysqli_num_rows($services_query) > 0):

            while ($service = mysqli_fetch_assoc($services_query)):

        ?>

            <div class="service-card">

                <div class="service-number">
                    <?php echo str_pad(
                        $service['service_id'],
                        2,
                        '0',
                        STR_PAD_LEFT
                    ); ?>
                </div>

                <div class="service-card-content">

                    <h3>
                        <?php echo htmlspecialchars(
                            $service['service_name']
                        ); ?>
                    </h3>

                    <?php if (!empty($service['description'])): ?>

                        <p>
                            <?php echo htmlspecialchars(
                                $service['description']
                            ); ?>
                        </p>

                    <?php endif; ?>

                    <div class="service-bottom">

                        <span class="service-price">
                            ₱<?php echo number_format(
                                $service['price'],
                                2
                            ); ?>
                        </span>

                        <a
                            href="customer/book_appointment.php"
                            class="service-book"
                        >
                            Book
                            <span>→</span>
                        </a>

                    </div>

                </div>

            </div>

        <?php

            endwhile;

        else:

        ?>

            <div class="services-empty">

                <p>
                    Our services are currently being updated.
                    Please check back soon.
                </p>

            </div>

        <?php endif; ?>

    </div>


    <div class="services-footer">

        <a
            href="customer/book_appointment.php"
            class="services-button"
        >
            View & Book Services
            <span>→</span>
        </a>

    </div>

</section>

<!-- =========================
     CONTACT SECTION
========================= -->

<section id="contact" class="contact-section">

    <div class="contact-container">

        <!-- LEFT SIDE -->
        <div class="contact-info">

            <span class="contact-label">
                GET IN TOUCH
            </span>

            <h2>
                We're Here<br>
                for You.
            </h2>

            <div class="contact-line"></div>

            <p class="contact-intro">
                Have a question about our services or want to
                schedule your next relaxing experience?
                We'd be happy to assist you.
            </p>


            <!-- CONTACT ITEM -->
            <div class="contact-item">

                <div class="contact-icon">
                    ☎
                </div>

                <div>
                    <span>PHONE</span>

                    <p>
                        0928 437 0386  |  0938 825 0644
                    </p>
                </div>

            </div>


            <!-- CONTACT ITEM -->
            <div class="contact-item">

                <div class="contact-icon">
                    ✉
                </div>

                <div>
                    <span>EMAIL</span>

                    <p>
                        lstrcaparos@gmail.com
                    </p>
                </div>

            </div>


            <!-- CONTACT ITEM -->
            <div class="contact-item">

                <div class="contact-icon">
                    ⌖
                </div>

                <div>
                    <span>LOCATION</span>

                    <p>
                        Reyes Bldg, Better Living Subdivision,
                    </p>
                    <p>
                        119 Doña Soledad Ave, Parañaque, 1711 Metro Manila
                    </p>
                </div>

            </div>

        </div>


        <!-- RIGHT SIDE -->
        <div class="contact-card">

            <span class="contact-card-label">
                YOUR WELL-BEING MATTERS
            </span>

            <h3>
                Ready to Relax?
            </h3>

            <p>
                Give yourself the time you deserve.
                Explore our treatments and book your
                appointment with us today.
            </p>

            <a
                href="customer/book_appointment.php"
                class="contact-button"
            >
                Book an Appointment
                <span>→</span>
            </a>

        </div>

    </div>

</section>

</main>

<?php
include 'includes/footer.php';

?>
<?php include "chatbot/chatbot.php"; ?>