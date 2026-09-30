function toggleChatbot() {

    const chatbotWindow =
        document.getElementById("chatbotWindow");

    const chatbotButton =
        document.getElementById("chatbotButton");

    chatbotWindow.classList.toggle("show");

    chatbotButton.classList.toggle("active");

}


/* =========================
   SEND MESSAGE
========================= */

function sendChatMessage() {

    const input =
        document.getElementById("chatbotInput");

    const messages =
        document.getElementById("chatbotMessages");

    const message =
        input.value.trim();

    if (message === "") {
        return;
    }


    /* Customer message */

    addCustomerMessage(message);

    input.value = "";

    messages.scrollTop =
        messages.scrollHeight;


    /* Bot response */

    setTimeout(function () {

        const response =
            getBotResponse(message);

        addBotMessage(response);

        messages.scrollTop =
            messages.scrollHeight;

    }, 500);

}


/* =========================
   CUSTOMER MESSAGE
========================= */

function addCustomerMessage(message) {

    const messages =
        document.getElementById("chatbotMessages");

    const customerMessage =
        document.createElement("div");

    customerMessage.className =
        "customer-message";


    customerMessage.innerHTML = `
        <div class="customer-message-content">
            ${escapeHtml(message)}
        </div>
    `;


    messages.appendChild(customerMessage);

}


/* =========================
   BOT MESSAGE
========================= */

function addBotMessage(message) {

    const messages =
        document.getElementById("chatbotMessages");

    const botMessage =
        document.createElement("div");

    botMessage.className =
        "bot-message";


    botMessage.innerHTML = `

        <div class="message-avatar">

            <img
                src="images/logo/logo.png"
                alt="JNIEL'S Assistant"
            >

        </div>

        <div class="message-content">

            ${message}

        </div>

    `;


    messages.appendChild(botMessage);

}


/* =========================
   CHATBOT BRAIN
========================= */

function getBotResponse(message) {

    const text =
        message.toLowerCase();


    /* GREETING */

    if (
        text.includes("hello") ||
        text.includes("hi") ||
        text.includes("hey") ||
        text.includes("good morning") ||
        text.includes("good afternoon") ||
        text.includes("good evening")
    ) {

        return `
            <p>Hello! 👋</p>

            <p>
                Welcome to JNIEL'S Body Massage & Spa.
                How can I help you today?
            </p>
        `;

    }


    /* SERVICES */

    if (
        text.includes("service") ||
        text.includes("massage") ||
        text.includes("offer") ||
        text.includes("available")
    ) {

        return `
            <p>
                💆 We offer different massage
                services at JNIEL'S Body Massage & Spa.
            </p>

            <p>
                You can view our complete list of
                available services and their prices
                on our website.
            </p>

            <p>
                Would you like to know more about
                a specific massage?
            </p>
        `;

    }


    /* PRICE */

    if (
        text.includes("price") ||
        text.includes("how much") ||
        text.includes("cost") ||
        text.includes("rate")
    ) {

        return `
            <p>
                💰 Our massage prices depend on
                the service and duration.
            </p>

            <p>
                You can check the available services
                on our website to see their current
                prices.
            </p>
        `;

    }


    /* BOOKING */

    if (
        text.includes("book") ||
        text.includes("booking") ||
        text.includes("appointment") ||
        text.includes("reserve") ||
        text.includes("reservation")
    ) {

        return `
            <p>
                📅 Want to book an appointment?
            </p>

            <p>
                Simply click
                <strong>Book an Appointment</strong>
                on our website.
            </p>

            <p>
                You may need to create an account
                or log in before making a reservation.
            </p>
        `;

    }


    /* OPENING HOURS */

    if (
        text.includes("open") ||
        text.includes("opening") ||
        text.includes("hours") ||
        text.includes("close") ||
        text.includes("schedule")
    ) {

        return `
            <p>
                🕐 Our spa opening hours will be
                displayed here.
            </p>

            <p>
                Please check our official spa
                information for the latest schedule.
            </p>
        `;

    }


    /* LOCATION */

    if (
        text.includes("where") ||
        text.includes("location") ||
        text.includes("address") ||
        text.includes("located")
    ) {

        return `
            <p>
                📍 You can find our spa location
                and address on our website.
            </p>
        `;

    }


    /* CONTACT */

    if (
        text.includes("contact") ||
        text.includes("phone") ||
        text.includes("call") ||
        text.includes("email")
    ) {

        return `
            <p>
                📞 You can contact JNIEL'S Body
                Massage & Spa through our official
                contact information.
            </p>
        `;

    }


    /* THANK YOU */

    if (
        text.includes("thank") ||
        text.includes("thanks")
    ) {

        return `
            <p>
                You're welcome! 😊
            </p>

            <p>
                Let me know if you have any other
                questions about JNIEL'S Body Massage
                & Spa.
            </p>
        `;

    }


    /* GOODBYE */

    if (
        text.includes("bye") ||
        text.includes("goodbye")
    ) {

        return `
            <p>
                Goodbye! 👋
            </p>

            <p>
                We hope to see you at JNIEL'S
                Body Massage & Spa soon.
            </p>
        `;

    }


    /* DEFAULT RESPONSE */

    return `
        <p>
            I'm sorry, I didn't quite understand
            your question. 😊
        </p>

        <p>
            You can ask me about:
        </p>

        <p>
            💆 Services<br>
            💰 Prices<br>
            📅 Appointments<br>
            🕐 Opening Hours<br>
            📍 Location<br>
            📞 Contact Information
        </p>
    `;

}


/* =========================
   ENTER KEY
========================= */

document
    .getElementById("chatbotInput")
    .addEventListener("keypress", function(event) {

        if (event.key === "Enter") {

            event.preventDefault();

            sendChatMessage();

        }

    });


/* =========================
   SECURITY
========================= */

function escapeHtml(text) {

    const div =
        document.createElement("div");

    div.textContent = text;

    return div.innerHTML;

}

document
    .querySelectorAll(".chatbot-quick-actions button")
    .forEach(function(button) {

        button.addEventListener("click", function() {

            const question =
                button.textContent.trim();

            document.getElementById("chatbotInput").value =
                question;

            sendChatMessage();

        });

    });