<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<style>
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
    }

    .notification {
        position: relative;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        margin-bottom: 15px;
        background: white;
        display: flex;
        transform: translateX(100%);
        animation: slideIn 0.3s forwards;
    }

    @keyframes slideIn {
        to { transform: translateX(0); }
    }

    .notification-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 60px;
        color: white;
        font-size: 1.8rem;
    }

    .notification-content {
        padding: 15px;
        flex-grow: 1;
    }

    .notification-title {
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 1.1rem;
    }

    .notification-message {
        font-size: 0.95rem;
        color: #444;
    }

    .notification-close {
        position: absolute;
        top: 8px;
        right: 8px;
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0;
        line-height: 1;
    }

    .notification-error .notification-icon {
        background-color: #dc3545;
    }

    .notification-success .notification-icon {
        background-color: #28a745;
    }
    .notification-notification .notification-icon {
        background-color: #e4c82bff;
    }

    .fade-out {
        animation: fadeOut 0.5s forwards;
    }

    @keyframes fadeOut {
        to { opacity: 0; transform: translateX(100%); }
    }
</style>

<div id="notification-container" class="notification-container"></div>

<script>
    // Проверка наличия сообщений при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($errorMessage)): ?>
            showNotification(TypeMessage.error, <?= json_encode($errorMessage) ?>);
        <?php endif; ?>
        
        <?php if (!empty($successMessage)): ?>
            showNotification(TypeMessage.success, <?= json_encode($successMessage) ?>);
        <?php endif; ?>

        <?php if (!empty($notificationMessage)): ?>
            showNotification(TypeMessage.notification, <?= json_encode($notificationMessage) ?>);
        <?php endif; ?>
    });    
</script>