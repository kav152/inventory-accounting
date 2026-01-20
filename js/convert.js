import { showNotification } from "./modals/setting.js";
import { TypeMessage } from "../src/constants/typeMessage.js";

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('conversion-form');
    const testConnectionBtn = document.getElementById('testConnection');
    const startConversionBtn = document.getElementById('startConversion');
    const progressSection = document.getElementById('conversion-progress');
    const progressBar = document.getElementById('progress-bar');
    const conversionLog = document.getElementById('conversion-log');
    const resultsSection = document.getElementById('conversion-results');
    const errorSection = document.getElementById('conversion-error');

    // Валидация формы
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        startConversion();
    });

    testConnectionBtn.addEventListener('click', function() {
        testDatabaseConnection();
    });

    function addLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.innerHTML = `<span class="text-muted">[${timestamp}]</span> ${message}`;
        
        if (type === 'error') {
            logEntry.classList.add('text-danger');
        } else if (type === 'success') {
            logEntry.classList.add('text-success');
        } else if (type === 'warning') {
            logEntry.classList.add('text-warning');
        }
        
        conversionLog.appendChild(logEntry);
        conversionLog.scrollTop = conversionLog.scrollHeight;
    }

    function updateProgress(percentage) {
        progressBar.style.width = percentage + '%';
        progressBar.textContent = Math.round(percentage) + '%';
    }

    function testDatabaseConnection() {
        const oldDb = document.getElementById('oldDatabase').value;
        const newDb = document.getElementById('newDatabase').value;

        
       // addLog(`Проверка подключения к базам данных...`);
       console.log(`Проверка подключения к базам данных...`);
        
        fetch('/src/BusinessLogic/conversion.php?action=test-connection', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                oldDatabase: oldDb,
                newDatabase: newDb
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addLog('✓ Подключение к базам данных успешно установлено', 'success');
                showNotification(TypeMessage.success, 'Проверка подключения', 'Подключение к базам данных успешно установлено');
            } else {
                addLog(`✗ Ошибка подключения: ${data.error}`, 'error');
                console.log(`Ошибка подключения ${data.error}`);
                showNotification(TypeMessage.error, `Ошибка подключения ${data.error}`);
            }
        })
        .catch(error => {
            addLog(`✗ Ошибка сети: ${error.message}`, 'error');
            showNotification(TypeMessage.error, `Ошибка сети ${error.message}`);
            console.log(`Ошибка сети ${error.message}`);
        });
    }

    function startConversion() {
        const oldDb = document.getElementById('oldDatabase').value;
        const newDb = document.getElementById('newDatabase').value;
        const skipDuplicates = document.getElementById('skipDuplicates').checked;
        const includeHistory = document.getElementById('includeHistory').checked;
        const includeRepairs = document.getElementById('includeRepairs').checked;
        
        // Сброс лога и прогресса
        conversionLog.innerHTML = '';
        progressSection.style.display = 'block';
        resultsSection.style.display = 'none';
        errorSection.style.display = 'none';
        updateProgress(0);
        
        addLog('Начало конвертации данных...');
        addLog(`Источник: ${oldDb}`);
        addLog(`Назначение: ${newDb}`);
        
        // Блокировка кнопок
        startConversionBtn.disabled = true;
        testConnectionBtn.disabled = true;
        
        // Отправка запроса на конвертацию
        fetch('/src/BusinessLogic/conversion.php?action=convert', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                oldDatabase: oldDb,
                newDatabase: newDb,
                skipDuplicates: skipDuplicates,
                includeHistory: includeHistory,
                includeRepairs: includeRepairs
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addLog('✓ Конвертация завершена успешно!', 'success');
                updateProgress(100);
                
                // Показать результаты
                document.getElementById('results-details').innerHTML = `
                    <p><strong>Статистика:</strong></p>
                    <ul>
                        <li>ТМЦ перенесено: ${data.stats.inventoryItems || 0}</li>
                        <li>Пользователей перенесено: ${data.stats.users || 0}</li>
                        <li>Брендов перенесено: ${data.stats.brands || 0}</li>
                        <li>Моделей перенесено: ${data.stats.models || 0}</li>
                        <li>Записей истории: ${data.stats.history || 0}</li>
                    </ul>
                `;
                resultsSection.style.display = 'block';
                showNotification('success', 'Конвертация завершена', 'Данные успешно перенесены в новую базу данных');
            } else {
                addLog(`✗ Ошибка конвертации: ${data.error}`, 'error');
                document.getElementById('error-details').textContent = data.error;
                errorSection.style.display = 'block';
                showNotification('error', 'Ошибка конвертации', data.error);
            }
        })
        .catch(error => {
            addLog(`✗ Ошибка сети: ${error.message}`, 'error');
            document.getElementById('error-details').textContent = error.message;
            errorSection.style.display = 'block';
            showNotification('error', 'Ошибка сети', error.message);
        })
        .finally(() => {
            // Разблокировка кнопок
            startConversionBtn.disabled = false;
            testConnectionBtn.disabled = false;
        });
    }
});