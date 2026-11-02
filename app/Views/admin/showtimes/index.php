<div class="page-head">
    <form class="filters" method="get" action="<?= url('/admin/showtimes') ?>" style="margin:0;flex:1">
        <input type="date" name="date" value="<?= e($filters['date']) ?>" aria-label="Date" data-tip="Filter by date">
        <select name="movie" aria-label="Movie">
            <option value="">All movies</option>
            <?php foreach ($movies as $m): ?><option value="<?= (int) $m['movie_id'] ?>" <?= (int) $filters['movie'] === (int) $m['movie_id'] ? 'selected' : '' ?>><?= e($m['title']) ?></option><?php endforeach; ?>
        </select>
        <select name="hall" aria-label="Hall">
            <option value="">All halls</option>
            <?php foreach ($halls as $h): ?><option value="<?= (int) $h['hall_id'] ?>" <?= (int) $filters['hall'] === (int) $h['hall_id'] ? 'selected' : '' ?>><?= e($h['hall_name']) ?></option><?php endforeach; ?>
        </select>
        <label class="check small"><input type="checkbox" name="past" value="1" <?= $filters['past'] ? 'checked' : '' ?>> Include past</label>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <a class="btn" href="<?= url('/admin/showtimes/create') ?>"><?= icon('plus', 16) ?> Add screening</a>
</div>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Date</th><th>Time</th><th>Movie</th><th>Hall</th><th class="num">Price</th><th class="num">Booked</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($showtimes as $s): $past = App\Models\Showtime::hasStarted($s); ?>
            <tr style="<?= $past ? 'opacity:.55' : '' ?>">
                <td><?= e(fmt_date($s['show_date'])) ?></td>
                <td><?= e(fmt_time($s['start_time'])) ?> – <?= e(fmt_time($s['end_time'])) ?></td>
                <td><?= e($s['title']) ?></td>
                <td><?= e($s['hall_name']) ?></td>
                <td class="num"><?= e(money($s['ticket_price'])) ?></td>
                <td class="num" data-tip="Seats booked / capacity"><?= (int) $s['booked_count'] ?>/<?= (int) $s['capacity'] ?></td>
                <td>
                    <div class="actions-cell">
                        <a class="icon-btn" href="<?= url('/admin/bookings', ['q' => $s['title'], 'date' => $s['show_date']]) ?>" data-tip="Bookings for this day" aria-label="Bookings"><?= icon('ticket', 16) ?></a>
                        <a class="icon-btn" href="<?= url('/admin/showtimes/' . (int) $s['showtime_id'] . '/edit') ?>" data-tip="Edit" aria-label="Edit"><?= icon('edit', 16) ?></a>
                        <form method="post" action="<?= url('/admin/showtimes/' . (int) $s['showtime_id'] . '/delete') ?>" class="inline" data-confirm="Delete this screening?">
                            <?= App\Core\Csrf::field() ?>
                            <button class="icon-btn danger" type="submit" data-tip="Delete" aria-label="Delete"><?= icon('trash', 16) ?></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$showtimes): ?><tr><td colspan="7" class="center muted">No screenings found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
