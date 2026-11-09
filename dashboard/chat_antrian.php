<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] != 'marketing') {
    die("Akses ditolak.");
}

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Antrian Chat Customer</h2>
    
    <div style="display: flex; gap: 2rem; margin-top: 2rem;">
        <div style="flex: 2;">
            <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem;">Antrian Baru</h3>
                <div id="queue-list" style="display: grid; gap: 1rem;">
                    <!-- Diisi via AJAX -->
                </div>
            </div>
        </div>
        
        <div style="flex: 1;">
            <div style="background: #fff; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem;">Chat Aktif Saya</h3>
                <div id="active-list" style="display: grid; gap: 1rem;">
                    <!-- Diisi via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF_TOKEN = '<?= csrf_token() ?>';

function fetchQueue() {
    fetch('api_chat_antrian.php')
        .then(res => res.json())
        .then(data => {
            const qList = document.getElementById('queue-list');
            qList.innerHTML = '';
            if(data.antrian.length === 0) {
                qList.innerHTML = '<p style="color: gray;">Tidak ada chat dalam antrian.</p>';
            } else {
                data.antrian.forEach(q => {
                    qList.innerHTML += `
                        <div style="padding: 1rem; border: 1px solid #eee; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>Customer #${q.customer_id}</strong><br>
                                <span style="font-size: 0.85rem; color: gray;">Sejak: ${q.waktu_mulai}</span>
                            </div>
                            <button class="btn" onclick="claimChat(${q.id})" style="padding: 0.5rem 1rem;">Terima Chat</button>
                        </div>
                    `;
                });
            }
            
            const aList = document.getElementById('active-list');
            aList.innerHTML = '';
            if(data.aktif.length === 0) {
                aList.innerHTML = '<p style="color: gray;">Belum ada chat aktif.</p>';
            } else {
                data.aktif.forEach(a => {
                    aList.innerHTML += `
                        <div style="padding: 1rem; border: 1px solid #eee; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <strong>Customer #${a.customer_id}</strong>
                            <a href="chat_aktif.php?id=${a.id}" class="btn" style="padding: 0.5rem 1rem; background-color: var(--color-slate-grey); color: #fff;">Buka</a>
                        </div>
                    `;
                });
            }
        });
}

function claimChat(roomId) {
    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('action', 'claim');
    formData.append('room_id', roomId);
    
    fetch('../api/chat_api.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        if(data.success) {
            window.location.href = 'chat_aktif.php?id=' + roomId;
        } else {
            alert(data.error);
            fetchQueue();
        }
    });
}

setInterval(fetchQueue, 3000);
fetchQueue();
</script>

<?php require_once '../includes/footer.php'; ?>
