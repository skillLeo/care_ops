<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CareOps Teams Tab</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; padding: 16px; }
    .box { max-width: 720px; padding: 16px; border: 1px solid #ddd; border-radius: 12px; }
    textarea { width: 100%; min-height: 120px; }
    button { padding: 10px 14px; border-radius: 10px; border: 1px solid #ccc; cursor: pointer; }
    .muted { color: #666; }
    .ok { color: #0a7a2f; }
    .err { color: #b00020; }
    pre { background:#f6f6f6; padding:12px; border-radius:10px; overflow:auto; }
  </style>
</head>
<body>
  <div class="box">
    <h2 id="title">Signing you in…</h2>
    <p class="muted" id="subtitle">If this is opened in a normal browser, Teams SSO won't work. That's expected.</p>

    <div id="actions" style="display:none; margin-top:12px;">
      <h4>Dev fallback (browser testing)</h4>
      <p class="muted">Paste a token here to test your backend endpoint.</p>
      <textarea id="tokenBox" placeholder="paste jwt token here..."></textarea>
      <div style="margin-top:10px; display:flex; gap:10px;">
        <button id="sendBtn">Send token to backend</button>
      </div>
    </div>

    <pre id="log"></pre>
  </div>

  <!-- Teams JS SDK (works only inside Teams) -->
  <script src="https://res.cdn.office.net/teams-js/2.0.0/js/MicrosoftTeams.min.js"></script>

  <script>
    const titleEl = document.getElementById('title');
    const subEl = document.getElementById('subtitle');
    const logEl = document.getElementById('log');
    const actionsEl = document.getElementById('actions');
    const tokenBox = document.getElementById('tokenBox');
    const sendBtn = document.getElementById('sendBtn');

    function log(msg) { logEl.textContent += msg + "\n"; }

    async function postToken(token) {
      const res = await fetch('/api/auth/teams', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ token })
      });
      const data = await res.json().catch(() => ({}));
      log("Backend status: " + res.status);
      log("Backend response: " + JSON.stringify(data, null, 2));
    }

    sendBtn?.addEventListener('click', async () => {
      const t = (tokenBox.value || '').trim();
      if (!t) { log("No token pasted."); return; }
      await postToken(t);
    });

    // Try Teams SSO
    try {
      if (!window.microsoftTeams || !microsoftTeams.app) {
        // Not in Teams
        titleEl.textContent = "Not running inside Microsoft Teams";
        subEl.textContent = "Open this tab from Teams later. For now you can test backend using dev fallback.";
        actionsEl.style.display = 'block';
        log("Teams SDK not available (browser mode).");
      } else {
        microsoftTeams.app.initialize().then(() => {
          titleEl.textContent = "Signing you in…";
          log("Teams SDK initialized.");

          microsoftTeams.authentication.getAuthToken({
            successCallback: async (token) => {
              titleEl.textContent = "Welcome to CareOps – Teams integration initialized";
              subEl.className = "ok";
              subEl.textContent = "SSO token acquired. Sending to backend…";
              log("Token acquired (len=" + token.length + ")");
              await postToken(token);
            },
            failureCallback: (err) => {
              titleEl.textContent = "SSO failed";
              subEl.className = "err";
              subEl.textContent = "Error: " + err + " (contact IT)";
              log("getAuthToken error: " + err);
            }
          });
        });
      }
    } catch (e) {
      titleEl.textContent = "Error";
      subEl.className = "err";
      subEl.textContent = "Unexpected error (check console)";
      log("Exception: " + (e && e.message ? e.message : e));
      actionsEl.style.display = 'block';
    }
  </script>
</body>
</html>