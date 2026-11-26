{{-- resources/views/admin/partials/browser_lock.blade.php --}}
<div id="knotelle-panel-guard-overlay" style="display:none;position:fixed;inset:0;background:rgba(28,25,23,0.96);backdrop-filter:blur(8px);z-index:99999999;align-items:center;justify-content:center;padding:24px;font-family:'Plus Jakarta Sans',sans-serif;">
    <div style="background:#ffffff;border-radius:24px;max-width:520px;width:100%;padding:36px;text-align:center;box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);border:1px solid #fee2e2;">
        <div style="width:72px;height:72px;border-radius:50%;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;font-size:32px;">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h3 style="font-size:22px;font-weight:800;color:#1c1917;margin-bottom:10px;">Browser Isolation Active</h3>
        <p style="font-size:14px;color:#78716c;line-height:1.6;margin-bottom:20px;">
            The <strong>KNOTELLE Storefront</strong> is currently active in another tab of this browser.
            To eliminate browser cache conflicts and protect your data, <strong>the Admin Panel and Storefront cannot run simultaneously in the same browser</strong>.
        </p>
        <div style="background:#fff1f2;border:1px solid #fecdd3;border-radius:14px;padding:16px;margin-bottom:24px;text-align:left;font-size:13px;color:#9f1239;line-height:1.6;">
            <strong>To proceed, please choose one:</strong><br>
            • <strong>Close all Knotelle storefront tabs</strong> in this browser, then click <em>Continue to Admin</em> below.<br>
            • <strong>OR</strong> open the Admin Panel in an <strong>Incognito Window</strong> or another browser (like <strong>Microsoft Edge</strong> or <strong>Firefox</strong>).
        </div>
        <button type="button" onclick="window.location.reload()" style="background:#dc2626;color:#ffffff;border:none;padding:12px 28px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 4px 12px rgba(220,38,38,0.25);transition:all 0.2s;">
            <i class="fas fa-redo-alt mr-2"></i> Continue to Admin
        </button>
    </div>
</div>

<script>
(function() {
    const overlay = document.getElementById('knotelle-panel-guard-overlay');
    if (!overlay) return;

    let channel = null;
    try {
        if ('BroadcastChannel' in window) {
            channel = new BroadcastChannel('knotelle_panel_guard');
        }
    } catch(e) {}

    function updateAdminHeartbeat() {
        try {
            localStorage.setItem('knotelle_admin_tab_heartbeat', Date.now().toString());
        } catch(e) {}
    }

    function isStorefrontOpen() {
        try {
            const raw = localStorage.getItem('knotelle_storefront_tab_heartbeat');
            if (raw) {
                const ts = parseInt(raw, 10);
                if (Date.now() - ts < 4500) {
                    return true;
                }
            }
        } catch(e) {}
        return false;
    }

    function checkConflict() {
        if (isStorefrontOpen()) {
            overlay.style.display = 'flex';
        } else {
            overlay.style.display = 'none';
        }
    }

    // Set initial heartbeat and check immediately
    updateAdminHeartbeat();
    checkConflict();

    // Regular heartbeat every 2 seconds
    const interval = setInterval(function() {
        updateAdminHeartbeat();
        checkConflict();
        if (channel) {
            try {
                channel.postMessage({ type: 'ADMIN_ACTIVE', ts: Date.now() });
            } catch(e) {}
        }
    }, 2000);

    // Listen on BroadcastChannel for real-time instant notification
    if (channel) {
        channel.onmessage = function(e) {
            if (e.data && e.data.type === 'STOREFRONT_ACTIVE') {
                overlay.style.display = 'flex';
            } else if (e.data && e.data.type === 'STOREFRONT_CLOSED') {
                checkConflict();
            }
        };
    }

    // Listen on storage events (fired across tabs in the same browser)
    window.addEventListener('storage', function(e) {
        if (e.key === 'knotelle_storefront_tab_heartbeat') {
            checkConflict();
        }
    });

    // When admin tab is closed, zero out heartbeat
    window.addEventListener('beforeunload', function() {
        try {
            localStorage.setItem('knotelle_admin_tab_heartbeat', '0');
            if (channel) channel.postMessage({ type: 'ADMIN_CLOSED', ts: Date.now() });
        } catch(e) {}
    });
})();
</script>
