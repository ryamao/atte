<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * 会員に関する操作を行うサービスクラス
 */
class UserService
{
    /**
     * 会員の名前を検索する。
     *
     * @param  string  $search  検索文字列
     */
    public function searchUserNames(string $search): Builder
    {
        $search = addcslashes($search, '%_\\');

        return User::select(['id', 'name'])
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->orderBy('id');
    }
}
