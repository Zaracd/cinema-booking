<section class="container section booking-page">
    <a class="back-link" href="<?= url('/movies/' . (int) $show['movie_id']) ?>#showtimes"><?= icon('back', 16) ?> Back to screenings</a>
    <div class="booking-layout">
        <div class="seat-panel card">
            <div class="screen-wrap"><div class="screen">SCREEN</div></div>
            <div id="seat-map" class="seat-map" data-url="<?= url('/api/showtimes/' . (int) $show['showtime_id'] . '/seats') ?>" aria-label="Seat map">
                <p class="muted center">Loading seats...</p>
            </div>
            <div class="legend">
                <span><i class="seat-demo"></i> Available</span>
                <span><i class="seat-demo vip"></i> VIP (×<?= e(config('app.vip_multiplier')) ?>)</span>
                <span><i class="seat-demo selected"></i> Selected</span>
                <span><i class="seat-demo booked"></i> Booked</span>
            </div>
        </div>

        <aside class="summary card">
            <img class="summary-poster" src="<?= e(poster_url($show['poster'])) ?>" alt="">
            <h2><?= e($show['title']) ?></h2>
            <ul class="summary-list">
                <li><?= icon('calendar', 16) ?> <?= e(fmt_date($show['show_date'])) ?></li>
                <li><?= icon('clock', 16) ?> <?= e(fmt_time($show['start_time'])) ?> – <?= e(fmt_time($show['end_time'])) ?></li>
                <li><?= icon('door', 16) ?> <?= e($show['hall_name']) ?></li>
                <li><?= icon('ticket', 16) ?> <?= e(money($show['ticket_price'])) ?> per seat</li>
            </ul>
            <div class="summary-seats">
                <span class="muted">Your seats</span>
                <p id="selected-list" class="selected-list">None selected</p>
            </div>
            <div class="summary-total">
                <span>Total (<span id="ticket-count">0</span> tickets)</span>
                <strong id="total-price">0.00 <?= e(config('app.currency')) ?></strong>
            </div>
            <form method="post" action="<?= url('/bookings') ?>" id="booking-form">
                <?= App\Core\Csrf::field() ?>
                <input type="hidden" name="showtime_id" value="<?= (int) $show['showtime_id'] ?>">
                <input type="hidden" name="seat_ids" id="seat-ids" value="">
                <button class="btn btn-block" type="submit" id="book-btn" disabled>Continue to payment <?= icon('arrow', 16) ?></button>
            </form>
            <p class="muted small center" id="seat-status" aria-live="polite">Up to 10 seats per booking. The map refreshes automatically.</p>
        </aside>
    </div>
</section>
<script src="<?= asset('assets/js/seats.js') ?>"></script>
