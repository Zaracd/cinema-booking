<?php $me = App\Core\Auth::id(); ?>
<form class="filters" method="get" action="<?= url('/admin/users') ?>">
    <label class="filter-search"><?= icon('search') ?><input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, email or phone" aria-label="Search users"></label>
    <select name="role" aria-label="Role">
        <option value="">All roles</option>
        <option <?= $filters['role'] === 'Customer' ? 'selected' : '' ?>>Customer</option>
        <option <?= $filters['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    <button class="btn btn-ghost" type="submit">Filter</button>
</form>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th><th class="num">Bookings</th><th>Role</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): $self = (int) $u['user_id'] === $me; ?>
            <tr>
                <td><strong><?= e($u['full_name']) ?></strong><?= $self ? ' <span class="muted small">(you)</span>' : '' ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['phone']) ?></td>
                <td class="small"><?= e(date('j M Y', strtotime($u['created_at']))) ?></td>
                <td class="num"><a href="<?= url('/admin/bookings', ['q' => $u['email']]) ?>"><?= (int) $u['bookings_count'] ?></a></td>
                <td>
                    <?php if ($self): ?>
                        <?= status_badge($u['role']) ?>
                    <?php else: ?>
                        <form method="post" action="<?= url('/admin/users/' . (int) $u['user_id'] . '/role') ?>" class="inline">
                            <?= App\Core\Csrf::field() ?>
                            <select name="role" data-autosubmit aria-label="Change role" data-tip="Change permissions">
                                <option <?= $u['role'] === 'Customer' ? 'selected' : '' ?>>Customer</option>
                                <option <?= $u['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </form>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$self): ?>
                        <form method="post" action="<?= url('/admin/users/' . (int) $u['user_id'] . '/delete') ?>" class="inline"
                              data-confirm="Delete <?= e($u['full_name']) ?>? Their bookings will be deleted too.">
                            <?= App\Core\Csrf::field() ?>
                            <button class="icon-btn danger" type="submit" data-tip="Delete user" aria-label="Delete user"><?= icon('trash', 16) ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="7" class="center muted">No users found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
