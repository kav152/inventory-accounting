<?php
include_once __DIR__ . '/../Templates/expandable_section.php';
?>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Добавить пользователя</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="idUser" name="id" value="<?= htmlspecialchars($user->IDUser ?? "") ?>">
                    <input type="hidden" id="isActive" name="isActive" value="<?= htmlspecialchars($user->isActive ?? 0) ?>">

                    <div class="mb-3">
                        <!--input type="text" name="surname" class="form-control" placeholder="Фамилия" required-->
                        <input type="text" name="Surname" class="form-control" placeholder="Фамилия"
                            value="<?= htmlspecialchars($user->Surname ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <!--input type="text" name="name" class="form-control" placeholder="Имя" required-->
                        <input type="text" name="Name" class="form-control" placeholder="Имя"
                            value="<?= htmlspecialchars($user->Name ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <!--input type="text" name="patronymic" class="form-control" placeholder="Отчество"-->
                        <input type="text" name="Patronymic" class="form-control" placeholder="Отчество"
                            value="<?= htmlspecialchars($user->Patronymic ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <!--input type="password" name="password" class="form-control" placeholder="Пароль" required-->
                        <input type="password" name="Password" class="form-control" placeholder="Пароль"
                            value="<?= htmlspecialchars($user->Password ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <select name="Status"
                            class="form-select form-select-sm">
                            <option value="0" <?= $user->Status == 0 ? 'selected' : '' ?>>Администратор</option>
                            <option value="1" <?= $user->Status == 1 ? 'selected' : '' ?>>Кладовщик
                            </option>
                        </select>
                    </div>


                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить пользователя</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>