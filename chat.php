<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_nama'] !== 'customer') {
    header("Location: auth/login.php");
    exit;
}

$customer_id = $_SESSION['user_id'];
$staff_roles = ['marketing', 'admin_servis'];

$channel = $_GET['channel'] ?? '';
$start = isset($_GET['start']) && $_GET['start'] !== '0';

// Mulai percakapan secara eksplisit (mis. tombol "Chat Marketing" di detail produk).
if ($start && in_array($channel, $staff_roles, true)) {
    $stmt = $pdo->prepare("SELECT id FROM chat_room WHERE customer_id = ? AND role_staff = ? AND status IN ('antri', 'aktif') LIMIT 1");
    $stmt->execute([$customer_id, $channel]);
    $rid = $stmt->fetchColumn();

    if (!$rid) {
        $ins = $pdo->prepare("INSERT INTO chat_room (customer_id, role_staff, status) VALUES (?, ?, 'antri')");
        $ins->execute([$customer_id, $channel]);
        notifikasi_ke_role($channel, $channel === 'marketing' ? 'Chat baru masuk antrian.' : 'Chat servis baru masuk antrian.');
        $rid = $pdo->lastInsertId();
    }

    header("Location: chat.php?room_id=" . (int)$rid);
    exit;
}

// Tutup room aktif yang sudah lama idle agar daftar mencerminkan status sebenarnya.
$pdo->exec("UPDATE chat_room SET status = 'selesai', waktu_selesai = NOW() WHERE status = 'aktif' AND waktu_aktivitas < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

// Ambil seluruh percakapan customer (aktif/antri lebih dulu, lalu riwayat selesai).
$stmt = $pdo->prepare("
    SELECT cr.id, cr.role_staff, cr.status, cr.staff_id, cr.waktu_mulai, cr.waktu_selesai, u.nama AS staff_nama
    FROM chat_room cr
    LEFT JOIN users u ON cr.staff_id = u.id
    WHERE cr.customer_id = ?
    ORDER BY cr.waktu_mulai DESC
");
$stmt->execute([$customer_id]);
$all_rooms = $stmt->fetchAll();

$stmt_last = $pdo->prepare("SELECT isi_pesan, waktu FROM chat_pesan WHERE chat_room_id = ? ORDER BY id DESC LIMIT 1");
$stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM chat_pesan WHERE chat_room_id = ? AND pengirim_id <> ? AND status_baca = 0");

$active_rooms = [];
$history_rooms = [];
foreach ($all_rooms as $r) {
    $stmt_last->execute([$r['id']]);
    $last = $stmt_last->fetch();
    $r['last_msg'] = $last['isi_pesan'] ?? '';
    $r['last_time'] = $last['waktu'] ?? $r['waktu_mulai'];

    $stmt_unread->execute([$r['id'], $customer_id]);
    $r['unread'] = (int)$stmt_unread->fetchColumn();

    if ($r['status'] === 'selesai') {
        $history_rooms[] = $r;
    } else {
        $active_rooms[] = $r;
    }
}
$history_rooms = array_slice($history_rooms, 0, 5);
$rooms = array_merge($active_rooms, $history_rooms);

$channel_label = function ($role_staff) {
    return $role_staff === 'admin_servis' ? 'Admin Servis' : 'Marketing';
};

// Room yang sedang dibuka.
$selected = null;
$sel_id = (int)($_GET['room_id'] ?? 0);
if ($sel_id) {
    foreach ($rooms as $r) {
        if ($r['id'] === $sel_id) {
            $selected = $r;
            break;
        }
    }
}
if (!$selected && in_array($channel, $staff_roles, true)) {
    foreach ($rooms as $r) {
        if ($r['role_staff'] === $channel && $r['status'] !== 'selesai') {
            $selected = $r;
            break;
        }
    }
}
if (!$selected) {
    foreach ($rooms as $r) {
        if ($r['status'] !== 'selesai') {
            $selected = $r;
            break;
        }
    }
}
if (!$selected && $rooms) {
    $selected = $rooms[0];
}

// Kanal yang belum punya percakapan aktif -> tawarkan tombol mulai.
$active_channels = [];
foreach ($active_rooms as $r) {
    $active_channels[$r['role_staff']] = true;
}
$can_start = [];
if (empty($active_channels['marketing'])) {
    $can_start[] = ['marketing', 'Mulai chat Marketing'];
}
if (empty($active_channels['admin_servis'])) {
    $can_start[] = ['admin_servis', 'Mulai chat Servis'];
}

$sel_label = $selected ? $channel_label($selected['role_staff']) : '';
$sel_staff = $selected ? ($selected['staff_nama'] ?: $sel_label) : '';

require_once 'includes/header.php';
?>

<div class="container" style="margin-top: 2rem; padding-bottom: 2.5rem;">
    <div class="page-head">
        <h2>Chat</h2>
    </div>

    <div class="chat-inbox<?= $selected ? ' has-selection' : '' ?>">
        <aside class="chat-list">
            <?php if (empty($rooms)): ?>
                <p class="chat-list-empty">Belum ada percakapan. Pilih salah satu untuk memulai.</p>
            <?php else: ?>
                <?php foreach ($rooms as $r): ?>
                    <?php
                        $label = $channel_label($r['role_staff']);
                        $preview = $r['last_msg'] !== '' ? $r['last_msg'] : ($r['status'] === 'antri' ? 'Menunggu balasan...' : 'Belum ada pesan');
                        if ($r['staff_nama']) {
                            $preview = $r['staff_nama'] . ': ' . $preview;
                        }
                    ?>
                    <a href="chat.php?room_id=<?= (int)$r['id'] ?>"
                       class="chat-conv<?= ($selected && $selected['id'] === $r['id']) ? ' active' : '' ?><?= $r['status'] === 'selesai' ? ' closed' : '' ?>">
                        <div class="chat-conv-avatar <?= $r['role_staff'] === 'admin_servis' ? 'servis' : 'mkt' ?>">
                            <?= strtoupper(substr($label, 0, 1)) ?>
                        </div>
                        <div class="chat-conv-main">
                            <div class="chat-conv-top">
                                <strong><?= htmlspecialchars($label) ?></strong>
                                <span class="chat-conv-time"><?= date('d M H:i', strtotime($r['last_time'])) ?></span>
                            </div>
                            <div class="chat-conv-sub">
                                <span class="chat-conv-preview"><?= htmlspecialchars($preview) ?></span>
                                <?php if ($r['unread'] > 0): ?>
                                    <span class="chat-unread"><?= (int)$r['unread'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($can_start): ?>
                <div class="chat-start">
                    <?php foreach ($can_start as $c): ?>
                        <a href="chat.php?channel=<?= $c[0] ?>&start=1" class="btn-secondary" style="text-align: center; text-decoration: none;"><?= htmlspecialchars($c[1]) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>

        <section class="chat-panel">
            <?php if ($selected): ?>
                <div class="chat-panel-head">
                    <a href="chat.php" class="chat-back" aria-label="Kembali ke daftar chat"><?= ypj_icon('arrow-right') ?></a>
                    <div class="chat-avatar <?= $selected['role_staff'] === 'admin_servis' ? 'chat-avatar-customer' : 'chat-avatar-staff' ?>" style="width: 36px; height: 36px; font-size: 0.8rem;">
                        <?= strtoupper(substr($sel_label, 0, 1)) ?>
                    </div>
                    <div>
                        <h3><?= htmlspecialchars($sel_label) ?></h3>
                        <p id="chat-status" style="margin: 0; font-size: 0.8rem;">
                            <?php if ($selected['status'] === 'antri'): ?>
                                <span style="color: var(--brand-orange-light);">Menunggu <?= strtolower($sel_label) ?>...</span>
                            <?php elseif ($selected['status'] === 'aktif'): ?>
                                <span class="ypj-online" style="color: #8fe3b0;"><span class="ypj-dot" style="background:#8fe3b0;"></span>Online &middot; <?= htmlspecialchars($sel_staff) ?></span>
                            <?php else: ?>
                                <span style="color: var(--auto-text-dim);">Percakapan telah diakhiri.</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div id="chat-box" style="flex-grow: 1; padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; background: #f8f9fa;"></div>

                <div id="typing-indicator" style="padding: 0.5rem 1.5rem; display: none; align-items: center; gap: 0.5rem; background: #f8f9fa;">
                    <div class="chat-avatar chat-avatar-staff" style="width: 24px; height: 24px; font-size: 0.6rem;"><?= strtoupper(substr($sel_label, 0, 1)) ?></div>
                    <div style="background: #fff; padding: 0.5rem 1rem; border-radius: 12px; border-bottom-left-radius: 0; display: flex; gap: 4px;">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>

                <div style="padding: 1rem; border-top: 1px solid #eee; background: #fff;">
                    <form id="chat-form" style="display: flex; gap: 0.5rem;">
                        <input type="text" id="chat-input" placeholder="Ketik pesan Anda..." style="flex-grow: 1; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 24px; outline: none; font-size: 0.95rem;" <?= $selected['status'] === 'antri' ? 'disabled' : '' ?>>
                        <button type="submit" id="chat-btn" class="btn" style="border-radius: 24px; padding: 0.75rem 1.5rem;" <?= $selected['status'] === 'antri' ? 'disabled' : '' ?>>Kirim</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="chat-panel-empty">
                    <p style="margin: 0;">Pilih percakapan di samping,<br>atau mulai obrolan baru dengan tim kami.</p>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: center;">
                        <a href="chat.php?channel=marketing&start=1" class="auto-btn-primary">Mulai chat Marketing</a>
                        <a href="chat.php?channel=admin_servis&start=1" class="btn-secondary" style="text-decoration: none;">Mulai chat Servis</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php if ($selected): ?>
<script>
const roomId = <?= (int)$selected['id'] ?>;
const CSRF_TOKEN = '<?= csrf_token() ?>';
const myId = <?= (int)$customer_id ?>;
const otherName = <?= json_encode($sel_staff) ?>;
const channelLabel = <?= json_encode($sel_label) ?>;
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
        avatar.className = 'chat-avatar chat-avatar-staff';
        avatar.style.width = '32px';
        avatar.style.height = '32px';
        avatar.style.fontSize = '0.75rem';
        avatar.textContent = (otherName || channelLabel).charAt(0).toUpperCase();
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
        bubble.style.background = 'var(--brand-orange)';
        bubble.style.color = 'var(--color-graphite-black)';
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
    fetch(`api/chat_api.php?action=fetch&room_id=${roomId}&last_id=${lastMessageId}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) return;

            if (data.status_room === 'selesai') {
                statusText.innerHTML = '<span style="color: var(--auto-text-dim);">Percakapan telah diakhiri.</span>';
                chatInput.disabled = true;
                chatBtn.disabled = true;
                typingIndicator.style.display = 'none';
                return;
            }
            if (data.status_room === 'aktif') {
                statusText.innerHTML = '<span class="ypj-online" style="color:#8fe3b0;"><span class="ypj-dot" style="background:#8fe3b0;"></span>Online &middot; ' + otherName + '</span>';
                chatInput.disabled = false;
                chatBtn.disabled = false;
            }

            typingIndicator.style.display = data.is_typing ? 'flex' : 'none';

            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    chatBox.appendChild(createMessageElement(msg));
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
    fetch('api/chat_api.php', { method: 'POST', body: formData });
    typingTimeout = setTimeout(() => {
        formData.set('is_typing', 'false');
        fetch('api/chat_api.php', { method: 'POST', body: formData });
    }, 3000);
});

chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const text = chatInput.value.trim();
    if (!text) return;
    chatInput.value = '';
    clearTimeout(typingTimeout);

    const formData = new FormData();
    formData.append('csrf_token', CSRF_TOKEN);
    formData.append('action', 'send');
    formData.append('room_id', roomId);
    formData.append('pesan', text);
    fetch('api/chat_api.php', { method: 'POST', body: formData }).then(() => fetchMessages());
});

// Unread room yang sedang dibuka sudah ditandai terbaca oleh polling pertama.
const activeBadge = document.querySelector('.chat-conv.active .chat-unread');
if (activeBadge) activeBadge.remove();

setInterval(fetchMessages, 3000);
fetchMessages();
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
