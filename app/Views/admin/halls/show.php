<a class="back-link" href="<?= url('/admin/halls') ?>"><?= icon('back', 16) ?> All halls</a>
<div class="dash-grid">
    <div class="card seat-panel">
        <h2><?= e($hall['hall_name']) ?> · <?= (int) $hall['capacity'] ?> seats</h2>
        <p class="muted small">Click a seat to change its type or delete it.</p>
        <div class="screen-wrap"><div class="screen">SCREEN</div></div>
        <div class="seat-map admin-seat-map">
            <?php foreach ($rows as $label => $seats): ?>
                <div class="seat-row">
                    <span class="row-label"><?= e($label) ?></span>
                    <div class="row-seats">
                        <?php foreach ($seats as $s): ?>
                            <div class="seat-cell">
                                <details>
                                    <summary class="seat <?= $s['seat_type'] === 'VIP' ? 'vip' : ($s['seat_type'] === 'Accessible' ? 'accessible' : '') ?>"
                                             style="display:flex;align-items:center;justify-content:center" data-tip="<?= e($s['row_label'] . $s['seat_number'] . ' · ' . $s['seat_type']) ?>"><?= (int) $s['seat_number'] ?></summary>
                                    <div class="seat-pop">
                                        <strong>Seat <?= e($s['row_label'] . $s['seat_number']) ?></strong>
                                        <form method="post" action="<?= url('/admin/seats/' . (int) $s['seat_id'] . '/type') ?>">
                                            <?= App\Core\Csrf::field() ?>
                                            <select name="seat_type" aria-label="Seat type">
                                                <?php foreach (App\Models\Seat::TYPES as $t): ?><option <?= $s['seat_type'] === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                                            </select>
                                            <button class="icon-btn" type="submit" data-tip="Save type" aria-label="Save type"><?= icon('check', 14) ?></button>
                                        </form>
                                        <form method="post" action="<?= url('/admin/seats/' . (int) $s['seat_id'] . '/delete') ?>" data-confirm="Delete seat <?= e($s['row_label'] . $s['seat_number']) ?>?">
                                            <?= App\Core\Csrf::field() ?>
                                            <button class="btn btn-danger btn-sm btn-block" type="submit"><?= icon('trash', 14) ?> Delete seat</button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$rows): ?><p class="muted">This hall has no seats yet.</p><?php endif; ?>
        </div>
        <div class="legend">
            <span><i class="seat-demo"></i> Standard</span>
            <span><i class="seat-demo vip"></i> VIP</span>
            <span><i class="seat-demo" style="background:#142f3d;border-color:#1f6c8a"></i> Accessible</span>
        </div>
    </div>

    <div style="display:grid;gap:18px;align-content:start">
        <div class="card">
            <h2>Rename hall</h2>
            <form method="post" action="<?= url('/admin/halls/' . (int) $hall['hall_id']) ?>" class="form js-validate" novalidate>
                <?= App\Core\Csrf::field() ?>
                <label class="field <?= isset($errors['hall_name']) ? 'invalid' : '' ?>">
                    <span>Hall name</span>
                    <input type="text" name="hall_name" value="<?= e(old('hall_name', $hall['hall_name'])) ?>" required maxlength="50">
                    <?= field_error($errors, 'hall_name') ?>
                </label>
                <button class="btn" type="submit">Save</button>
            </form>
        </div>
        <div class="card">
            <h2>Add a seat</h2>
            <form method="post" action="<?= url('/admin/halls/' . (int) $hall['hall_id'] . '/seats') ?>" class="form js-validate" novalidate>
                <?= App\Core\Csrf::field() ?>
                <div class="field-row">
                    <label class="field <?= isset($errors['row_label']) ? 'invalid' : '' ?>">
                        <span>Row</span>
                        <input type="text" name="row_label" value="<?= e(old('row_label')) ?>" required maxlength="3" pattern="[A-Za-z]{1,3}" placeholder="A" data-msg="Row must be letters.">
                        <?= field_error($errors, 'row_label') ?>
                    </label>
                    <label class="field <?= isset($errors['seat_number']) ? 'invalid' : '' ?>">
                        <span>Number</span>
                        <input type="number" name="seat_number" value="<?= e(old('seat_number')) ?>" required min="1" max="99">
                        <?= field_error($errors, 'seat_number') ?>
                    </label>
                </div>
                <label class="field">
                    <span>Type</span>
                    <select name="seat_type">
                        <?php foreach (App\Models\Seat::TYPES as $t): ?><option><?= e($t) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <button class="btn" type="submit"><?= icon('plus', 16) ?> Add seat</button>
            </form>
        </div>
    </div>
</div>
