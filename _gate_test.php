<?php
use App\Models\User;

foreach (User::where('is_admin', true)->get() as $u) {
    echo "{$u->id}\t{$u->email}\trole={$u->role}\tsuper=" . ($u->isSuperAdmin() ? 'yes' : 'no') . PHP_EOL;
}
echo 'super_admin count=' . User::where('role', 'super_admin')->count() . PHP_EOL;
