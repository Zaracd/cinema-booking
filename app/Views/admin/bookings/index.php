<div class="stat-grid">
    <div class="stat"><span class="stat-label">All bookings</span><span class="stat-value"><?= (int) $stats['total'] ?></span></div>
    <div class="stat"><span class="stat-label">Confirmed</span><span class="stat-value" style="color:var(--green)"><?= (int) $stats['confirmed'] ?></span></div>
    <div class="stat"><span class="stat-label">Pending payment</span><span class="stat-value" style="color:var(--amber)"><?= (int) $stats['pending'] ?></span></div>
    <div class="stat"><span class="stat-label">Cancelled</span><span class="stat-value" style="color:var(--red)"><?= (int) $stats['cancelled'] ?></span></div>
</div>

<form class="filters" method="get" action="<?= url('/admin/bookings') ?>">
    <label class="filter-search"><?= icon('search') ?><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Booking #, customer, email or movie" aria-label="Search bookings"></label>
    <select name="status" aria-label="Booking status">
        <option value="">Any status</option>
        <?php foreach (['Pending', 'Confirmed', 'Cancelled'] as $s): ?><option <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
    </select>
    <select name="payment" aria-label="Payment status">
        <option value="">Any payment</option>
        <?php foreach (['Unpaid', 'Paid', 'Refunded'] as $s): ?><option <?= $filters['payment'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
    </select>
    <input type="date" name="date" value="<?= e($filters['date']) ?>" aria-label="Screening date" data-tip="Screening date">
    <button class="btn btn-ghost" type="submit">Filter</button>
    <?php if (array_filter($filters)): ?><a class="btn btn-ghost" href="<?= url('/admin/bookings') ?>">Clear</a><?php endif; ?>
</form>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>#</th><th>Customer</th><th>Movie / screening</th><th>Seats</th><th>Booked on</th><th>Status</th><th>Payment</th><th class="num">Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><a href="<?= url('/admin/bookings/' . (int) $b['booking_id']) ?>"><?= (int) $b['booking_id'] ?></a></td>
                <td><?= e($b['full_name']) ?><br><span class="muted small"><?= e($b['email']) ?></span></td>
                <td><?= e($b['title']) ?><br><span class="muted small"><?= e(fmt_date($b['show_date'])) ?> · <?= e(fmt_time($b['start_time'])) ?> · <?= e($b['hall_name']) ?></span></td>
                <td class="small"><?= $b['seat_list'] ? e($b['seat_list']) : '<span class="muted">—</span>' ?></td>
                <td class="small"><?= e(date('j M Y, H:i', strtotime($b['booking_date']))) ?></td>
                <td><?= status_badge($b['status']) ?></td>
                <td><?= status_badge($b['payment_status']) ?></td>
                <td class="num"><?= e(money($b['total_amount'])) ?></td>
                <td><a class="icon-btn" href="<?= url('/admin/bookings/' . (int) $b['booking_id']) ?>" data-tip="Details" aria-label="Details"><?= icon('eye', 16) ?></a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$bookings): ?><tr><td colspan="9" class="center muted">No bookings found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
