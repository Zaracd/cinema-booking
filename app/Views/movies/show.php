<section class="movie-hero">
    <div class="movie-hero-bg" style="background-image:url('<?= e(poster_url($movie['poster'])) ?>')"></div>
    <div class="container movie-hero-inner">
        <img class="movie-poster" src="<?= e(poster_url($movie['poster'])) ?>" alt="<?= e($movie['title']) ?> poster">
        <div class="movie-info">
            <a class="back-link" href="<?= url('/movies') ?>"><?= icon('back', 16) ?> All movies</a>
            <?= status_badge($movie['status']) ?>
            <h1><?= e($movie['title']) ?></h1>
            <?php if (!empty($movie['original_title'])): ?>
                <p class="original-title" lang="ar" dir="rtl"><?= e($movie['original_title']) ?></p>
            <?php endif; ?>
            <ul class="meta-list">
                <?php if ((float) $movie['rating'] > 0): ?>
                    <li data-tip="IMDb rating out of 10"><?= icon('star', 16) ?> <?= e(number_format((float) $movie['rating'], 1)) ?>/10</li>
                <?php endif; ?>
                <li data-tip="Duration"><?= icon('clock', 16) ?> <?= intdiv((int) $movie['duration'], 60) ?>h <?= (int) $movie['duration'] % 60 ?>m</li>
                <li data-tip="Release date"><?= icon('calendar', 16) ?> <?= e(fmt_date($movie['release_date'])) ?></li>
                <li data-tip="Language"><?= icon('film', 16) ?> <?= e($movie['language']) ?></li>
            </ul>
            <p class="genres"><?php foreach (explode(', ', (string) $movie['genres']) as $g): if ($g): ?><span class="pill"><?= e($g) ?></span><?php endif; endforeach; ?></p>
            <?php foreach (preg_split('/\R+/u',trim($movie['description'])) as $para): ?>
                <p class="description" dir="auto"><?= e($para) ?></p>
            <?php endforeach; ?>
            <p><span class="muted">Director:</span> <strong><?= e($movie['director']) ?></strong></p>
            <?php if (!empty($movie['starring'])): ?>
                <p><span class="muted">Starring:</span> <?= e($movie['starring']) ?></p>
            <?php endif; ?>
            <p class="movie-links">
                <a class="btn btn-sm btn-ghost" target="_blank" rel="noopener" href="https://www.imdb.com/find/?q=<?= urlencode($movie['title'] . ' ' . substr($movie['release_date'], 0, 4)) ?>"><?= icon('star', 14) ?> IMDb page</a>
                <a class="btn btn-sm btn-ghost" target="_blank" rel="noopener" href="https://www.youtube.com/results?search_query=<?= urlencode($movie['title'] . ' ' . substr($movie['release_date'], 0, 4) . ' trailer') ?>"><?= icon('film', 14) ?> Watch trailer</a>
            </p>
        </div>
    </div>
</section>

<section class="container section" id="showtimes">
    <h2>Screening times</h2>
    <?php if (!$byDate): ?>
        <div class="empty">
            <?= icon('calendar', 40) ?>
            <p><?= $movie['status'] === 'Coming Soon' ? 'Screenings for this movie will be announced soon.' : 'There are no upcoming screenings for this movie.' ?></p>
        </div>
    <?php else: ?>
        <div class="date-tabs" role="tablist">
            <?php $i = 0; foreach ($byDate as $date => $list): ?>
                <button type="button" role="tab" class="date-tab <?= $i === 0 ? 'active' : '' ?>" data-date="<?= e($date) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                    <span class="dt-day"><?= $date === date('Y-m-d') ? 'Today' : ($date === date('Y-m-d', strtotime('+1 day')) ? 'Tomorrow' : date('D', strtotime($date))) ?></span>
                    <span class="dt-date"><?= date('j M', strtotime($date)) ?></span>
                </button>
            <?php $i++; endforeach; ?>
        </div>
        <?php $i = 0; foreach ($byDate as $date => $list): ?>
            <div class="showtime-list <?= $i === 0 ? '' : 'hidden' ?>" data-date="<?= e($date) ?>" role="tabpanel">
                <?php foreach ($list as $s): $left = (int) $s['capacity'] - (int) $s['booked_count']; ?>
                    <a class="showtime <?= $left <= 0 ? 'sold-out' : '' ?>" href="<?= $left > 0 ? url('/showtimes/' . (int) $s['showtime_id'] . '/seats') : '#' ?>">
                        <span class="st-time"><?= e(fmt_time($s['start_time'])) ?></span>
                        <span class="st-hall"><?= icon('door', 14) ?> <?= e($s['hall_name']) ?></span>
                        <span class="st-price"><?= e(money($s['ticket_price'])) ?></span>
                        <span class="st-left <?= $left < 10 ? 'low' : '' ?>"><?= $left > 0 ? $left . ' seats left' : 'Sold out' ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php $i++; endforeach; ?>
    <?php endif; ?>
</section>
