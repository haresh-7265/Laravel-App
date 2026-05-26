<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    // ── Subscription Tiers ──────────────────────────────────────────────
    public const TIER_FREE = 'free';

    public const TIER_PRO = 'pro';

    public const TIER_ENTERPRISE = 'enterprise';

    public const API_LIMITS = [
        self::TIER_FREE => 60,
        self::TIER_PRO => 600,
        self::TIER_ENTERPRISE => 6000,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'subscription_tier',
        'preferred_locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Get the API rate limit for this user's subscription tier.
     */
    public function apiRateLimit(): int
    {
        return self::API_LIMITS[$this->subscription_tier] ?? self::API_LIMITS[self::TIER_FREE];
    }

    public function hasRole($role)
    {
        if (is_array($role)) {
            return in_array($this->role, $role);
        }

        return $this->role === $role;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class)
            ->withPivot('usage_limit', 'used_count')
            ->withTimestamps();
    }

    public function waitlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_waitlist')
            ->withTimestamps();
    }

    public function preferredLocale(): ?string
    {
        return $this->preferred_locale;
    }

    /**
     * Route the webhook notification channel.
     *
     * Per-user override: if the user has a webhook_url column, use that.
     * Otherwise the WebhookChannel falls back to config('services.webhook.url').
     */
    public function routeNotificationForWebhook(): ?string
    {
        return $this->webhook_url ?? null;
    }

    /**
     * Route notifications for the Slack channel.
     */
    public function routeNotificationForSlack(): ?string
    {
        // Return the user's specific channel, or fallback to the system default
        return $this->slack_channel ?? config('services.slack.notifications.channel', '#orders');
    }
}
