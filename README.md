# RuXt - Private AI Personal Assistant

A modern, responsive, voice-enabled Private AI Assistant web application built with **PHP 8**, **Vanilla JavaScript**, **Tailwind CSS**, and **Google Gemini API**.

---

## 🚀 Features

- **Brain (Google Gemini API):** High-speed responses with universal multilingual intelligence that fluently mirrors any language or dialect you converse in.
- **Voice Capabilities:**
  - **Speech-to-Text (Voice Input):** Speak directly using the microphone in any language.
  - **Text-to-Speech (Voice Output):** Assistant can read out responses aloud.
- **Mobile PWA Support:**
  - Fully responsive mobile design.
  - Can be installed on Android & iOS as a native app via **"Add to Home Screen"**.
- **Tasks & Reminders Management:**
  - Built-in To-Do list that the assistant automatically knows about and can reference in conversations.
- **Secure User Authentication:**
  - Built-in **Login & Registration system** with encrypted password hashing (`BCRYPT`).
  - Session management with "Remember Me" option and full Sign Out capability.
  - Personalized assistant responses addressing the user by their registered name.
- **High Performance MySQL / MariaDB Database:**
  - Fast, reliable and scalable. Sessions, messages, notes, ventures, executives, and preferences are stored in MySQL (`private_assistance` database). (SQLite is also supported via `database/config.php`).
- **Customizable Assistant:**
  - Change Assistant Name, Your Name, and Custom System Instructions via Settings.

---

## 🛠️ How to Run

1. **Start Apache & MySQL:**
   - Open **XAMPP Control Panel** and click **Start** on both **Apache** and **MySQL**.
2. **Access via Browser:**
   - On PC: Open `http://localhost/PrivateAssistance/`
   - On Mobile: Open `http://<YOUR_PC_IP_ADDRESS>/PrivateAssistance/` in Chrome or Safari, and select **"Add to Home screen"** to install it as an app.
3. **Configure API Key:**
   - Click the **Settings & API** button on the bottom left.
   - Paste your free API key from [Google AI Studio](https://aistudio.google.com/app/apikey).
   - Click **Save Settings**.
