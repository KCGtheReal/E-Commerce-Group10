<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? ['*'];

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    /**
     * Issue a new cryptographically random API token storing only its SHA-256 digest.
     *
     * @param  array<int, string>  $abilities
     * @return array{plainTextToken: string, apiToken: self, expiresAt: ?Carbon}
     */
    public static function issueFor(
        User $user,
        string $name = 'mobile-app',
        array $abilities = ['*'],
        int $ttlDays = 30
    ): array {
        $plainText = 'rst_'.bin2hex(random_bytes(32));
        $expiresAt = $ttlDays > 0 ? now()->addDays($ttlDays) : null;

        $apiToken = self::create([
            'user_id' => $user->id,
            'name' => mb_substr(trim($name) !== '' ? trim($name) : 'mobile-app', 0, 100),
            'token_hash' => hash('sha256', $plainText),
            'abilities' => $abilities,
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        return [
            'plainTextToken' => $plainText,
            'apiToken' => $apiToken,
            'expiresAt' => $expiresAt,
        ];
    }

    public static function findActiveByPlainText(?string $plainTextToken): ?self
    {
        $token = trim((string) $plainTextToken);
        if ($token === '') {
            return null;
        }

        $record = self::with('user')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $record || $record->isExpired() || ! $record->user) {
            return null;
        }

        return $record;
    }
}
