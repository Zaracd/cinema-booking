<div class="dash-grid">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Hall</th><th>Capacity</th><th>VIP seats</th><th>Upcoming screenings</th><th class="num">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($halls as $h): ?>
                <tr>
                    <td><a href="<?= url('/admin/halls/' . (int) $h['hall_id']) ?>"><strong><?= e($h['hall_name']) ?></strong></a></td>
                    <td><?= (int) $h['capacity'] ?> seats</td>
                    <td><?= (int) $h['vip_count'] ?></td>
                    <td><?= (int) $h['upcoming_shows'] ?></td>
                    <td>
                        <div class="actions-cell">
                            <a class="icon-btn" href="<?= url('/admin/halls/' . (int) $h['hall_id']) ?>" data-tip="Manage seats" aria-label="Manage seats"><?= icon('grid', 16) ?></a>
                            <form method="post" action="<?= url('/admin/halls/' . (int) $h['hall_id'] . '/delete') ?>" class="inline" data-confirm="Delete <?= e($h['hall_name']) ?> and all its seats?">
                                <?= App\Core\Csrf::field() ?>
                                <button class="icon-btn danger" type="submit" data-tip="Delete hall" aria-label="Delete hall"><?= icon('trash', 16) ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$halls): ?><tr><td colspan="5" class="center muted">No halls yet. Add one on the right.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Add a hall</h2>
        <p class="muted small">Seats are created in a grid. Rows are named A, B, C... You can add, remove or change single seats afterwards.</p>
        <form method="post" action="<?= url('/admin/halls') ?>" class="form js-validate" novalidate>
            <?= App\Core\Csrf::field() ?>
            <label class="field <?= isset($errors['hall_name']) ? 'invalid' : '' ?>">
                <span>Hall name</span>
                <input type="text" name="hall_name" value="<?= e(old('hall_name')) ?>" required maxlength="50" placeholder="e.g. Hall 4">
                <?= field_error($errors, 'hall_name') ?>
            </label>
            <div class="field-row">
                <label class="field <?= isset($errors['rows']) ? 'invalid' : '' ?>">
                    <span>Rows</span>
                    <input type="number" id="hall-rows" name="rows" value="<?= e(old('rows', '6')) ?>" required min="1" max="26">
                    <?= field_error($errors, 'rows') ?>
                </label>
                <label class="field <?= isset($errors['per_row']) ? 'invalid' : '' ?>">
                    <span>Seats per row</span>
                    <input type="number" id="hall-per" name="per_row" value="<?= e(old('per_row', '10')) ?>" required min="1" max="40">
                    <?= field_error($errors, 'per_row') ?>
                </label>
            </div>
            <label class="field">
                <span>VIP rows (optional)</span>
                <input type="text" name="vip_rows" value="<?= e(old('vip_rows')) ?>" placeholder="e.g. E, F" maxlength="60">
            </label>
            <p class="muted small">Capacity: <strong id="hall-cap">0</strong> seats</p>
            <button class="btn" type="submit"><?= icon('plus', 16) ?> Create hall</button>
        </form>
    </div>
</div>
