<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    protected static string $layout = 'filament.pages.auth.layout';

    protected static string $view = 'filament.pages.auth.login';

    public function getTitle(): string|Htmlable
    {
        return 'Admin Sign In — RESET Studio';
    }

    public function getHeading(): string|Htmlable
    {
        return 'ADMIN SIGN IN';
    }

    public function getSubHeading(): string|Htmlable|null
    {
        return 'Authorized access for store management & operations.';
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('ADMIN EMAIL')
            ->email()
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->placeholder('admin@reset.store')
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('PASSWORD')
            ->password()
            ->revealable()
            ->autocomplete('current-password')
            ->required()
            ->placeholder('••••••••')
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('REMEMBER THIS DEVICE');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('SIGN IN TO PORTAL →')
            ->submit('authenticate');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => strtolower(trim((string) ($data['email'] ?? ''))),
            'password' => (string) ($data['password'] ?? ''),
        ];
    }
}
