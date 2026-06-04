<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key_id',
        'key_secret_hash',
        'user_id',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateFor(User $user, string $name = 'default'): string
    {
        $keyId = 'ak_'.Str::random(16);
        $secret = Str::random(40);
        $fullKey = $keyId.'.'.$secret;

        self::create([
            'user_id' => $user->id,
            'key_id' => $keyId,
            'key_secret_hash' => hash('sha256', $secret),
            'name' => $name,
        ]);

        return $fullKey;
    }
}
