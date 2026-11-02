<section class="container section">
    <div class="page-head">
        <h1>Movies</h1>
        <p class="muted"><?= count($movies) ?> movie<?= count($movies) === 1 ? '' : 's' ?> found</p>
    </div>

    <form class="filters" method="get" action="<?= url('/movies') ?>">
        <label class="filter-search">
            <?= icon('search') ?>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by title" maxlength="100" aria-label="Search by title">
        </label>
        <select name="genre" aria-label="Genre">
            <option value="">All genres</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= (int) $g['genre_id'] ?>" <?= (int) $filters['genre'] === (int) $g['genre_id'] ? 'selected' : '' ?>><?= e($g['genre_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
            <option value="">Any status</option>
            <?php foreach (App\Models\Movie::STATUSES as $s): ?>
                <option <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="date" value="<?= e($filters['date']) ?>" aria-label="Screening date" data-tip="Show movies screening on this date">
        <button class="btn" type="submit">Filter</button>
        <?php if (array_filter($filters)): ?>
            <a class="btn btn-ghost" href="<?= url('/movies') ?>">Clear</a>
        <?php endif; ?>
    </form>

    <?php if ($movies): ?>
        <div class="movie-grid">
            <?php foreach ($movies as $m) { require ROOT . '/app/Views/partials/movie-card.php'; } ?>
        </div>
    <?php else: ?>
        <div class="empty">
            <?= icon('film', 40) ?>
            <p>No movies match your search. Try another title or clear the filters.</p>
        </div>
    <?php endif; ?>
</section>
