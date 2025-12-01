<!-- PWA Meta Tags and Links -->
<link rel="manifest" href="/ReySystemDemo/manifest.json">
<meta name="theme-color" content="#1152d4">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="ReySystem">

<!-- PWA Icons -->
<link rel="icon" type="image/png" sizes="72x72" href="/ReySystemDemo/pwa-icons/icon-72x72.png">
<link rel="icon" type="image/png" sizes="96x96" href="/ReySystemDemo/pwa-icons/icon-96x96.png">
<link rel="icon" type="image/png" sizes="128x128" href="/ReySystemDemo/pwa-icons/icon-128x128.png">
<link rel="icon" type="image/png" sizes="192x192" href="/ReySystemDemo/pwa-icons/icon-192x192.png">
<link rel="icon" type="image/png" sizes="512x512" href="/ReySystemDemo/pwa-icons/icon-512x512.png">

<!-- Apple Touch Icons -->
<link rel="apple-touch-icon" sizes="72x72" href="/ReySystemDemo/pwa-icons/icon-72x72.png">
<link rel="apple-touch-icon" sizes="96x96" href="/ReySystemDemo/pwa-icons/icon-96x96.png">
<link rel="apple-touch-icon" sizes="128x128" href="/ReySystemDemo/pwa-icons/icon-128x128.png">
<link rel="apple-touch-icon" sizes="144x144" href="/ReySystemDemo/pwa-icons/icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="/ReySystemDemo/pwa-icons/icon-152x152.png">
<link rel="apple-touch-icon" sizes="192x192" href="/ReySystemDemo/pwa-icons/icon-192x192.png">
<link rel="apple-touch-icon" sizes="384x384" href="/ReySystemDemo/pwa-icons/icon-384x384.png">
<link rel="apple-touch-icon" sizes="512x512" href="/ReySystemDemo/pwa-icons/icon-512x512.png">

<!-- PWA Installer Script -->
<script src="/ReySystemDemo/pwa-installer.js" defer></script>

<!-- Install Button (optional - can be added to any page) -->
<style>
#pwa-install-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
    background: linear-gradient(135deg, #1152d4 0%, #0d3d9f 100%);
    color: white;
    padding: 12px 24px;
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(17, 82, 212, 0.4);
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

#pwa-install-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(17, 82, 212, 0.6);
}

#pwa-install-btn.hidden {
    display: none;
}
</style>

<button id="pwa-install-btn" class="hidden" onclick="installPWA()">
    <span class="material-symbols-outlined">download</span>
    Instalar App
</button>
