<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.string' => 'メールアドレスは文字列を入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.string' => 'パスワードは文字列を入力してください',
        ];
    }
}
