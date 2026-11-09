<?php
require_once '../config/database.php';

cek_role(['admin_servis']);

$admin_servis_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT r.*, u.nama as nama_customer
    FROM chat_room r
    JOIN users u ON r.customer_id = u.id
    WHERE r.role_staff = 'admin_servis' AND r.status IN ('antri', 'aktif')
    ORDER BY r.waktu_mulai ASC
");
$stmt->execute();
$rooms = $stmt->fetchAll();

$room_detail = null;
if (isset($_GET['room_id'])) {
    $room_id = (int)$_GET['room_id'];
    $stmt_detail = $pdo->prepare("
        SELECT r.*, u.nama as nama_customer, u.email as email_customer
        FROM chat_room r
        JOIN users u ON r.customer_id = u.id
        WHERE r.id = ? AND r.role_staff = 'admin_servis'
    ");
    $stmt_detail->execute([$room_id]);
    $room_detail = $stmt_detail->fetch();

    if ($room_detail && $room_detail['status'] == 'antri') {
        $stmt_claim = $pdo->prepare("UPDATE chat_room SET staff_id = ?, status = 'aktif' WHERE id = ? AND status = 'antri'");
        $stmt_claim->execute([$admin_servis_id, $room_id]);
        $room_detail['status'] = 'aktif';
        $room_detail['staff_id'] = $admin_servis_id;
    }
}

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2>Chat Servis</h2>

    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem; margin-top: 2rem;">
        <div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden;">
            <div style="padding: 1rem 1.5rem; background: var(--color-graphite-dark); color: #fff;">
                <h3 style="margin: 0; font-size: 1rem;">Antrian & Chat Aktif</h3>
            </div>
            <div style="max-height: 500px; overflow-y: auto;">
                <?php if (empty($rooms)): ?>
                    <p style="padding: 1rem; color: var(--color-steel-grey); font-size: 0.9rem;">Tidak ada chat.</p>
                <?php else: ?>
                    <?php foreach ($rooms as $r): ?>
                        <a href="?room_id=<?= $r['id'] ?>" style="display: block; padding: 1rem 1.5rem; border-bottom: 1px solid #f0f0f0; text-decoration: none; color: inherit; <?= ($room_detail && $room_detail['id'] == $r['id']) ? 'background: #f0f7ff;' : '' ?>">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <strong style="font-size: 0.9rem;"><?= htmlspecialchars($r['nama_customer'] ?? 'Customer') ?></strong>
                                <span style="display: inline-block; padding: 0.15rem 0.5rem; background: <?= $r['status'] == 'aktif' ? '#dcfce3' : '#fef3c7' ?>; font-size: 0.75rem; border-radius: 4px;">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </div>
                            <p style="font-size: 0.8rem; color: var(--color-steel-grey); margin: 0.25rem 0 0;">
                                <?= date('d M H:i', strtotime($r['waktu_mulai'])) ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); display: flex; flex-direction: column; height: 550px;">
            <?php if ($room_detail): ?>
                <div style="padding: 1rem 1.5rem; background: var(--color-graphite-dark); color: #fff; display: flex; align-items: center; gap: 0.75rem;">
                    <div class="chat-avatar chat-avatar-customer" style="width: 36px; height: 36px; font-size: 0.8rem;">
                        <?= strtoupper(substr($room_detail['nama_customer'], 0, 1)) ?>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 1rem; color: #fff;"><?= htmlspecialchars($room_detail['nama_customer']) ?></h3>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--color-steel-grey);"><?= htmlspecialchars($room_detail['email_customer']) ?></p>
                    </div>
                </div>

                <div id="chat-box-servis" style="flex-grow: 1; padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; background: #f8f9fa;">
                </div>

                <div id="typing-indicator-servis" style="padding: 0.5rem 1.5rem; display: none; align-items: center; gap: 0.5rem; background: #f8f9fa;">
                    <div class="chat-avatar chat-avatar-customer" style="width: 24px; height: 24px; font-size: 0.6rem;">
                        <?= strtoupper(substr($room_detail['nama_customer'], 0, 1)) ?>
                    </div>
                    <div style="background: #fff; padding: 0.5rem 1rem; border-radius: 12px; border-bottom-left-radius: 0; display: flex; gap: 4px;">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>

                <?php if ($room_detail['status'] == 'aktif'): ?>
                    <div style="padding: 1rem; border-top: 1px solid #eee; background: #fff;">
                        <form id="chat-form-servis" style="display: flex; gap: 0.5rem;">
                            <input type="text" id="chat-input-servis" placeholder="Ketik pesan..." style="flex-grow: 1; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 24px; outline: none; font-size: 0.95rem;">
                            <button type="submit" class="btn" style="border-radius: 24px; padding: 0.75rem 1.5rem; background: var(--color-momentum-orange);">Kirim</button>
                        </form>
                        <button onclick="endChatServis()" style="margin-top: 0.5rem; padding: 0.5rem 1rem; background: #fee2e2; color: #991b1b; border: 1px solid #f87171; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">Akhiri Percakapan</button>
                    </div>
                <?php elseif ($room_detail['status'] == 'antri'): ?>
                    <div style="padding: 1rem; border-top: 1px solid #eee; background: #fff; text-align: center;">
                        <p style="color: var(--color-steel-grey);">Menunggu Anda mengklaim chat ini...</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="flex-grow: 1; display: flex; align-items: center; justify-content: center; color: var(--color-steel-grey);">
                    <p>Pilih chat dari daftar di sebelah kiri.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($room_detail): ?>
<script>
const roomIdServis = <?= $room_detail['id'] ?>;
const CSRF_TOKEN = '<?= csrf_token() ?>';
const myIdServis = <?= $admin_servis_id ?>;
const customerName = <?= json_encode($room_detail['nama_customer']) ?>;
const chatBoxServis = document.getElementById('chat-box-servis');
const chatFormServis = document.getElementById('chat-form-servis');
const chatInputServis = document.getElementById('chat-input-servis');
const typingIndicatorServis = document.getElementById('typing-indicator-servis');

let lastMessageIdServis = 0;

function scrollToBottomServis() {
    chatBoxServis.scrollTop = chatBoxServis.scrollHeight;
}

function formatTimeServis(waktu) {
    return new Date(waktu).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

const YPJ_CHECK_SINGLE = '<svg class="ypj-icon ypj-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
const YPJ_CHECK_DOUBLE = '<svg class="ypj-icon ypj-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12l4 4L14 8"/><path d="M10 14l2 2L20 8"/></svg>';

function createMessageElementServis(msg) {
    const isMe = msg.pengirim_id == myIdServis;
    const wrapper = document.createElement('div');
    wrapper.style.display = 'flex';
    wrapper.style.alignItems = 'flex-end';
    wrapper.style.gap = '0.5rem';
    wrapper.style.justifyContent = isMe ? 'flex-end' : 'flex-start';
    wrapper.className = 'chat-bubble';

    if (!isMe) {
        const avatar = document.createElement('div');
        avatar.className = 'chat-avatar chat-avatar-customer';
        avatar.style.width = '32px';
        avatar.style.height = '32px';
        avatar.style.fontSize = '0.75rem';
        avatar.textContent = customerName.charAt(0).toUpperCase();
        wrapper.appendChild(avatar);
    }

    const contentDiv = document.createElement('div');
    contentDiv.style.display = 'flex';
    contentDiv.style.flexDirection = 'column';
    contentDiv.style.alignItems = isMe ? 'flex-end' : 'flex-start';
    contentDiv.style.maxWidth = '70%';

    const bubble = document.createElement('div');
    bubble.style.padding = '0.6rem 1rem';
    bubble.style.borderRadius = '16px';
    bubble.style.lineHeight = '1.4';
    bubble.style.fontSize = '0.95rem';
    bubble.style.wordBreak = 'break-word';

    if (isMe) {
        bubble.style.background = 'var(--color-momentum-orange)';
        bubble.style.color = '#fff';
        bubble.style.borderBottomRightRadius = '4px';
    } else {
        bubble.style.background = '#fff';
        bubble.style.color = 'var(--color-charcoal-text)';
        bubble.style.border = '1px solid #e2e8f0';
        bubble.style.borderBottomLeftRadius = '4px';
    }

    bubble.textContent = msg.isi_pesan;
    contentDiv.appendChild(bubble);

    const meta = document.createElement('div');
    meta.style.display = 'flex';
    meta.style.alignItems = 'center';
    meta.style.gap = '0.35rem';
    meta.style.marginTop = '0.2rem';
    meta.style.justifyContent = isMe ? 'flex-end' : 'flex-start';

    const time = document.createElement('span');
    time.style.fontSize = '0.7rem';
    time.style.color = 'var(--color-steel-grey)';
    time.textContent = formatTimeServis(msg.waktu);
    meta.appendChild(time);

    if (isMe) {
        const receipt = document.createElement('span');
        receipt.className = 'read-receipt' + (msg.status_baca ? ' read' : '');
        receipt.style.fontSize = '0.7rem';
        receipt.innerHTML = msg.status_baca ? YPJ_CHECK_DOUBLE : YPJ_CHECK_SINGLE;
        meta.appendChild(receipt);
    }

    contentDiv.appendChild(meta);
    wrapper.appendChild(contentDiv);
    return wrapper;
}

function fetchMessagesServis() {
    fetch(`../api/chat_api.php?action=fetch&room_id=${roomIdServis}&last_id=${lastMessageIdServis}`)
        .then(res => res.json())
        .then(data => {
            if (data.status_room === 'selesai') {
                chatInputServis.disabled = true;
                chatFormServis.querySelector('button').disabled = true;
                typingIndicatorServis.style.display = 'none';
                return;
            }

            if (data.is_typing) {
                typingIndicatorServis.style.display = 'flex';
            } else {
                typingIndicatorServis.style.display = 'none';
            }

            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const el = createMessageElementServis(msg);
                    chatBoxServis.appendChild(el);
                    lastMessageIdServis = msg.id;
                });
                scrollToBottomServis();
            }
        });
}

let typingTimeoutServis;
chatInputServis.addEventListener('input', () => {
    clearTimeout(typingTimeoutServis);

    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('action', 'typing');
    formData.append('room_id', roomIdServis);
    formData.append('is_typing', 'true');
    fetch('../api/chat_api.php', { method: 'POST', body: formData });

    typingTimeoutServis = setTimeout(() => {
        formData.set('is_typing', 'false');
        fetch('../api/chat_api.php', { method: 'POST', body: formData });
    }, 3000);
});

chatFormServis.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = chatInputServis.value.trim();
    if (!text) return;

    chatInputServis.value = '';
    clearTimeout(typingTimeoutServis);

    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('action', 'send');
    formData.append('room_id', roomIdServis);
    formData.append('pesan', text);

    fetch('../api/chat_api.php', {
        method: 'POST',
        body: formData
    }).then(() => fetchMessagesServis());
});

function endChatServis() {
    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('action', 'end');
    formData.append('room_id', roomIdServis);

    fetch('../api/chat_api.php', {
        method: 'POST',
        body: formData
    }).then(() => {
        location.reload();
    });
}

setInterval(fetchMessagesServis, 3000);
fetchMessagesServis();
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
