<?php $b = $booking; ?>
<a class="back-link" href="<?= url('/admin/bookings') ?>"><?= icon('back', 16) ?> All bookings</a>
<div class="dash-grid">
    <div class="card">
        <h2>Booking #<?= (int) $b['booking_id'] ?> <?= status_badge($b['status']) ?> <?= status_badge($b['payment_status']) ?></h2>
        <dl class="kv">
            <dt>Movie</dt><dd><?= e($b['title']) ?></dd>
            <dt>Screening</dt><dd><?= e(fmt_date($b['show_date'])) ?>, <?= e(fmt_time($b['start_time'])) ?> – <?= e(fmt_time($b['end_time'])) ?></dd>
            <dt>Hall</dt><dd><?= e($b['hall_name']) ?></dd>
            <dt>Booked on</dt><dd><?= e(date('j M Y, H:i', strtotime($b['booking_date']))) ?></dd>
            <dt>Total</dt><dd><strong><?= e(money($b['total_amount'])) ?></strong></dd>
        </dl>
        <h3 style="margin-top:20px">Seats</h3>
        <?php if ($seats): ?>
            <table class="price-table">
                <?php foreach ($seats as $s): ?>
                    <tr><td><?= e($s['row_label'] . $s['seat_number']) ?> <span class="muted small"><?= e($s['seat_type']) ?></span></td><td class="num"><?= e(money($s['price'])) ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="muted">Seats were released when this booking was cancelled.</p>
        <?php endif; ?>
    </div>
    <div class="card" style="align-self:start">
        <h2>Customer</h2>
        <dl class="kv">
            <dt>Name</dt><dd><?= e($b['full_name']) ?></dd>
            <dt>Email</dt><dd><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></dd>
            <dt>Phone</dt><dd><?= e($b['phone']) ?></dd>
        </dl>
        <?php if ($b['status'] !== 'Cancelled'): ?>
            <form method="post" action="<?= url('/admin/bookings/' . (int) $b['booking_id'] . '/cancel') ?>" style="margin-top:20px"
                  data-confirm="Cancel booking #<?= (int) $b['booking_id'] ?>? The seats will be released and the customer emailed.">
                <?= App\Core\Csrf::field() ?>
                <button class="btn btn-danger btn-block" type="submit"><?= icon('x', 16) ?> Cancel booking</button>
            </form>
        <?php endif; ?>
    </div>
</div>
