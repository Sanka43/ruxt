# RuXt Next-Gen: cPanel Backend Setup & Deployment Guide

This guide explains how to run the full-stack RuXt Next-Gen (Fastify Backend + React 19 Frontend) on cPanel hosting.

---

## 1. What was Causing the `<` / HTML 404 Error?
Previously, the GitHub Actions FTP deployment only uploaded `./apps/web/dist/` (static HTML/CSS/JS) to cPanel `public_html/`. Because the Node.js Fastify backend (`apps/server`) was never uploaded or running on cPanel, Apache received all `/api/...` requests and returned an Apache 404 HTML page (`<!DOCTYPE HTML>... 404 Not Found`).

---

## 2. Automated Full-Stack Deployment
We updated `.github/workflows/deploy.yml` so every push to `main` automatically:
1. Builds both React 19 SPA (`npm run build:web`) and Fastify Server (`npm run build:server`).
2. Bundles the unified deployment folder (`.deploy/`) containing:
   - Static frontend assets (`index.html`, `assets/`)
   - Backend compiled server (`apps/server/dist/`)
   - cPanel startup file (`app.js`)
   - Dependency definitions (`package.json`, `package-lock.json`)
   - Optimized `.htaccess` with Passenger and Apache proxy routing.
3. Automatically deploys the full bundle to your cPanel `public_html/` via FTP!

---

## 3. Configuring cPanel "Setup Node.js App" (Step-by-Step)

Follow these simple steps in your cPanel dashboard:

### Step 1: Open "Setup Node.js App"
- Log in to your cPanel dashboard.
- In the **Software** section, click **Setup Node.js App**.

### Step 2: Create Application
Click **Create Application** and enter the following settings:
- **Node.js Version**: `20.x` (or `18.x`)
- **Application Mode**: `Production`
- **Application Root**: `public_html` (or your subdomain folder)
- **Application URL**: `yourdomain.com` (leave root or select your domain)
- **Application Startup File**: `app.js`

Click **Create**.

### Step 3: Install Production Dependencies
- Inside the Node.js App card in cPanel, click **Run NPM Install** (or click the copyable command for the virtual environment and run `npm install --omit=dev` in cPanel Terminal).

### Step 4: Configure Environment Variables (Optional)
In the **Environment Variables** section at the bottom of the Node.js app card, click **Add Variable**:
- `NODE_ENV`: `production`
- `ANTIGRAVITY_API_KEY`: *(Your Google AI Studio / Gemini API key - starts with AIzaSy...)*
- `GEMINI_API_KEY`: *(Optional fallback)*

### Step 5: Start / Restart the App
- Click **Restart** or **Start Application**.
- Now visit `https://yourdomain.com/api/health` in your browser. You should receive:
  ```json
  {"status":"ok","platform":"ruxt-next","version":"1.0.0", ...}
  ```
- All `/api/antigravity/connect`, `/api/chat`, and WebSocket `/ws` endpoints are now 100% active!

---

## 4. The 3 Antigravity Pro Connection Options

In `/auth/connect?provider=antigravity_pro`:

1. **🔑 Google Gemini / Antigravity API Key (Recommended & Easiest)**:
   - Get a free API key from [Google AI Studio](https://aistudio.google.com/app/apikey).
   - Paste it into the API Key field and click **Verify & Link API Key**.
   - RuXt validates the key live against Google's API and streams real DeepMind neural tokens chunk-by-chunk!

2. **🌐 Real Google OAuth 2.0 Sign-In**:
   - Click **Continue with Google Account**.
   - If using a custom Google Cloud Client ID, configure your Client ID in the toggle section with authorized JavaScript origin `https://yourdomain.com`.
   - Google identity is verified and displayed with your name and avatar.

3. **💻 Local Antigravity IDE Outbound Tunnel Bridge**:
   - For running local IDE tools (Antigravity IDE, Cursor, Claude CLI):
     ```bash
     npm run tunnel -- wss://yourdomain.com/ws
     ```
   - Connects outbound over secure WebSockets without opening inbound firewall ports.
