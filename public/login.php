<?php
session_start();

function parse_env(): array {
    if (!empty($_ENV) || !empty($_SERVER)) {
        return [
            'DISCORD_CLIENT_ID' => $_ENV['DISCORD_CLIENT_ID'] ?? $_SERVER['DISCORD_CLIENT_ID'] ?? '',
            'DISCORD_REDIRECT_URI' => $_ENV['DISCORD_REDIRECT_URI'] ?? $_SERVER['DISCORD_REDIRECT_URI'] ?? '',
        ];
    }
    
    $envFile = __DIR__ . '/../config/env.txt';
    if (!is_file($envFile)) return [];
    $vars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    return is_array($vars) ? $vars : [];
}

$env = parse_env();
$clientId = $env['DISCORD_CLIENT_ID'];
$redirectUri = $env['DISCORD_REDIRECT_URI'];

if (isset($_SESSION['discord_user'])) {
    header('Location: dashboard.php');
    exit;
}

$authUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'identify email'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Roblox Refresher</title>

  <style>
    :root{
      --bg0:#070A12; --bg1:#050712;
      --text:rgba(255,255,255,.92); --muted:rgba(255,255,255,.62);
      --card:rgba(255,255,255,.06); --card2:rgba(255,255,255,.045); --stroke:rgba(140,190,255,.14);
      --accent:#7CB6FF; --accent2:#A8CEFF;
      --shadow:0 26px 80px rgba(0,0,0,.62); --radius:18px;
      --discord:#5865F2;
    }
    *{box-sizing:border-box} html,body{height:100%}
    body{
      margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; color:var(--text);
      background:
        radial-gradient(900px 520px at 50% 26%, rgba(124,182,255,.10), transparent 60%),
        radial-gradient(700px 420px at 50% 42%, rgba(124,182,255,.06), transparent 58%),
        linear-gradient(180deg, var(--bg0), var(--bg1));
      display:grid; place-items:center; padding:28px 16px; min-height:100vh;
    }
    .wrap{width:min(520px,100%); text-align:center}
    
    .brand{display:flex; flex-direction:column; align-items:center; gap:10px; margin-bottom:32px}
    .brand-badge{
      width:64px;height:64px;border-radius:999px;display:grid;place-items:center;
      background:rgba(255,255,255,.05);border:1px solid rgba(140,190,255,.18);box-shadow:0 18px 50px rgba(0,0,0,.52)
    }
    .brand-badge svg{width:28px;height:28px;display:block;color:var(--accent)}
    
    h1{margin:0;font-weight:600;letter-spacing:-.02em;font-size:clamp(26px,4vw,34px);line-height:1.15}
    .sub{margin:8px 0 0;color:var(--muted);font-size:14px;line-height:1.5}

    .card{
      width:100%; border-radius:var(--radius);
      border:1px solid var(--stroke); background:linear-gradient(180deg,var(--card),var(--card2));
      box-shadow:var(--shadow); padding:32px; text-align:center; position:relative; overflow:hidden;
      backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);
    }
    .card::before{
      content:"";position:absolute;inset:-2px;
      background:radial-gradient(520px 180px at 40% 0%, rgba(124,182,255,.12), transparent 60%);
      opacity:.55;pointer-events:none
    }

    .card-content{position:relative;z-index:1}
    
    .login-icon{
      width:56px;height:56px;margin:0 auto 20px;
      background:linear-gradient(135deg, rgba(88,101,242,.2), rgba(88,101,242,.1));
      border:1px solid rgba(88,101,242,.3);border-radius:16px;
      display:grid;place-items:center;
    }
    .login-icon svg{width:28px;height:28px;color:var(--accent2)}

    .card-title{margin:0 0 8px;font-size:22px;font-weight:600;color:var(--text)}
    .card-desc{margin:0 0 28px;font-size:14px;color:var(--muted);line-height:1.5}

    .discord-btn{
      width:100%;height:54px;border-radius:14px;
      border:1px solid rgba(88,101,242,.35);
      background:linear-gradient(135deg, rgba(88,101,242,.45), rgba(88,101,242,.35));
      color:var(--text);font-weight:600;font-size:15px;cursor:pointer;
      display:flex;align-items:center;justify-content:center;gap:12px;
      box-shadow:0 16px 40px rgba(88,101,242,.25); text-decoration:none;
      transition:all .2s ease; position:relative; overflow:hidden;
    }
    .discord-btn::before{
      content:"";position:absolute;inset:0;
      background:linear-gradient(135deg, rgba(88,101,242,.15), transparent);
      opacity:0;transition:opacity .2s ease;
    }
    .discord-btn:hover{
      background:linear-gradient(135deg, rgba(88,101,242,.55), rgba(88,101,242,.45));
      border-color:rgba(88,101,242,.5);
      transform:translateY(-2px);
      box-shadow:0 20px 48px rgba(88,101,242,.35);
    }
    .discord-btn:hover::before{opacity:1}
    .discord-btn:active{transform:translateY(0)}
    .discord-btn svg{width:24px;height:24px;flex-shrink:0;position:relative;z-index:1}
    .discord-btn span{position:relative;z-index:1}

    .security-note{
      margin-top:24px;padding:16px;
      background:rgba(124,182,255,.08);border:1px solid rgba(124,182,255,.15);
      border-radius:12px;display:flex;align-items:flex-start;gap:12px;text-align:left;
    }
    .security-note svg{width:20px;height:20px;color:var(--accent);flex-shrink:0;margin-top:2px}
    .security-note-text{flex:1;font-size:13px;color:var(--muted);line-height:1.5}
    .security-note-text strong{color:var(--accent2);font-weight:600}

    .back-link{
      display:inline-flex;align-items:center;gap:8px;margin-top:24px;
      color:var(--muted);font-size:14px;text-decoration:none;transition:color .2s ease;
    }
    .back-link:hover{color:var(--accent2)}
    .back-link svg{width:16px;height:16px}

    .features-mini{
      margin-top:28px;padding-top:24px;border-top:1px solid rgba(255,255,255,.08);
      display:flex;justify-content:center;gap:24px;flex-wrap:wrap;
    }
    .feature-mini{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted)}
    .feature-mini svg{width:16px;height:16px;color:var(--accent);flex-shrink:0}

    @media (max-width: 480px) {
      .card{padding:24px 20px}
      .features-mini{flex-direction:column;gap:12px;align-items:flex-start}
    }
  </style>
</head>

<body>
  <main class="wrap">
    <header class="brand">
      <div class="brand-badge" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M12 2.5l7 3.6v6.2c0 5.1-3.1 9.1-7 10.7-3.9-1.6-7-5.6-7-10.7V6.1l7-3.6z" stroke="currentColor" stroke-width="1.6" />
          <path d="M12 6.2v13.6" stroke="currentColor" stroke-opacity="0.25" stroke-width="1.2" />
        </svg>
      </div>
      <h1>Roblox Refresher</h1>
      <p class="sub">Login to access the cookie refresher</p>
    </header>

    <section class="card" aria-label="Login panel">
      <div class="card-content">
        <div class="login-icon">
          <svg viewBox="0 0 71 55" fill="none">
            <path d="M60.1045 4.8978C55.5792 2.8214 50.7265 1.2916 45.6527 0.41542C45.5603 0.39851 45.468 0.440769 45.4204 0.525289C44.7963 1.6353 44.105 3.0834 43.6209 4.2216C38.1637 3.4046 32.7345 3.4046 27.3892 4.2216C26.905 3.0581 26.1886 1.6353 25.5617 0.525289C25.5141 0.443589 25.4218 0.40133 25.3294 0.41542C20.2584 1.2888 15.4057 2.8186 10.8776 4.8978C10.8384 4.9147 10.8048 4.9429 10.7825 4.9795C1.57795 18.7309 -0.943561 32.1443 0.293408 45.3914C0.299005 45.4562 0.335386 45.5182 0.385761 45.5576C6.45866 50.0174 12.3413 52.7249 18.1147 54.5195C18.2071 54.5477 18.305 54.5139 18.3638 54.4378C19.7295 52.5728 20.9469 50.6063 21.9907 48.5383C22.0523 48.4172 21.9935 48.2735 21.8676 48.2256C19.9366 47.4931 18.0979 46.6 16.3292 45.5858C16.1893 45.5041 16.1781 45.304 16.3068 45.2082C16.679 44.9293 17.0513 44.6391 17.4067 44.3461C17.471 44.2926 17.5606 44.2813 17.6362 44.3151C29.2558 49.6202 41.8354 49.6202 53.3179 44.3151C53.3935 44.2785 53.4831 44.2898 53.5502 44.3433C53.9057 44.6363 54.2779 44.9293 54.6529 45.2082C54.7816 45.304 54.7732 45.5041 54.6333 45.5858C52.8646 46.6197 51.0259 47.4931 49.0921 48.2228C48.9662 48.2707 48.9102 48.4172 48.9718 48.5383C50.038 50.6034 51.2554 52.5699 52.5959 54.435C52.6519 54.5139 52.7526 54.5477 52.845 54.5195C58.6464 52.7249 64.529 50.0174 70.6019 45.5576C70.6551 45.5182 70.6887 45.459 70.6943 45.3942C72.1747 30.0791 68.2147 16.7757 60.1968 4.9823C60.1772 4.9429 60.1437 4.9147 60.1045 4.8978ZM23.7259 37.3253C20.2276 37.3253 17.3451 34.1136 17.3451 30.1693C17.3451 26.225 20.1717 23.0133 23.7259 23.0133C27.308 23.0133 30.1626 26.2532 30.1066 30.1693C30.1066 34.1136 27.28 37.3253 23.7259 37.3253ZM47.3178 37.3253C43.8196 37.3253 40.9371 34.1136 40.9371 30.1693C40.9371 26.225 43.7636 23.0133 47.3178 23.0133C50.9 23.0133 53.7545 26.2532 53.6986 30.1693C53.6986 34.1136 50.9 37.3253 47.3178 37.3253Z" fill="currentColor"/>
          </svg>
        </div>

        <h2 class="card-title">Login with Discord</h2>
        <p class="card-desc">Authenticate securely using Discord OAuth2 to access the refresher tool</p>

        <a href="<?php echo htmlspecialchars($authUrl); ?>" class="discord-btn">
          <svg viewBox="0 0 71 55" fill="none">
            <path d="M60.1045 4.8978C55.5792 2.8214 50.7265 1.2916 45.6527 0.41542C45.5603 0.39851 45.468 0.440769 45.4204 0.525289C44.7963 1.6353 44.105 3.0834 43.6209 4.2216C38.1637 3.4046 32.7345 3.4046 27.3892 4.2216C26.905 3.0581 26.1886 1.6353 25.5617 0.525289C25.5141 0.443589 25.4218 0.40133 25.3294 0.41542C20.2584 1.2888 15.4057 2.8186 10.8776 4.8978C10.8384 4.9147 10.8048 4.9429 10.7825 4.9795C1.57795 18.7309 -0.943561 32.1443 0.293408 45.3914C0.299005 45.4562 0.335386 45.5182 0.385761 45.5576C6.45866 50.0174 12.3413 52.7249 18.1147 54.5195C18.2071 54.5477 18.305 54.5139 18.3638 54.4378C19.7295 52.5728 20.9469 50.6063 21.9907 48.5383C22.0523 48.4172 21.9935 48.2735 21.8676 48.2256C19.9366 47.4931 18.0979 46.6 16.3292 45.5858C16.1893 45.5041 16.1781 45.304 16.3068 45.2082C16.679 44.9293 17.0513 44.6391 17.4067 44.3461C17.471 44.2926 17.5606 44.2813 17.6362 44.3151C29.2558 49.6202 41.8354 49.6202 53.3179 44.3151C53.3935 44.2785 53.4831 44.2898 53.5502 44.3433C53.9057 44.6363 54.2779 44.9293 54.6529 45.2082C54.7816 45.304 54.7732 45.5041 54.6333 45.5858C52.8646 46.6197 51.0259 47.4931 49.0921 48.2228C48.9662 48.2707 48.9102 48.4172 48.9718 48.5383C50.038 50.6034 51.2554 52.5699 52.5959 54.435C52.6519 54.5139 52.7526 54.5477 52.845 54.5195C58.6464 52.7249 64.529 50.0174 70.6019 45.5576C70.6551 45.5182 70.6887 45.459 70.6943 45.3942C72.1747 30.0791 68.2147 16.7757 60.1968 4.9823C60.1772 4.9429 60.1437 4.9147 60.1045 4.8978ZM23.7259 37.3253C20.2276 37.3253 17.3451 34.1136 17.3451 30.1693C17.3451 26.225 20.1717 23.0133 23.7259 23.0133C27.308 23.0133 30.1626 26.2532 30.1066 30.1693C30.1066 34.1136 27.28 37.3253 23.7259 37.3253ZM47.3178 37.3253C43.8196 37.3253 40.9371 34.1136 40.9371 30.1693C40.9371 26.225 43.7636 23.0133 47.3178 23.0133C50.9 23.0133 53.7545 26.2532 53.6986 30.1693C53.6986 34.1136 50.9 37.3253 47.3178 37.3253Z" fill="currentColor"/>
          </svg>
          <span>Continue with Discord</span>
        </a>

        <div class="security-note">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div class="security-note-text">
            <strong>Secure OAuth2 Authentication</strong><br>
            We'll redirect you to Discord to authorize access. We only request your basic profile information (username, ID, email).
          </div>
        </div>

        <div class="features-mini">
          <div class="feature-mini">
            <svg viewBox="0 0 16 16" fill="currentColor">
              <path d="M8 0a8 8 0 110 16A8 8 0 018 0zm3.97 4.97a.75.75 0 00-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 00-1.06 1.06L6.97 11.03a.75.75 0 001.079-.02l3.992-4.99a.75.75 0 00-.01-1.05z"/>
            </svg>
            <span>No passwords stored</span>
          </div>
          <div class="feature-mini">
            <svg viewBox="0 0 16 16" fill="currentColor">
              <path d="M8 0a8 8 0 110 16A8 8 0 018 0zm3.97 4.97a.75.75 0 00-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 00-1.06 1.06L6.97 11.03a.75.75 0 001.079-.02l3.992-4.99a.75.75 0 00-.01-1.05z"/>
            </svg>
            <span>Open to everyone</span>
          </div>
          <div class="feature-mini">
            <svg viewBox="0 0 16 16" fill="currentColor">
              <path d="M8 0a8 8 0 110 16A8 8 0 018 0zm3.97 4.97a.75.75 0 00-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 00-1.06 1.06L6.97 11.03a.75.75 0 001.079-.02l3.992-4.99a.75.75 0 00-.01-1.05z"/>
            </svg>
            <span>Instant access</span>
          </div>
        </div>
      </div>
    </section>

    <a href="index.php" class="back-link">
      <svg viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/>
      </svg>
      Back to home
    </a>
  </main>
</body>
</html>
