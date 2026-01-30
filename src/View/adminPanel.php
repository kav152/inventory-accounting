<?php
date_default_timezone_set('Europe/Moscow');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/adminPanel.log');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Repositories/GenericRepository.php';
/*require_once __DIR__ . '/../Repositories/UserRepository.php';*/
require_once __DIR__ . '/../BusinessLogic/UserController.php';
require_once __DIR__ . '/../Database/DatabaseFactory.php';

session_start();

DatabaseFactory::setConfig();
$userController = new UserController();
$users = $userController->getUsers();

$errorMessage = '';
$successMessage = '';
$importResults = null;

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/styles/table.css">

    <style>
        :root {
            --base-clr: #f8f9fa;
            --line-clr: #42434a;
            --hover-clr: #f0f0f0;
            --text-clr: #222533;
            --accent-clr: #db3f39;
            --secondary-text-clr: #b0b3c1;
            --select-row-clr: #1f93f1;
        }

        /* Стили для скролла вкладок */
        .nav-tabs-scrollable {
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            flex-wrap: nowrap;
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }

        .nav-tabs-scrollable::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        .nav-tabs-scrollable .nav-item {
            display: inline-block;
            float: none;
        }

        .nav-tabs-scrollable .nav-link {
            min-width: 120px;
            text-align: center;
        }

        /* Улучшенный стиль скролла (если хотите видеть скролл) */
        .nav-tabs-scrollable-visible {
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            flex-wrap: nowrap;
            scrollbar-width: thin;
            scrollbar-color: #6c757d #f8f9fa;
        }

        .nav-tabs-scrollable-visible::-webkit-scrollbar {
            height: 6px;
        }

        .nav-tabs-scrollable-visible::-webkit-scrollbar-track {
            background: #f8f9fa;
            border-radius: 3px;
        }

        .nav-tabs-scrollable-visible::-webkit-scrollbar-thumb {
            background-color: #6c757d;
            border-radius: 3px;
        }

        /* Стили для индикатора текущей вкладки при скролле */
        .nav-tabs-wrapper {
            position: relative;
            border-bottom: 1px solid #dee2e6;
        }

        /* Стили для ползунка активности */
        .active-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .active-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #27ae60;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(30px);
        }

        /* Контейнер для таблицы с вертикальной прокруткой */
        .scrollable-table-container {
            overflow-y: auto;
        }

        /* Фиксированный заголовок таблицы */
        .fixed-header {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
            color: white;
        }

        .feature-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .user-card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
        }

        /* Улучшенный стиль для вкладок */
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-radius: 0.375rem 0.375rem 0 0;
            margin-bottom: -1px;
        }

        .nav-tabs .nav-link.active {
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
            color: #0d6efd;
            font-weight: 500;
        }

        /* Кнопки-стрелки для навигации по вкладкам (опционально) */
        .tabs-navigation {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            gap: 10px;
        }

        .tabs-navigation .nav-tabs {
            flex: 1;
            overflow: hidden;
        }

        .tab-scroll-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-scroll-btn:hover {
            background: #e9ecef;
        }

        .tab-scroll-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>

    <script type="module" src="/src/constants/typeMessage.js"></script>
    <script type="module" src="/src/constants/actions.js"></script>
    <script type="module" src="/js/modals/setting.js"></script>
</head>

<body>
    <?php include __DIR__ . '/Modal/message_modal.php'; ?>
    <!-- Навигационная панель -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-person-badge me-2"></i>Панель администратора
            </a>
            <div class="ms-auto d-flex align-items-center">
                <span class="navbar-text me-3">Привет, <?= htmlspecialchars($_SESSION["FIO"]) ?></span>
                <a href="/src/View/home.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-house me-1"></i>На главную
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Сообщения -->
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= $successMessage ?>
            </div>
        <?php endif; ?>

        <!-- Панель управления с прокручиваемыми вкладками -->
        <div class="tabs-navigation">
            <button class="tab-scroll-btn" id="scrollLeftBtn" onclick="scrollTabs(-150)">
                <i class="bi bi-chevron-left"></i>
            </button>

            <ul class="nav nav-tabs nav-tabs-scrollable-visible mb-2" id="adminTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users"
                        type="button">
                        <i class="bi bi-people"></i> Пользователи
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="customers-tab" data-bs-toggle="tab" data-bs-target="#customers"
                        type="button">
                        <i class="bi bi-building"></i> Локации
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="convert-tab" data-bs-toggle="tab" data-bs-target="#serviceCenters"
                        type="button">
                        <i class="bi bi-building"></i> Сервисные центры
                    </button>
                </li>
                <!-- Можно добавить дополнительные вкладки для тестирования скролла -->
                <!--li class="nav-item" role="presentation">
                    <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings"
                        type="button">
                        <i class="bi bi-gear"></i> Настройки
                    </button>
                </!--li-->
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button">
                        <i class="bi bi-journal-text"></i> Логи
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="error-logs-tab" data-bs-toggle="tab" data-bs-target="#error-logs" type="button">
                        <i class="bi bi-exclamation-triangle"></i> Логи - ошибок
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports"
                        type="button">
                        <i class="bi bi-bar-chart"></i> Отчеты
                    </button>
                </li>
            </ul>

            <button class="tab-scroll-btn" id="scrollRightBtn" onclick="scrollTabs(150)">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

        <div class="tab-content" id="adminTabsContent">
            <!-- Вкладка пользователей -->
            <div class="tab-pane fade show active" id="users" role="tabpanel">
                <?php include __DIR__ . '/AdminTabs/users_tab.php'; ?>
            </div>

            <!-- Вкладка локации -->
            <div class="tab-pane fade" id="customers" role="tabpanel">
                <?php include __DIR__ . '/AdminTabs/locations_tab.php'; ?>
            </div>

            <!-- Вкладка сервисные центры -->
            <div class="tab-pane fade" id="serviceCenters" role="tabpanel">
                <?php include __DIR__ . '/AdminTabs/serviceCenters_tab.php'; ?>
            </div>

            <!-- Дополнительные вкладки для демонстрации -->
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Настройки системы</h5>
                        <p class="card-text">Раздел настроек будет доступен в следующей версии.</p>
                    </div>
                </div>
            </div>

            <!-- Вкладка логи -->
            <div class="tab-pane fade" id="logs" role="tabpanel">
                <?php include __DIR__ . '/AdminTabs/logs_tab.php'; ?>
            </div>

            <div class="tab-pane fade" id="error-logs" role="tabpanel">
                <?php include __DIR__ . '/AdminTabs/error_logs_tab.php'; ?>
            </div>
            <!--div class="tab-pane fade" id="logs" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Логи системы</h5>
                        <p class="card-text">Просмотр логов будет доступен в следующей версии.</p>
                    </div>
                </div>
            </div-->

            <div class="tab-pane fade" id="reports" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Отчеты</h5>
                        <p class="card-text">Генерация отчетов будет доступна в следующей версии.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script type="module" src="/js/modals/userModal.js"></script>

    <script>
        // Активация вкладок Bootstrap
        const triggerTabList = document.querySelectorAll('#adminTabs button');
        triggerTabList.forEach(triggerEl => {
            new bootstrap.Tab(triggerEl);
        });

        // Функция для скролла вкладок
        function scrollTabs(scrollOffset) {
            const tabsContainer = document.querySelector('.nav-tabs-scrollable-visible');
            if (tabsContainer) {
                tabsContainer.scrollLeft += scrollOffset;
                updateScrollButtons();
            }
        }

        // Обновление состояния кнопок скролла
        function updateScrollButtons() {
            const tabsContainer = document.querySelector('.nav-tabs-scrollable-visible');
            const scrollLeftBtn = document.getElementById('scrollLeftBtn');
            const scrollRightBtn = document.getElementById('scrollRightBtn');

            if (tabsContainer && scrollLeftBtn && scrollRightBtn) {
                scrollLeftBtn.disabled = tabsContainer.scrollLeft <= 0;
                scrollRightBtn.disabled = tabsContainer.scrollLeft + tabsContainer.clientWidth >= tabsContainer.scrollWidth;
            }
        }

        // Инициализация скролла вкладок
        document.addEventListener('DOMContentLoaded', function() {
            // Обновление кнопок скролла
            updateScrollButtons();

            // Обновление при скролле
            const tabsContainer = document.querySelector('.nav-tabs-scrollable-visible');
            if (tabsContainer) {
                tabsContainer.addEventListener('scroll', updateScrollButtons);
            }

            // Автоматическая прокрутка к активной вкладке
            setTimeout(function() {
                const activeTab = document.querySelector('#adminTabs .nav-link.active');
                if (activeTab && tabsContainer) {
                    activeTab.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'center'
                    });
                }
            }, 100);

            // Сохранение и восстановление активной вкладки
            function saveActiveTab(tabId) {
                localStorage.setItem('activeAdminTab', tabId);
            }

            function loadActiveTab() {
                const savedTab = localStorage.getItem('activeAdminTab');
                if (savedTab) {
                    const tabButton = document.querySelector(`[data-bs-target="${savedTab}"]`);
                    if (tabButton) {
                        const tab = new bootstrap.Tab(tabButton);
                        tab.show();
                        // Прокрутка к активной вкладке после загрузки
                        setTimeout(() => {
                            if (tabButton && tabsContainer) {
                                tabButton.scrollIntoView({
                                    behavior: 'smooth',
                                    block: 'nearest',
                                    inline: 'center'
                                });
                            }
                        }, 50);
                    }
                }
            }

            // Обработчик переключения вкладок
            const tabButtons = document.querySelectorAll('#adminTabs button[data-bs-toggle="tab"]');
            tabButtons.forEach(button => {
                button.addEventListener('shown.bs.tab', function(event) {
                    const target = event.target.getAttribute('data-bs-target');
                    saveActiveTab(target);

                    // Прокрутка к активной вкладке
                    if (tabsContainer) {
                        event.target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                            inline: 'center'
                        });
                    }
                });
            });

            // Загружаем сохраненную вкладку при загрузке страницы
            loadActiveTab();

            // Подтверждение перед деактивацией текущего пользователя
            document.getElementById('users-form')?.addEventListener('submit', function(e) {
                const selfCheckbox = this.querySelector('input[value="<?= $currentUser->idUser ?? '' ?>"]');
                if (selfCheckbox && !selfCheckbox.checked) {
                    if (!confirm('Вы деактивируете свой аккаунт! Продолжить?')) {
                        e.preventDefault();
                        selfCheckbox.checked = true;
                    }
                }
            });
        });

        // Обработка клавиш для навигации по вкладкам
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'PageUp') {
                scrollTabs(-150);
                e.preventDefault();
            } else if (e.ctrlKey && e.key === 'PageDown') {
                scrollTabs(150);
                e.preventDefault();
            }
        });
    </script>

    <script type="module" src="/js/templates/cudRowsInTable.js"></script>
    <script type="module" src="/js/templates/cudRowsInSelect.js"></script>
    <script type="module" src="/js/templates/expandableSection.js"></script>
    <script type="module" src="/js/templates/entityCreationTemplate.js"></script>
    <script type="module" src="/js/templates/entityActionTemplate.js"></script>
    <script type="module" src="/js/modals/modalLoader.js"></script>
    <script type="module" src="/js/modals/userModal.js"></script>
    <script type="module" src="/js/modals/locationModal.js"></script>
    <script type="module" src="/js/modalTypes.js"></script>
    <script type="module" src="/js/updateFunctions.js"></script>

    <div id="modalContainer"></div>
</body>

</html>