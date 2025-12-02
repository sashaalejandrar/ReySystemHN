/**
 * Shared Functions for Chat and Forum
 * Emoji picker and file handling utilities
 */

// ===== EMOJI PICKER =====
const emojis = ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝️', '✋', '🤚', '🖐', '🖖', '👋', '🤝', '💪', '🙏', '✍️', '💅', '🤳', '💃', '🕺', '👯', '🧘', '🛀', '🛌', '👨‍💻', '👩‍💻', '🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '⚽', '🏀', '🏈', '⚾', '🎾', '🏐', '🏉', '🎱', '🏓', '🏸', '🥊', '🥋', '⛳', '⛸', '🎣', '🎿', '🛷', '🥌', '🎯', '🎮', '🕹', '🎲', '♠️', '♥️', '♦️', '♣️', '🃏', '🀄', '🎴', '🎭', '🎨', '🧵', '🧶', '🎼', '🎵', '🎶', '🎤', '🎧', '🎷', '🎺', '🎸', '🪕', '🎻', '🥁', '📱', '💻', '⌨️', '🖥', '🖨', '🖱', '🖲', '💾', '💿', '📀', '📷', '📹', '📺', '📻', '⏰', '⏱', '⏲', '⏳', '📡', '🔋', '🔌', '💡', '🔦', '🕯', '🧯', '🛢', '💸', '💵', '💴', '💶', '💷', '💰', '💳', '💎', '⚖️', '🔧', '🔨', '⚒', '🛠', '⛏', '🔩', '⚙️', '⛓', '🔫', '💣', '🔪', '🗡', '⚔️', '🛡', '🚬', '⚰️', '⚱️', '🏺', '🔮', '📿', '💈', '⚗️', '🔭', '🔬', '🕳', '💊', '💉', '🌡', '🚽', '🚰', '🚿', '🛁', '🛀', '🧴', '🧷', '🧹', '🧺', '🧻', '🧼', '🧽', '🧯', '🛒', '🚬', '⚰️', '⚱️', '🗿', '🏧', '🚮', '🚰', '♿', '🚹', '🚺', '🚻', '🚼', '🚾', '🛂', '🛃', '🛄', '🛅', '⚠️', '🚸', '⛔', '🚫', '🚳', '🚭', '🚯', '🚱', '🚷', '📵', '🔞', '☢️', '☣️'];

let currentEmojiContext = null;

function toggleEmojiPicker(context) {
    currentEmojiContext = context;
    const pickerId = context === 'chat' ? 'chat-emoji-picker' : 'foro-emoji-picker';
    const picker = document.getElementById(pickerId);

    if (!picker) return;

    if (picker.classList.contains('hidden')) {
        // Populate emojis if not already done
        const grid = picker.querySelector('.grid');
        if (grid && grid.children.length === 0) {
            emojis.forEach(emoji => {
                const btn = document.createElement('button');
                btn.textContent = emoji;
                btn.className = 'text-2xl hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1 transition';
                btn.onclick = () => insertEmoji(emoji, context);
                grid.appendChild(btn);
            });
        }
        picker.classList.remove('hidden');
    } else {
        picker.classList.add('hidden');
    }
}

function insertEmoji(emoji, context) {
    const inputId = context === 'chat' ? 'message-input' : 'post-content';
    const input = document.getElementById(inputId);

    if (!input) return;

    const cursorPos = input.selectionStart || input.value.length;
    const textBefore = input.value.substring(0, cursorPos);
    const textAfter = input.value.substring(cursorPos);
    input.value = textBefore + emoji + textAfter;
    input.focus();
    input.selectionStart = input.selectionEnd = cursorPos + emoji.length;

    // Close picker
    const pickerId = context === 'chat' ? 'chat-emoji-picker' : 'foro-emoji-picker';
    const picker = document.getElementById(pickerId);
    if (picker) picker.classList.add('hidden');
}

// ===== FILE HANDLING =====
let selectedChatFile = null;
let selectedForoImage = null;
let selectedForoFile = null;

function handleChatFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    selectedChatFile = file;
    const input = document.getElementById('message-input');
    const fileName = file.name;
    const fileSize = (file.size / 1024).toFixed(2);

    // Show file preview in input
    input.placeholder = `📎 ${fileName} (${fileSize} KB) - Presiona Enter para enviar`;
    input.style.borderColor = '#1152d4';

    // Show notification
    showNotification(`Archivo adjunto: ${fileName}`, 'info');
}

function handleForoImageSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    selectedForoImage = file;
    const textarea = document.getElementById('post-content');

    // Remove previous preview if exists
    const existingPreview = textarea.parentElement.querySelector('.image-preview');
    if (existingPreview) existingPreview.remove();

    // Show image preview
    const reader = new FileReader();
    reader.onload = function (e) {
        const preview = document.createElement('div');
        preview.className = 'image-preview mt-2 relative inline-block';
        preview.innerHTML = `
            <img src="${e.target.result}" class="max-h-32 rounded-lg border-2 border-primary">
            <button onclick="removeForoImage()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition">×</button>
        `;
        textarea.parentElement.insertBefore(preview, textarea.nextSibling);
    };
    reader.readAsDataURL(file);

    showNotification(`Imagen agregada: ${file.name}`, 'success');
}

function handleForoFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;

    selectedForoFile = file;
    const textarea = document.getElementById('post-content');
    const fileSize = (file.size / 1024).toFixed(2);

    textarea.placeholder = `📎 ${file.name} (${fileSize} KB) adjunto`;
    showNotification(`Archivo adjunto: ${file.name}`, 'info');
}

function removeForoImage() {
    selectedForoImage = null;
    const input = document.getElementById('foro-image-input');
    if (input) input.value = '';

    const preview = document.querySelector('.image-preview');
    if (preview) preview.remove();
}

function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };

    const notif = document.createElement('div');
    notif.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-[10000] transition-all`;
    notif.style.animation = 'slideInRight 0.3s ease-out';
    notif.textContent = message;
    document.body.appendChild(notif);

    setTimeout(() => {
        notif.style.opacity = '0';
        notif.style.transform = 'translateX(100%)';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Close emoji pickers when clicking outside
document.addEventListener('click', function (e) {
    const chatPicker = document.getElementById('chat-emoji-picker');
    const foroPicker = document.getElementById('foro-emoji-picker');

    if (chatPicker && !e.target.closest('#chat-emoji-picker') && !e.target.closest('[onclick*="toggleEmojiPicker"]')) {
        chatPicker.classList.add('hidden');
    }

    if (foroPicker && !e.target.closest('#foro-emoji-picker') && !e.target.closest('[onclick*="toggleEmojiPicker"]')) {
        foroPicker.classList.add('hidden');
    }
});

// Add slide animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
`;
document.head.appendChild(style);
