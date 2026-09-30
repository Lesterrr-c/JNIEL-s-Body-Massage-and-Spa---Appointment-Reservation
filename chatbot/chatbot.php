<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="stylesheet" href="../chatbot/chatbot.css">
</head>
<body>
    


<div class="chatbot-widget">

    <!-- CHAT WINDOW -->
    <div class="chatbot-window" id="chatbotWindow">

        <!-- HEADER -->
        <div class="chatbot-header">

            <div class="chatbot-brand">

                <div class="chatbot-logo">
                    <img
                        src="images/logo/logo.png"
                        alt="JNIEL'S"
                    >
                </div>

                <div class="chatbot-title">

                    <h3>JNIEL'S Assistant</h3>

                    <span>
                        <i></i>
                        Online
                    </span>

                </div>

            </div>

            <button
                type="button"
                class="chatbot-close"
                onclick="toggleChatbot()"
            >
                ×
            </button>

        </div>


        <!-- MESSAGES -->
        <div
            class="chatbot-messages"
            id="chatbotMessages"
        >

            <div class="bot-message">

                <div class="message-avatar">

                    <img
                        src="images/logo/logo.png"
                        alt="JNIEL'S Assistant"
                    >

                </div>

                <div class="message-content">

                    <p>Hello! 👋</p>

                    <p>
                        Welcome to JNIEL'S Body Massage & Spa.
                    </p>

                    <p>
                        How can I help you today?
                    </p>

                </div>

            </div>


            <!-- QUICK QUESTIONS -->

            <div class="chatbot-quick-actions">

                <button type="button">
                    💆 Our Services
                </button>

                <button type="button">
                    💰 Prices
                </button>

                <button type="button">
                    📅 How to Book
                </button>

                <button type="button">
                    🕐 Opening Hours
                </button>

            </div>

        </div>


        <!-- INPUT -->

        <div class="chatbot-input-area">

            <input
                type="text"
                id="chatbotInput"
                placeholder="Ask us anything..."
                autocomplete="off"
            >

            <button
                type="button"
                class="chatbot-send"
                onclick="sendChatMessage()"
            >
                ➤
            </button>

        </div>

    </div>


    <!-- FLOATING BUTTON -->

    <button
        type="button"
        class="chatbot-button"
        id="chatbotButton"
        onclick="toggleChatbot()"
        aria-label="Open JNIEL'S Assistant"
    >

        <span>💬</span>

    </button>

</div>


<link rel="stylesheet"href="chatbot/chatbot.css">

<script src="chatbot/chatbot.js"></script>

</body>
</html>