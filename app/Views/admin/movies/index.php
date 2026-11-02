<div class="page-head">
    <form class="filters" method="get" action="<?= url('/admin/movies') ?>" style="margin:0;flex:1">
        <label class="filter-search"><?= icon('search') ?><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search title" aria-label="Search title"></label>
        <select name="genre" aria-label="Genre">
            <option value="">All genres</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= (int) $g['genre_id'] ?>" <?= (int) $filters['genre'] === (int) $g['genre_id'] ? 'selected' : '' ?>><?= e($g['genre_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" aria-label="Status">
            <option value="">Any status</option>
            <?php foreach (App\Models\Movie::STATUSES as $s): ?><option <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit">Filter</button>
    </form>
    <a class="btn" href="<?= url('/admin/movies/create') ?>"><?= icon('plus', 16) ?> Add movie</a>
</div>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th></th><th>Title</th><th>Genres</th><th>Duration</th><th>Release</th><th>Rating</th><th>Status</th><th class="num">Actions</th></tr></thead>
        <tbody>
        <?php foreach ($movies as $m): ?>
            <tr>
                <td><img class="thumb" src="<?= e(poster_url($m['poster'])) ?>" alt=""></td>
                <td><strong><?= e($m['title']) ?></strong><br><span class="muted small"><?= e($m['director']) ?></span></td>
                <td class="small"><?= e($m['genres']) ?></td>
                <td><?= (int) $m['duration'] ?> min</td>
                <td><?= e(date('j M Y', strtotime($m['release_date']))) ?></td>
                <td><?= (float) $m['rating'] > 0 ? e(number_format((float) $m['rating'], 1)) : '—' ?></td>
                <td>
                    <form method="post" action="<?= url('/admin/movies/' . (int) $m['movie_id'] . '/status') ?>" class="inline">
                        <?= App\Core\Csrf::field() ?>
                        <select name="status" data-autosubmit aria-label="Change status" data-tip="Change status">
                            <?php foreach (App\Models\Movie::STATUSES as $s): ?><option <?= $m['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td>
                    <div class="actions-cell">
                        <a class="icon-btn" href="<?= url('/movies/' . (int) $m['movie_id']) ?>" data-tip="View on website" aria-label="View"><?= icon('eye', 16) ?></a>
                        <a class="icon-btn" href="<?= url('/admin/movies/' . (int) $m['movie_id'] . '/edit') ?>" data-tip="Edit" aria-label="Edit"><?= icon('edit', 16) ?></a>
                        <form method="post" action="<?= url('/admin/movies/' . (int) $m['movie_id'] . '/delete') ?>" class="inline" data-confirm="Delete &quot;<?= e($m['title']) ?>&quot;? This cannot be undone.">
                            <?= App\Core\Csrf::field() ?>
                            <button class="icon-btn danger" type="submit" data-tip="Delete" aria-label="Delete"><?= icon('trash', 16) ?></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$movies): ?><tr><td colspan="8" class="center muted">No movies found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
