<?php $b = $booking; $confirmed = $b['status'] === 'Confirmed'; ?>
<section class="container section narrow">
    <?php if ($confirmed): ?>
        <div class="confirm-head">
            <span class="confirm-icon"><?= icon('check', 34) ?></span>
            <h1>You're all set!</h1>
            <p class="muted">A confirmation was sent to <strong><?= e($b['email']) ?></strong>.</p>
        </div>
    <?php else: ?>
        <div class="page-head"><h1>Booking #<?= (int) $b['booking_id'] ?></h1></div>
    <?php endif; ?>

    <div class="ticket">
        <div class="ticket-main">
            <img src="<?= e(poster_url($b['poster'])) ?>" alt="" class="ticket-poster">
            <div>
                <p class="eyebrow">Booking number</p>
                <p class="booking-no">#<?= str_pad((string) $b['booking_id'], 6, '0', STR_PAD_LEFT) ?></p>
                <h2><?= e($b['title']) ?></h2>
                <p><?= status_badge($b['status']) ?> <?= status_badge($b['payment_status']) ?></p>
            </div>
        </div>
        <dl class="ticket-grid">
            <div><dt>Date</dt><dd><?= e(fmt_date($b['show_date'])) ?></dd></div>
            <div><dt>Time</dt><dd><?= e(fmt_time($b['start_time'])) ?></dd></div>
            <div><dt>Hall</dt><dd><?= e($b['hall_name']) ?></dd></div>
            <div><dt>Tickets</dt><dd><?= $b['status'] === 'Cancelled' ? '—' : (int) $b['ticket_count'] ?></dd></div>
            <div class="wide"><dt>Seats</dt><dd><?= $b['seat_list'] ? e($b['seat_list']) : '<span class="muted">Released</span>' ?></dd></div>
            <div><dt>Total</dt><dd><strong><?= e(money($b['total_amount'])) ?></strong></dd></div>
        </dl>
    </div>

    <div class="actions">
        <?php if ($b['status'] === 'Pending'): ?>
            <a class="btn" href="<?= url('/bookings/' . (int) $b['booking_id'] . '/pay') ?>"><?= icon('card', 16) ?> Complete payment</a>
        <?php endif; ?>
        <?php if ($confirmed): ?>
            <button class="btn btn-ghost" type="button" onclick="window.print()" data-tip="Print or save as PDF"><?= icon('printer', 16) ?> Print ticket</button>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= url('/account/bookings') ?>">My bookings</a>
        <?php if ($canCancel): ?>
            <form method="post" action="<?= url('/bookings/' . (int) $b['booking_id'] . '/cancel') ?>" class="inline"
                  data-confirm="Cancel this booking? Your seats will be released<?= $b['payment_status'] === 'Paid' ? ' and your payment refunded' : '' ?>.">
                <?= App\Core\Csrf::field() ?>
                <button class="btn btn-danger" type="submit"><?= icon('x', 16) ?> Cancel booking</button>
            </form>
        <?php elseif ($b['status'] !== 'Cancelled'): ?>
            <p class="muted small">Cancellation closes <?= (int) config('app.cancel_hours') ?> hours before the screening.</p>
        <?php endif; ?>
    </div>
</section>
