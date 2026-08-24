<div class="modal fade" id="writtenOffMiniModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#0f766e,#0d9488);color:#fff;border:0;">
                <div>
                    <h5 class="modal-title mb-0">Все списанные</h5>
                    <small style="opacity:.85">Мини-список списанных ТМЦ</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3 flex-wrap">
                    <span class="badge rounded-pill text-bg-light border" id="writtenOffMiniCount">0 записей</span>
                    <input type="search" id="writtenOffMiniSearch" class="form-control form-control-sm"
                        style="max-width:280px;" placeholder="Поиск…" autocomplete="off">
                    <a href="/src/View/write_off.php?filter=written-off" class="btn btn-sm btn-outline-success">
                        Открыть полный реестр
                    </a>
                </div>
                <div class="table-responsive" style="max-height:420px;">
                    <table class="table table-sm align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>ИД</th>
                                <th>Наименование</th>
                                <th>Сер. номер</th>
                                <th>Бренд</th>
                                <th>Локация</th>
                                <th>Юр. лицо</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="writtenOffMiniBody">
                            <tr><td colspan="7" class="text-center text-muted py-4">Загрузка…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function refreshWrittenOffMiniCount() {
        const countEl = document.getElementById('writtenOffMiniCount');
        const visible = Array.from(document.querySelectorAll('#writtenOffMiniBody tr[data-id]'))
            .filter((row) => row.style.display !== 'none').length;
        if (countEl) countEl.textContent = visible + ' записей';
    }

    function filterWrittenOffMiniRows() {
        const q = (document.getElementById('writtenOffMiniSearch')?.value || '').trim().toLowerCase();
        document.querySelectorAll('#writtenOffMiniBody tr[data-id]').forEach((row) => {
            const blob = (row.getAttribute('data-search') || '').toLowerCase();
            row.style.display = !q || blob.includes(q) ? '' : 'none';
        });
        refreshWrittenOffMiniCount();
    }

    async function openWrittenOffMiniModal() {
        const modalEl = document.getElementById('writtenOffMiniModal');
        if (!modalEl) return;
        const body = document.getElementById('writtenOffMiniBody');
        const countEl = document.getElementById('writtenOffMiniCount');
        const searchInput = document.getElementById('writtenOffMiniSearch');
        if (searchInput) searchInput.value = '';
        body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Загрузка…</td></tr>';

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        try {
            const response = await fetch('/src/BusinessLogic/ActionsTMC/processGetWrittenOffItems.php');
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'Ошибка загрузки');

            const items = data.items || [];
            countEl.textContent = items.length + ' записей';

            if (!items.length) {
                body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Списанных ТМЦ нет</td></tr>';
                return;
            }

            body.innerHTML = items.map((item) => {
                const search = [item.id, item.name, item.serial, item.brand, item.location, item.legal]
                    .join(' ')
                    .toLowerCase();
                return `
                <tr data-id="${item.id}" data-search="${escapeHtml(search)}">
                    <td>${item.id}</td>
                    <td>${escapeHtml(item.name)}</td>
                    <td>${escapeHtml(item.serial || '—')}</td>
                    <td>${escapeHtml(item.brand || '—')}</td>
                    <td>${escapeHtml(item.location || '—')}</td>
                    <td>${escapeHtml(item.legal || 'не указано')}</td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-teal restore-mini-btn"
                            data-id="${item.id}" title="Вернуть из списания"
                            style="border-color:#0d9488;color:#0f766e;">
                            Вернуть
                        </button>
                    </td>
                </tr>`;
            }).join('');

            body.querySelectorAll('.restore-mini-btn').forEach((btn) => {
                btn.addEventListener('click', async function () {
                    const id = this.getAttribute('data-id');
                    if (typeof window.returnToWorkTMC === 'function') {
                        await window.returnToWorkTMC(id);
                        refreshWrittenOffMiniCount();
                        if (!document.querySelectorAll('#writtenOffMiniBody tr[data-id]').length) {
                            body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Списанных ТМЦ нет</td></tr>';
                        }
                    }
                });
            });

            if (searchInput && !searchInput.dataset.bound) {
                searchInput.dataset.bound = '1';
                searchInput.addEventListener('input', filterWrittenOffMiniRows);
            }
        } catch (error) {
            body.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(error.message || error)}</td></tr>`;
        }
    }

    window.openWrittenOffMiniModal = openWrittenOffMiniModal;
})();
</script>
