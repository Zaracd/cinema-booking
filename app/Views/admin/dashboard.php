<?php $max = max(1, max(array_column($revenue, 'amount'))); ?>
<div class="stat-grid">
    <a class="stat" href="<?= url('/admin/users') ?>"><span class="stat-label"><?= icon('users', 16) ?> Users</span><span class="stat-value"><?= (int) $counts['users'] ?></span></a>
    <a class="stat" href="<?= url('/admin/movies') ?>"><span class="stat-label"><?= icon('film', 16) ?> Movies</span><span class="stat-value"><?= (int) $counts['movies'] ?></span></a>
    <a class="stat" href="<?= url('/admin/halls') ?>"><span class="stat-label"><?= icon('door', 16) ?> Halls</span><span class="stat-value"><?= (int) $counts['halls'] ?></span></a>
    <a class="stat" href="<?= url('/admin/showtimes') ?>"><span class="stat-label"><?= icon('calendar', 16) ?> Screenings</span><span class="stat-value"><?= (int) $counts['showtimes'] ?></span><span class="stat-sub"><?= (int) $counts['upcoming'] ?> upcoming</span></a>
    <a class="stat" href="<?= url('/admin/bookings') ?>"><span class="stat-label"><?= icon('ticket', 16) ?> Bookings</span><span class="stat-value"><?= (int) $stats['total'] ?></span><span class="stat-sub"><?= (int) $stats['confirmed'] ?> confirmed · <?= (int) $stats['cancelled'] ?> cancelled</span></a>
    <div class="stat"><span class="stat-label"><?= icon('card', 16) ?> Revenue (paid)</span><span class="stat-value" style="font-size:1.4rem"><?= e(money($stats['revenue'])) ?></span><span class="stat-sub"><?= (int) $stats['tickets'] ?> seats sold</span></div>
</div>

<div class="dash-grid">
    <div class="card">
        <h2>Recent bookings</h2>
        <?php if ($recent): ?>
        <div class="table-wrap" style="border:0">
            <table class="table">
                <thead><tr><th>#</th><th>Customer</th><th>Movie</th><th>Status</th><th class="num">Total</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $b): ?>
                    <tr>
                        <td><a href="<?= url('/admin/bookings/' . (int) $b['booking_id']) ?>"><?= (int) $b['booking_id'] ?></a></td>
                        <td><?= e($b['full_name']) ?></td>
                        <td><?= e($b['title']) ?><br><span class="muted small"><?= e(fmt_date($b['show_date'])) ?> <?= e(fmt_time($b['start_time'])) ?></span></td>
                        <td><?= status_badge($b['status']) ?></td>
                        <td class="num"><?= e(money($b['total_amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?><p class="muted">No bookings yet.</p><?php endif; ?>
    </div>
    <div style="display:grid;gap:18px;align-content:start">
        <div class="card">
            <h2>Revenue, last 7 days</h2>
            <div class="bar-chart">
                <?php foreach ($revenue as $r): ?>
                    <div class="bar-col" data-tip="<?= e(date('D j M', strtotime($r['date'])) . ': ' . money($r['amount'])) ?>">
                        <div class="bar" style="height:<?= round($r['amount'] / $max * 100) ?>%"></div>
                        <small><?= date('D', strtotime($r['date'])) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <h2>Today's screenings</h2>
            <?php if ($today): ?>
                <table class="table">
                    <?php foreach ($today as $s): ?>
                        <tr>
                            <td><?= e(fmt_time($s['start_time'])) ?></td>
                            <td><?= e($s['title']) ?><br><span class="muted small"><?= e($s['hall_name']) ?></span></td>
                            <td class="num" data-tip="Seats booked / capacity"><?= (int) $s['booked_count'] ?>/<?= (int) $s['capacity'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?><p class="muted">No screenings today.</p><?php endif; ?>
        </div>
    </div>
</div>
