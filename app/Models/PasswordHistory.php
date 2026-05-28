<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PasswordHistory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'password',
    ];

    /**
     * Get the user that owns the password history.
     */
    public function historyable(): MorphTo
    {
        return $this->morphTo();
    }
}
