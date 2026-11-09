<?php
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] != 'marketing') {
    die("Akses ditolak.");
}

$room_id = (int)($_GET['id'] ?? 0);
$staff_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM chat_room WHERE id = ? AND staff_id = ? AND status = 'aktif'");
$stmt->execute([$room_id, $staff_id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: chat_antrian.php");
    exit;
}

$stmt_customer = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
$stmt_customer->execute([$room['customer_id']]);
$customer_name = $stmt_customer->fetchColumn() ?? 'Customer';

require_once '../includes/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="max-width: 700px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); display: flex; flex-direction: column; height: 650px; overflow: hidden;">
        <div style="padding: 1rem 1.5rem; background: var(--color-graphite-dark); color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div class="chat-avatar chat-avatar-customer" style="width: 36px; height: 36px; font-size: 0.8rem;">
                    <?= strtoupper(substr($customer_name, 0, 1)) ?>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1rem; color: #fff;"><?= htmlspecialchars($customer_name) ?></h3>
                    <p style="margin: 0; font-size: 0.8rem;" id="chat-status"><span class="ypj-online"><span class="ypj-dot"></span>Online</span></p>
                </div>
            </div>
            <button onclick="endChat()" class="btn" style="background: #c53030; color: #fff; padding: 0.5rem 1rem; border-radius: 20px;">Akhiri Percakapan</button>
        </div>

        <div id="chat-box" style="flex-grow: 1; padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; background: #f8f9fa;">
        </div>

        <div id="typing-indicator" style="padding: 0.5rem 1.5rem; display: none; align-items: center; gap: 0.5rem; background: #f8f9fa;">
            <div class="chat-avatar chat-avatar-customer" style="width: 24px; height: 24px; font-size: 0.6rem;">
                <?= strtoupper(substr($customer_name, 0, 1)) ?>
            </div>
            <div style="background: #fff; padding: 0.5rem 1rem; border-radius: 12px; border-bottom-left-radius: 0; display: flex; gap: 4px;">
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>
        </div>

        <div style="padding: 1rem; border-top: 1px solid #eee; background: #fff;">
            <form id="chat-form" style="display: flex; gap: 0.5rem;">
                <input type="text" id="chat-input" placeholder="Ketik balasan Anda..." style="flex-grow: 1; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 24px; outline: none; font-size: 0.95rem;">
                <button type="submit" class="btn" id="chat-btn" style="border-radius: 24px; padding: 0.75rem 1.5rem; background: var(--color-momentum-orange);">Kirim</button>
            </form>
        </div>
    </div>
</div>

<script>
const roomId = <?= $room_id ?>;
const CSRF_TOKEN = '<?= csrf_token() ?>';
const myId = <?= $staff_id ?>;
const customerName = <?= json_encode($customer_name) ?>;
const chatBox = document.getElementById('chat-box');
const chatForm = document.getElementById('chat-form');
const chatInput = document.getElementById('chat-input');
const chatBtn = document.getElementById('chat-btn');
const statusText = document.getElementById('chat-status');
const typingIndicator = document.getElementById('typing-indicator');

let lastMessageId = 0;

function scrollToBottom() {
    chatBox.scrollTop = chatBox.scrollHeight;
}

function formatTime(waktu) {
    return new Date(waktu).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

const YPJ_CHECK_SINGLE = '<svg class="ypj-icon ypj-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
const YPJ_CHECK_DOUBLE = '<svg class="ypj-icon ypj-icon-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12l4 4L14 8"/><path d="M10 14l2 2L20 8"/></svg>';

function createMessageElement(msg) {
    const isMe = msg.pengirim_id == myId;
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
    time.textContent = formatTime(msg.waktu);
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

function fetchMessages() {
    fetch(`../api/chat_api.php?action=fetch&room_id=${roomId}&last_id=${lastMessageId}`)
        .then(res => res.json())
        .then(data => {
            if(data.status_room === 'selesai') {
                statusText.innerHTML = '<span style="color: var(--color-steel-grey);">Percakapan telah diakhiri.</span>';
                chatInput.disabled = true;
                chatBtn.disabled = true;
                typingIndicator.style.display = 'none';
                return;
            }

            if (data.is_typing) {
                typingIndicator.style.display = 'flex';
            } else {
                typingIndicator.style.display = 'none';
            }

            if(data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    const el = createMessageElement(msg);
                    chatBox.appendChild(el);
                    lastMessageId = msg.id;
                });
                scrollToBottom();
            }
        });
}

let typingTimeout;
chatInput.addEventListener('input', () => {
    clearTimeout(typingTimeout);

    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('action', 'typing');
    formData.append('room_id', roomId);
    formData.append('is_typing', 'true');
    fetch('../api/chat_api.php', { method: 'POST', body: formData });

    typingTimeout = setTimeout(() => {
        formData.set('is_typing', 'false');
        fetch('../api/chat_api.php', { method: 'POST', body: formData });
    }, 3000);
});

chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = chatInput.value.trim();
    if(!text) return;

    chatInput.value = '';
    clearTimeout(typingTimeout);

    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('action', 'send');
    formData.append('room_id', roomId);
    formData.append('pesan', text);

    fetch('../api/chat_api.php', {
        method: 'POST',
        body: formData
    }).then(() => fetchMessages());
});

function endChat() {
    if(confirm('Yakin ingin mengakhiri percakapan ini?')) {
        const formData = new FormData();
        formData.append('csrf_token', CSRF_TOKEN);
        formData.append('action', 'end');
        formData.append('room_id', roomId);

        fetch('../api/chat_api.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            window.location.href = 'chat_antrian.php';
        });
    }
}

setInterval(fetchMessages, 3000);
fetchMessages();
</script>

<?php require_once '../includes/footer.php'; ?>
