<?php /** @var array $m */ ?>
<article class="movie-card">
    <a href="<?= url('/movies/' . (int) $m['movie_id']) ?>" class="poster-link">
        <img src="<?= e(poster_url($m['poster'])) ?>" alt="<?= e($m['title']) ?> poster" loading="lazy">
        <?php if ((float) $m['rating'] > 0): ?>
            <span class="rating-chip" data-tip="Rating out of 10"><?= icon('star', 14) ?> <?= e(number_format((float) $m['rating'], 1)) ?></span>
        <?php endif; ?>
        <?php if ($m['status'] !== 'Now Showing'): ?>
            <span class="status-chip"><?= e($m['status']) ?></span>
        <?php endif; ?>
    </a>
    <div class="movie-card-body">
        <h3><a href="<?= url('/movies/' . (int) $m['movie_id']) ?>"><?= e($m['title']) ?></a></h3>
        <p class="muted small"><?= e($m['genres'] ?? '') ?> · <?= (int) $m['duration'] ?> min</p>
        <?php if ($m['status'] === 'Now Showing'): ?>
            <a class="btn btn-sm btn-block" href="<?= url('/movies/' . (int) $m['movie_id']) ?>#showtimes"><?= icon('ticket', 16) ?> Book tickets</a>
        <?php else: ?>
            <a class="btn btn-sm btn-ghost btn-block" href="<?= url('/movies/' . (int) $m['movie_id']) ?>">Details</a>
        <?php endif; ?>
    </div>
</article>
