<?php
$val = fn (string $k, $default = '') => old($k, $show[$k] ?? $default);
$action = $show ? url('/admin/showtimes/' . (int) $show['showtime_id']) : url('/admin/showtimes');
?>
<a class="back-link" href="<?= url('/admin/showtimes') ?>"><?= icon('back', 16) ?> All screenings</a>
<div class="card" style="max-width:720px">
    <p class="muted small">Two screenings cannot overlap in the same hall. The end time fills in from the movie length plus 15 minutes for cleaning; you can change it.</p>
    <form method="post" action="<?= $action ?>" class="form js-validate" novalidate>
        <?= App\Core\Csrf::field() ?>
        <div class="field-row">
            <label class="field <?= isset($errors['movie_id']) ? 'invalid' : '' ?>">
                <span>Movie</span>
                <select name="movie_id" id="st-movie" required>
                    <option value="">Choose a movie</option>
                    <?php foreach ($movies as $m): ?>
                        <option value="<?= (int) $m['movie_id'] ?>" data-duration="<?= (int) $m['duration'] ?>" <?= (int) $val('movie_id') === (int) $m['movie_id'] ? 'selected' : '' ?>>
                            <?= e($m['title']) ?> (<?= (int) $m['duration'] ?> min<?= $m['status'] === 'Coming Soon' ? ', coming soon' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'movie_id') ?>
            </label>
            <label class="field <?= isset($errors['hall_id']) ? 'invalid' : '' ?>">
                <span>Hall</span>
                <select name="hall_id" required>
                    <option value="">Choose a hall</option>
                    <?php foreach ($halls as $h): ?>
                        <option value="<?= (int) $h['hall_id'] ?>" <?= (int) $val('hall_id') === (int) $h['hall_id'] ? 'selected' : '' ?>><?= e($h['hall_name']) ?> (<?= (int) $h['capacity'] ?> seats)</option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'hall_id') ?>
            </label>
        </div>
        <div class="field-row">
            <label class="field <?= isset($errors['show_date']) ? 'invalid' : '' ?>">
                <span>Date</span>
                <input type="date" name="show_date" value="<?= e($val('show_date', date('Y-m-d'))) ?>" required>
                <?= field_error($errors, 'show_date') ?>
            </label>
            <label class="field <?= isset($errors['ticket_price']) ? 'invalid' : '' ?>">
                <span>Ticket price (<?= e(config('app.currency')) ?>)</span>
                <input type="number" name="ticket_price" value="<?= e($val('ticket_price', '20')) ?>" required min="0" step="0.5">
                <?= field_error($errors, 'ticket_price') ?>
            </label>
        </div>
        <div class="field-row">
            <label class="field <?= isset($errors['start_time']) ? 'invalid' : '' ?>">
                <span>Start time</span>
                <input type="time" name="start_time" id="st-start" value="<?= e(substr((string) $val('start_time'), 0, 5)) ?>" required>
                <?= field_error($errors, 'start_time') ?>
            </label>
            <label class="field <?= isset($errors['end_time']) ? 'invalid' : '' ?>">
                <span>End time</span>
                <input type="time" name="end_time" id="st-end" value="<?= e(substr((string) $val('end_time'), 0, 5)) ?>" required>
                <?= field_error($errors, 'end_time') ?>
            </label>
        </div>
        <div class="actions">
            <button class="btn" type="submit"><?= icon('check', 16) ?> <?= $show ? 'Save changes' : 'Add screening' ?></button>
            <a class="btn btn-ghost" href="<?= url('/admin/showtimes') ?>">Cancel</a>
        </div>
    </form>
</div>
