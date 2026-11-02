<?php $ok = flash('success'); $err = flash('error'); ?>
<?php if ($ok || $err): ?>
<div class="flash-wrap container">
    <?php if ($ok): ?>
        <div class="flash flash-success" role="status"><?= icon('check') ?><span><?= e($ok) ?></span><button type="button" class="flash-close" data-tip="Dismiss" aria-label="Dismiss"><?= icon('x', 16) ?></button></div>
    <?php endif; ?>
    <?php if ($err): ?>
        <div class="flash flash-error" role="alert"><?= icon('x') ?><span><?= e($err) ?></span><button type="button" class="flash-close" data-tip="Dismiss" aria-label="Dismiss"><?= icon('x', 16) ?></button></div>
    <?php endif; ?>
</div>
<?php endif; ?>
