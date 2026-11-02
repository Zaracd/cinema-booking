<section class="container section booking-page">
    <div class="page-head">
        <h1>Review &amp; pay</h1>
        <p class="hold-timer" data-seconds="<?= (int) $secondsLeft ?>" data-tip="Your seats are held for you until the timer ends">
            <?= icon('clock', 16) ?> Seats held for <strong id="hold-left"><?= sprintf('%d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60) ?></strong>
        </p>
    </div>
    <div class="booking-layout">
        <div class="card">
            <h2><?= icon('card') ?> Payment details</h2>
            <p class="notice"><?= icon('shield', 16) ?> This is a payment <strong>simulation</strong> for the project. No money is charged and card details are not saved. Test card: <code>4242 4242 4242 4242</code>, any future date, any 3 digits.</p>
            <form method="post" action="<?= url('/bookings/' . (int) $booking['booking_id'] . '/pay') ?>" class="form js-validate" novalidate id="pay-form">
                <?= App\Core\Csrf::field() ?>
                <label class="field <?= isset($errors['card_name']) ? 'invalid' : '' ?>">
                    <span>Name on card</span>
                    <input type="text" name="card_name" value="<?= e(old('card_name', $booking['full_name'])) ?>" required maxlength="100" autocomplete="cc-name">
                    <?= field_error($errors, 'card_name') ?>
                </label>
                <label class="field <?= isset($errors['card_number']) ? 'invalid' : '' ?>">
                    <span>Card number</span>
                    <input type="text" name="card_number" inputmode="numeric" required placeholder="1234 5678 9012 3456" maxlength="23"
                           autocomplete="cc-number" data-card data-msg="Enter a valid card number.">
                    <?= field_error($errors, 'card_number') ?>
                </label>
                <div class="field-row">
                    <label class="field <?= isset($errors['expiry']) ? 'invalid' : '' ?>">
                        <span>Expiry (MM/YY)</span>
                        <input type="text" name="expiry" value="<?= e(old('expiry')) ?>" required placeholder="MM/YY" maxlength="5" pattern="(0[1-9]|1[0-2])\/\d{2}"
                               autocomplete="cc-exp" data-expiry data-msg="Use the format MM/YY.">
                        <?= field_error($errors, 'expiry') ?>
                    </label>
                    <label class="field <?= isset($errors['cvv']) ? 'invalid' : '' ?>">
                        <span>CVV</span>
                        <input type="password" name="cvv" required inputmode="numeric" pattern="\d{3,4}" maxlength="4" autocomplete="cc-csc" data-msg="CVV is 3 or 4 digits.">
                        <?= field_error($errors, 'cvv') ?>
                    </label>
                </div>
                <button class="btn btn-block btn-lg" type="submit"><?= icon('check', 18) ?> Pay <?= e(money($booking['total_amount'])) ?></button>
            </form>
        </div>

        <aside class="summary card">
            <img class="summary-poster" src="<?= e(poster_url($booking['poster'])) ?>" alt="">
            <h2><?= e($booking['title']) ?></h2>
            <ul class="summary-list">
                <li><?= icon('calendar', 16) ?> <?= e(fmt_date($booking['show_date'])) ?></li>
                <li><?= icon('clock', 16) ?> <?= e(fmt_time($booking['start_time'])) ?></li>
                <li><?= icon('door', 16) ?> <?= e($booking['hall_name']) ?></li>
            </ul>
            <table class="price-table">
                <?php foreach ($seats as $s): ?>
                    <tr><td>Seat <?= e($s['row_label'] . $s['seat_number']) ?> <span class="muted small"><?= e($s['seat_type']) ?></span></td><td class="num"><?= e(money($s['price'])) ?></td></tr>
                <?php endforeach; ?>
                <tr class="total"><td>Total</td><td class="num"><?= e(money($booking['total_amount'])) ?></td></tr>
            </table>
            <a class="btn btn-ghost btn-block" href="<?= url('/showtimes/' . (int) $booking['showtime_id'] . '/seats') ?>" data-tip="Your current hold will expire on its own">Change seats</a>
        </aside>
    </div>
</section>
