let currentPhone = '08123456789';
const apiEndpoint = '../../src/api/diagnosa.php';

document.addEventListener('DOMContentLoaded', () => {
    const phoneInput = document.getElementById('phoneInput');
    const btnSetPhone = document.getElementById('btnSetPhone');

    currentPhone = phoneInput.value.trim() || '08123456789';

    btnSetPhone.addEventListener('click', () => {
        const val = phoneInput.value.trim();
        if (val) {
            currentPhone = val;
            document.getElementById('activePhoneDisplay').innerText = currentPhone;
            appendSystemNotice(`Sesi WhatsApp diperbarui ke nomor: ${currentPhone}`);
            sendQuickMessage('START');
        }
    });

    // Initial Start Message on page load
    sendQuickMessage('START');
});

function appendSystemNotice(text) {
    const chatContainer = document.getElementById('chatMessages');
    const notice = document.createElement('div');
    notice.style.textAlign = 'center';
    notice.style.fontSize = '0.75rem';
    notice.style.color = '#8696a0';
    notice.style.margin = '0.5rem 0';
    notice.innerText = `— ${text} —`;
    chatContainer.appendChild(notice);
    scrollToBottom();
}

async function handleChatSubmit(e) {
    e.preventDefault();
    const inputField = document.getElementById('userInput');
    const text = inputField.value.trim();

    if (!text) return;

    inputField.value = '';
    await sendMessage(text);
}

async function sendQuickMessage(text) {
    await sendMessage(text);
}

async function sendMessage(userText) {
    // Render User Message Bubble
    renderMessage(userText, 'user');

    // Show Bot Typing Indicator
    showTypingIndicator();

    try {
        const response = await fetch(apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                no_wa: currentPhone,
                user_input: userText
            })
        });

        const data = await response.json();

        // Remove Typing Indicator
        hideTypingIndicator();

        if (data.message) {
            renderMessage(data.message, 'bot', data);
            updateQuickReplies(data);
        } else {
            renderMessage('⚠️ Respons dari server tidak valid.', 'bot');
        }

    } catch (error) {
        hideTypingIndicator();
        console.error('API Error:', error);
        renderMessage('❌ Gagal terhubung ke API Server PHP. Pastikan Apache XAMPP aktif.', 'bot');
    }
}

function renderMessage(text, sender, data = null) {
    const chatContainer = document.getElementById('chatMessages');

    const row = document.createElement('div');
    row.className = `message-row ${sender}`;

    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    
    // Format text message
    let formattedText = escapeHtml(text);
    // Format Bold (*text*)
    formattedText = formattedText.replace(/\*(.*?)\*/g, '<strong>$1</strong>');
    // Format Italic (_text_)
    formattedText = formattedText.replace(/_(.*?)_/g, '<em>$1</em>');

    bubble.innerHTML = formattedText;

    // Timestamp
    const timeSpan = document.createElement('span');
    timeSpan.className = 'time-stamp';
    const now = new Date();
    timeSpan.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    bubble.appendChild(timeSpan);

    // If Conclusion & Ticket created, append Ticket Badge
    if (data && data.status === 'CONCLUSION' && data.no_tiket) {
        const ticketBadge = document.createElement('div');
        ticketBadge.className = 'ticket-badge';
        ticketBadge.innerHTML = `
            <i class="fa-solid fa-ticket"></i> <strong>Tiket Dibuat: #${data.no_tiket}</strong><br>
            <small>Tersimpan ke Dashboard CS Monitoring</small>
        `;
        bubble.appendChild(ticketBadge);
    }

    row.appendChild(bubble);
    chatContainer.appendChild(row);
    scrollToBottom();
}

function updateQuickReplies(data) {
    const container = document.getElementById('quickRepliesContainer');
    container.innerHTML = '';

    if (data.status === 'QUESTION') {
        if (data.step === 1) {
            addChip('1️⃣ 1. Internet Mati Total', '1');
            addChip('2️⃣ 2. Internet Lambat', '2');
        } else if (data.step === 2) {
            addChip('1️⃣ 1. Ya', '1');
            addChip('2️⃣ 2. Tidak', '2');
            addChip('3️⃣ 3. Perangkat Tertentu', '3');
        } else {
            addChip('1️⃣ 1. Ya', '1');
            addChip('2️⃣ 2. Tidak', '2');
        }
    } else if (data.status === 'CONCLUSION') {
        addChip('🔄 Diagnosa Ulang (START)', 'START');
    }

    addChip('❌ Reset Sesi', 'RESET');
}

function addChip(label, value) {
    const container = document.getElementById('quickRepliesContainer');
    const chip = document.createElement('button');
    chip.className = 'chip';
    chip.innerText = label;
    chip.onclick = () => sendQuickMessage(value);
    container.appendChild(chip);
}

function showTypingIndicator() {
    const chatContainer = document.getElementById('chatMessages');
    const existing = document.getElementById('typingIndicatorRow');
    if (existing) return;

    const row = document.createElement('div');
    row.className = 'message-row bot';
    row.id = 'typingIndicatorRow';

    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.innerHTML = `
        <div class="typing-indicator">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        </div>
    `;

    row.appendChild(bubble);
    chatContainer.appendChild(row);
    scrollToBottom();
}

function hideTypingIndicator() {
    const indicator = document.getElementById('typingIndicatorRow');
    if (indicator) indicator.remove();
}

function scrollToBottom() {
    const chatContainer = document.getElementById('chatMessages');
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function clearChatHistory() {
    const chatContainer = document.getElementById('chatMessages');
    chatContainer.innerHTML = '';
    appendSystemNotice('Tampilan chat dibersihkan');
}

function escapeHtml(text) {
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}
