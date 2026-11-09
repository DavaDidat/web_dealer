<?php
if (isset($_SESSION['user_id'])):
    $stmt_notif = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND dibaca = FALSE");
    $stmt_notif->execute([$_SESSION['user_id']]);
    $unread_count = $stmt_notif->fetchColumn();
?>
<div id="notifikasi-dropdown" style="position: relative; display: inline-block;">
    <button id="notif-toggle" onclick="toggleNotifikasi()" aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false" style="background: none; border: none; color: var(--color-warm-ivory); cursor: pointer; position: relative; padding: 0.5rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
        <?php if ($unread_count > 0): ?>
            <span id="notif-badge" style="position: absolute; top: 0; right: 0; background: #e53e3e; color: #fff; font-size: 0.7rem; padding: 0.1rem 0.4rem; border-radius: 10px;"><?= $unread_count ?></span>
        <?php endif; ?>
    </button>
    <div id="notifikasi-list" style="display: none; position: absolute; right: 0; top: 100%; background: #fff; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 320px; max-height: 400px; overflow-y: auto; z-index: 1000;">
        <div style="padding: 1rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <strong style="font-size: 0.9rem;">Notifikasi</strong>
            <a href="#" onclick="markAllRead(); return false;" style="font-size: 0.8rem;">Tandai dibaca</a>
        </div>
        <div id="notifikasi-items">
            <p style="padding: 1rem; color: var(--color-slate-grey); font-size: 0.85rem;">Memuat...</p>
        </div>
    </div>
</div>

<script>
const NOTIF_CSRF_TOKEN = '<?= csrf_token() ?>';

function toggleNotifikasi() {
    const list = document.getElementById('notifikasi-list');
    const toggle = document.getElementById('notif-toggle');
    if (list.style.display === 'none') {
        list.style.display = 'block';
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
        fetchNotifikasi();
    } else {
        list.style.display = 'none';
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }
}

function fetchNotifikasi() {
    fetch('/project_PTYangpentingMaju/api/notifikasi_api.php?action=fetch')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('notifikasi-items');
            if (data.notifikasi && data.notifikasi.length > 0) {
                container.innerHTML = data.notifikasi.map(n => `
                    <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #f0f0f0; font-size: 0.85rem; cursor: pointer;" onclick="markRead(${n.id})">
                        <p style="margin: 0; color: var(--color-charcoal-text);">${n.pesan}</p>
                        <p style="margin: 0.25rem 0 0; font-size: 0.75rem; color: var(--color-slate-grey);">${new Date(n.waktu).toLocaleString('id-ID')}</p>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p style="padding: 1rem; color: var(--color-slate-grey); font-size: 0.85rem;">Tidak ada notifikasi.</p>';
            }

            const badge = document.getElementById('notif-badge');
            if (data.unread > 0) {
                if (badge) badge.textContent = data.unread;
                else location.reload();
            } else if (badge) {
                badge.remove();
            }
        });
}

function markRead(id) {
    const formData = new FormData();
    formData.append('csrf_token', NOTIF_CSRF_TOKEN);
    formData.append('action', 'mark_read');
    formData.append('id', id);
    fetch('/project_PTYangpentingMaju/api/notifikasi_api.php', { method: 'POST', body: formData })
        .then(() => fetchNotifikasi());
}

function markAllRead() {
    const formData = new FormData();
    formData.append('csrf_token', NOTIF_CSRF_TOKEN);
    formData.append('action', 'mark_all_read');
    fetch('/project_PTYangpentingMaju/api/notifikasi_api.php', { method: 'POST', body: formData })
        .then(() => fetchNotifikasi());
}

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notifikasi-dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        document.getElementById('notifikasi-list').style.display = 'none';
    }
});

setInterval(fetchNotifikasi, 10000);
</script>
<?php endif; ?>
