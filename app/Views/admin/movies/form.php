<?php
$val = fn (string $k, $default = '') => old($k, $movie[$k] ?? $default);
$action = $movie ? url('/admin/movies/' . (int) $movie['movie_id']) : url('/admin/movies');
?>
<a class="back-link" href="<?= url('/admin/movies') ?>"><?= icon('back', 16) ?> All movies</a>
<div class="card" style="max-width:860px">
    <form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="form js-validate" novalidate>
        <?= App\Core\Csrf::field() ?>
        <label class="field <?= isset($errors['title']) ? 'invalid' : '' ?>">
            <span>Title</span>
            <input type="text" name="title" value="<?= e($val('title')) ?>" required maxlength="150">
            <?= field_error($errors, 'title') ?>
        </label>
        <div class="field-row">
            <label class="field <?= isset($errors['original_title']) ? 'invalid' : '' ?>">
                <span>Arabic / original title (optional)</span>
                <input type="text" name="original_title" value="<?= e($val('original_title')) ?>" maxlength="150" dir="auto">
                <?= field_error($errors, 'original_title') ?>
            </label>
            <label class="field <?= isset($errors['language']) ? 'invalid' : '' ?>">
                <span>Language</span>
                <input type="text" name="language" value="<?= e($val('language', 'English')) ?>" required maxlength="50" list="languages">
                <datalist id="languages"><option>English</option><option>Arabic</option><option>Arabic / English</option></datalist>
                <?= field_error($errors, 'language') ?>
            </label>
        </div>
        <label class="field <?= isset($errors['description']) ? 'invalid' : '' ?>">
            <span>Description</span>
            <textarea name="description" required dir="auto"><?= e($val('description')) ?></textarea>
            <?= field_error($errors, 'description') ?>
        </label>
        <div class="field-row three">
            <label class="field <?= isset($errors['duration']) ? 'invalid' : '' ?>">
                <span>Duration (minutes)</span>
                <input type="number" name="duration" value="<?= e($val('duration')) ?>" required min="30" max="400" step="1">
                <?= field_error($errors, 'duration') ?>
            </label>
            <label class="field <?= isset($errors['release_date']) ? 'invalid' : '' ?>">
                <span>Release date</span>
                <input type="date" name="release_date" value="<?= e($val('release_date')) ?>" required>
                <?= field_error($errors, 'release_date') ?>
            </label>
            <label class="field <?= isset($errors['rating']) ? 'invalid' : '' ?>">
                <span>Rating (0–10)</span>
                <input type="number" name="rating" value="<?= e($val('rating', '0')) ?>" required min="0" max="10" step="0.1">
                <?= field_error($errors, 'rating') ?>
            </label>
        </div>
        <div class="field-row">
            <label class="field <?= isset($errors['director']) ? 'invalid' : '' ?>">
                <span>Director</span>
                <input type="text" name="director" value="<?= e($val('director')) ?>" required maxlength="100">
                <?= field_error($errors, 'director') ?>
            </label>
            <label class="field <?= isset($errors['status']) ? 'invalid' : '' ?>">
                <span>Status</span>
                <select name="status" required>
                    <?php foreach (App\Models\Movie::STATUSES as $s): ?><option <?= $val('status', 'Coming Soon') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label class="field <?= isset($errors['starring']) ? 'invalid' : '' ?>">
            <span>Starring (optional, names separated by commas)</span>
            <input type="text" name="starring" value="<?= e($val('starring')) ?>" maxlength="255">
            <?= field_error($errors, 'starring') ?>
        </label>
        <div class="field <?= isset($errors['genres']) ? 'invalid' : '' ?>">
            <span>Genres</span>
            <div class="genre-checks">
                <?php foreach ($genres as $g): ?>
                    <label><input type="checkbox" name="genres[]" value="<?= (int) $g['genre_id'] ?>" <?= in_array((int) $g['genre_id'], $selected, true) ? 'checked' : '' ?>> <?= e($g['genre_name']) ?></label>
                <?php endforeach; ?>
            </div>
            <?= field_error($errors, 'genres') ?>
        </div>
        <div class="field-row">
            <label class="field <?= isset($errors['poster']) ? 'invalid' : '' ?>">
                <span>Poster image (JPG, PNG or WebP, max 2 MB)</span>
                <input type="file" name="poster" accept="image/jpeg,image/png,image/webp">
                <?= field_error($errors, 'poster') ?>
            </label>
            <label class="field <?= isset($errors['poster_url']) ? 'invalid' : '' ?>">
                <span>…or poster link</span>
                <input type="url" name="poster_url" value="<?= e(old('poster_url', preg_match('#^https?://#', (string) ($movie['poster'] ?? '')) ? $movie['poster'] : '')) ?>" placeholder="https://...">
                <?= field_error($errors, 'poster_url') ?>
            </label>
        </div>
        <?php if (!empty($movie['poster'])): ?>
            <div><span class="muted small">Current poster</span><img class="poster-preview" src="<?= e(poster_url($movie['poster'])) ?>" alt=""></div>
        <?php endif; ?>
        <div class="actions">
            <button class="btn" type="submit"><?= icon('check', 16) ?> <?= $movie ? 'Save changes' : 'Add movie' ?></button>
            <a class="btn btn-ghost" href="<?= url('/admin/movies') ?>">Cancel</a>
        </div>
    </form>
</div>

<div class="card" style="max-width:860px;margin-top:18px">
    <h2>Add a new genre</h2>
    <form method="post" action="<?= url('/admin/genres') ?>" class="filters" style="margin:0">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="back" value="<?= $movie ? 'list' : 'create' ?>">
        <input type="text" name="genre_name" placeholder="e.g. Documentary" maxlength="50" required aria-label="Genre name" style="flex:1">
        <button class="btn btn-ghost" type="submit"><?= icon('plus', 16) ?> Add genre</button>
    </form>
</div>
