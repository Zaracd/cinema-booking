<?php
$render = function (array $list, bool $upcoming) {
    foreach ($list as $b): ?>
        <article class="booking-row card">
            <img src="<?= e(poster_url($b['poster'])) ?>" alt="" class="booking-thumb">
            <div class="booking-info">
                <h3><a href="<?= url('/bookings/' . (int) $b['booking_id']) ?>"><?= e($b['title']) ?></a></h3>
                <p class="muted small">#<?= (int) $b['booking_id'] ?> · <?= e(fmt_date($b['show_date'])) ?> · <?= e(fmt_time($b['start_time'])) ?> · <?= e($b['hall_name']) ?></p>
                <p class="small">Seats: <?= $b['seat_list'] ? e($b['seat_list']) : '<span class="muted">released</span>' ?></p>
            </div>
            <div class="booking-side">
                <div><?= status_badge($b['status']) ?> <?= status_badge($b['payment_status']) ?></div>
                <strong><?= e(money($b['total_amount'])) ?></strong>
                <div class="row-actions">
                    <?php if ($b['status'] === 'Pending'): ?>
                        <a class="btn btn-sm" href="<?= url('/bookings/' . (int) $b['booking_id'] . '/pay') ?>">Pay now</a>
                    <?php endif; ?>
                    <a class="icon-btn" href="<?= url('/bookings/' . (int) $b['booking_id']) ?>" data-tip="View ticket" aria-label="View ticket"><?= icon('eye') ?></a>
                    <?php if ($upcoming && $b['can_cancel']): ?>
                        <form method="post" action="<?= url('/bookings/' . (int) $b['booking_id'] . '/cancel') ?>" class="inline" data-confirm="Cancel booking #<?= (int) $b['booking_id'] ?>? Your seats will be released.">
                            <?= App\Core\Csrf::field() ?>
                            <button class="icon-btn danger" type="submit" data-tip="Cancel booking" aria-label="Cancel booking"><?= icon('trash') ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach;
};
?>
<section class="container section">
    <div class="page-head">
        <h1>My bookings</h1>
        <a class="btn" href="<?= url('/movies') ?>"><?= icon('ticket', 16) ?> Book another movie</a>
    </div>

    <h2 class="sub-head">Upcoming <span class="count"><?= count($upcoming) ?></span></h2>
    <?php if ($upcoming): ?>
        <div class="booking-list"><?php $render($upcoming, true); ?></div>
    <?php else: ?>
        <p class="empty">You have no upcoming bookings. <a href="<?= url('/movies') ?>">Find a movie</a></p>
    <?php endif; ?>

    <h2 class="sub-head">Past <span class="count"><?= count($past) ?></span></h2>
    <?php if ($past): ?>
        <div class="booking-list past"><?php $render($past, false); ?></div>
    <?php else: ?>
        <p class="empty">No past bookings yet.</p>
    <?php endif; ?>
</section>
