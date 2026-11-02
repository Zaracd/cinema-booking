<?php $hero = $nowShowing[0] ?? null; ?>
<section class="hero">
    <?php if ($hero): ?>
        <div class="hero-bg" style="background-image:url('<?= e(poster_url($hero['poster'])) ?>')"></div>
    <?php endif; ?>
    <div class="container hero-inner">
        <div class="hero-text">
            <p class="eyebrow">Book online · Choose your seat</p>
            <h1>Your next movie night starts here.</h1>
            <p class="lead">See what's playing, check screening times, pick the exact seats you want and get your ticket in under a minute.</p>
            <form class="hero-search" action="<?= url('/movies') ?>" method="get" role="search">
                <?= icon('search') ?>
                <input type="search" name="q" placeholder="Search for a movie..." aria-label="Search movies" maxlength="100">
                <button class="btn" type="submit">Search</button>
            </form>
            <div class="genre-pills">
                <?php foreach (array_slice($genres, 0, 8) as $g): ?>
                    <a class="pill" href="<?= url('/movies', ['genre' => $g['genre_id']]) ?>"><?= e($g['genre_name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if ($hero): ?>
            <a class="hero-poster" href="<?= url('/movies/' . (int) $hero['movie_id']) ?>" data-tip="<?= e($hero['title']) ?>">
                <img src="<?= e(poster_url($hero['poster'])) ?>" alt="<?= e($hero['title']) ?> poster">
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="container section">
    <div class="section-head">
        <h2>Now showing</h2>
        <a href="<?= url('/movies', ['status' => 'Now Showing']) ?>" class="link-arrow">All movies <?= icon('arrow', 16) ?></a>
    </div>
    <?php if ($nowShowing): ?>
        <div class="movie-grid">
            <?php foreach ($nowShowing as $m) { require ROOT . '/app/Views/partials/movie-card.php'; } ?>
        </div>
    <?php else: ?>
        <p class="empty">No movies are showing right now. Check back soon!</p>
    <?php endif; ?>
</section>

<section class="steps">
    <div class="container steps-inner">
        <div class="step"><span class="step-icon"><?= icon('film', 26) ?></span><h3>1. Pick a movie</h3><p>Browse what's on and read the details.</p></div>
        <div class="step"><span class="step-icon"><?= icon('clock', 26) ?></span><h3>2. Choose a time</h3><p>See every screening with hall and price.</p></div>
        <div class="step"><span class="step-icon"><?= icon('grid', 26) ?></span><h3>3. Select seats</h3><p>Live seat map shows what's still free.</p></div>
        <div class="step"><span class="step-icon"><?= icon('ticket', 26) ?></span><h3>4. Pay &amp; enjoy</h3><p>Get a confirmation by email instantly.</p></div>
    </div>
</section>

<?php if ($comingSoon): ?>
<section class="container section">
    <div class="section-head">
        <h2>Coming soon</h2>
        <a href="<?= url('/movies', ['status' => 'Coming Soon']) ?>" class="link-arrow">See all <?= icon('arrow', 16) ?></a>
    </div>
    <div class="movie-grid">
        <?php foreach ($comingSoon as $m) { require ROOT . '/app/Views/partials/movie-card.php'; } ?>
    </div>
</section>
<?php endif; ?>
