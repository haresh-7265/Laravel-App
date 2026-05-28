<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class NotUsedPassword implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(protected $user = null)
    {
        $this->user = $user ?? auth()->user();
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->user) {
            return;
        }

        // Retrieve last 5 password histories
        $histories = $this->user->passwordHistories()
            ->latest()
            ->take(5)
            ->get();

        foreach ($histories as $history) {
            if (Hash::check($value, $history->password)) {
                $fail('The new password cannot be the same as any of your last 5 passwords.');
                return;
            }
        }
    }
}
