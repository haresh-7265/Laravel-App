<?php

namespace App\Models;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements HasLocalePreference, MustVerifyEmail, CanResetPassword
{
    use HasFactory, HasRoles, Notifiable;

    /**
     * The table associated with the model.
     */
    protected $table = 'admins';

    /**
     * The guard used by this model.
     */
    protected string $guard = 'admin';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferred_locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function preferredLocale(): ?string
    {
        return $this->preferred_locale;
    }

    public function isAdmin(): bool
    {
        return true;
    }

    public function role(): Attribute
    {
        return Attribute::make(
            get: fn () => 'admin',
        );
    }

    /**
     * Get the password history for the admin.
     */
    public function passwordHistories(): MorphMany
    {
        return $this->morphMany(PasswordHistory::class, 'historyable');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (Admin $user) {
            if ($user->wasChanged('password')) {
                // Record the password history
                $user->passwordHistories()->create([
                    'password' => $user->password,
                ]);

                $count = $user->passwordHistories()->count();
                // Keep only the last 5 entries
                if ($count > 5) {
                    // get oldest records beyond limit — delete them
                    $user->passwordHistories()
                        ->oldest()
                        ->limit($count - 5)
                        ->get()
                        ->each
                        ->delete();
                }
            }
        });
    }
}
