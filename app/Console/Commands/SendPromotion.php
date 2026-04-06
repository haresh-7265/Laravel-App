<?php

namespace App\Console\Commands;

use App\Mail\CouponMail;
use App\Models\Coupon;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendPromotion extends Command
{
    protected $signature = 'promotion:send';
    protected $description = 'Interactively create and send a new promotion to targeted users';

    public function __construct(protected CouponService $couponService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('🎟️  Coupon Creator & Promoter');
        $this->line('  <fg=gray>Fill in the details below to generate and send a new coupon.</>');
        $this->newLine();

        // ── 1. Code ────────────────────────────────────────────
        $code = $this->askCode();

        // ── 2. Type ────────────────────────────────────────────
        $type = $this->choice(
            question: 'Discount type',
            choices: ['percentage', 'fixed'],
            default: 'percentage',
        );

        // ── 3. Value ───────────────────────────────────────────
        $value = $this->askValue($type);

        // ── 4. Minimum Order Amount ────────────────────────────
        $minOrderAmount = $this->askMinOrderAmount();

        // ── 5. Expiry Date ─────────────────────────────────────
        $expiresAt = $this->askExpiresAt();

        // ── 6. Active Status ───────────────────────────────────
        $isActive = $this->confirm('Should this coupon be active immediately?', true);

        // ── 7. Usage Limit Per User ────────────────────────────
        $usageLimit = $this->askUsageLimit();

        // ── 8. Target Audience ─────────────────────────────────
        $audience = $this->askTargetAudience();

        // ── 9. Resolve Users ───────────────────────────────────
        $users = $this->resolveAudience($audience);

        if ($users->isEmpty()) {
            $this->components->warn("No users found for audience: \"{$audience}\". Aborting.");
            return self::SUCCESS;
        }

        // ── 10. Preview ────────────────────────────────────────
        $this->newLine();
        $this->showPreview($code, $type, $value, $minOrderAmount, $expiresAt, $isActive, $usageLimit, $audience, $users->count());

        // ── 11. Confirm ────────────────────────────────────────
        $this->newLine();
        if (!$this->confirm("Save coupon and assign to {$users->count()} user(s)?", false)) {
            $this->components->warn('Cancelled. No coupon was created.');
            return self::SUCCESS;
        }

        // ── 12. Save & Assign ──────────────────────────────────
        $coupon = $this->saveCoupon($code, $type, $value, $minOrderAmount, $expiresAt, $isActive, $usageLimit, $users);

        // ── 13. Send Email ─────────────────────────────────────
        $this->sendEmails($users, $coupon, $usageLimit);

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────
    // New Prompt Helpers
    // ──────────────────────────────────────────────────────────

    protected function askUsageLimit(): int
    {
        do {
            $raw = $this->ask('Usage limit per user (e.g. 3)', '1');

            if (!is_numeric($raw) || (int) $raw <= 0) {
                $this->components->error('Usage limit must be a positive integer.');
                $limit = null;
                continue;
            }

            $limit = (int) $raw;

        } while (is_null($limit));

        return $limit;
    }

    protected function askTargetAudience(): string
    {
        $audience = $this->choice(
            question: 'Target audience',
            choices: [
                'all' => 'All users',
                'new_customers' => 'New customers (registered in last 30 days)',
                'inactive' => 'Inactive users (no order in last 60 days)',
                'top_buyers' => 'Top buyers (5+ orders)',
            ],
            default: 'all',
        );

        // choice() returns the key when associative, but returns value on some versions
        // normalize to key
        $map = [
            'All users' => 'all',
            'New customers (registered in last 30 days)' => 'new_customers',
            'Inactive users (no order in last 60 days)' => 'inactive',
            'Top buyers (5+ orders)' => 'top_buyers',
        ];

        return $map[$audience] ?? $audience;
    }

    /**
     * Resolve users based on selected audience
     */
    protected function resolveAudience(string $audience)
    {
        $this->line("  <fg=gray>Resolving users for audience: {$audience}...</>");

        return match ($audience) {

            'all' => User::select('id', 'name', 'email')->get(),

            'new_customers' => User::select('id', 'name', 'email')
                ->where('created_at', '>=', now()->subDays(30))
                ->get(),

            'inactive' => User::select('users.id', 'users.name', 'users.email')
                ->whereDoesntHave('orders', function ($q) {
                        $q->where('created_at', '>=', now()->subDays(60));
                    })
                ->get(),

            'top_buyers' => User::select('users.id', 'users.name', 'users.email')
                ->withCount('orders')
                ->having('orders_count', '>=', 5)
                ->get(),

            default => collect(),
        };
    }

    protected function sendEmails($users, $coupon, $usageLimit)
    {
        foreach ($users as $user) {
            Mail::to($user->email)
                ->queue(
                    new CouponMail($user, $coupon, $usageLimit)
                );
        }
    }

    // ──────────────────────────────────────────────────────────
    // Existing Prompt Helpers (unchanged)
    // ──────────────────────────────────────────────────────────

    protected function askCode(): string
    {
        $suggested = strtoupper(Str::random(8));

        do {
            $code = strtoupper(trim(
                $this->ask("Coupon code", $suggested)
            ));

            if (empty($code)) {
                $this->components->error('Code cannot be empty.');
                continue;
            }

            if (!preg_match('/^[A-Z0-9_\-]{3,32}$/', $code)) {
                $this->components->error('Code must be 3–32 characters: letters, numbers, hyphens or underscores only.');
                $code = null;
                continue;
            }

            if (DB::table('coupons')->where('code', $code)->exists()) {
                $this->components->error("Code \"{$code}\" already exists. Please choose another.");
                $code = null;
            }

        } while (empty($code));

        return $code;
    }

    protected function askValue(string $type): float
    {
        $hint = $type === 'percentage' ? '(1 – 100)' : '(e.g. 5.00)';

        do {
            $raw = $this->ask("Discount value {$hint}");

            if (!is_numeric($raw)) {
                $this->components->error('Please enter a numeric value.');
                $value = null;
                continue;
            }

            $value = (float) $raw;

            if ($value <= 0) {
                $this->components->error('Value must be greater than 0.');
                $value = null;
                continue;
            }

            if ($type === 'percentage' && $value > 100) {
                $this->components->error('Percentage cannot exceed 100.');
                $value = null;
            }

        } while (is_null($value));

        return $value;
    }

    protected function askMinOrderAmount(): float
    {
        do {
            $raw = $this->ask('Minimum order amount (0 = no minimum)', '0');

            if (!is_numeric($raw)) {
                $this->components->error('Please enter a numeric value.');
                $amount = null;
                continue;
            }

            $amount = (float) $raw;

            if ($amount < 0) {
                $this->components->error('Minimum order amount cannot be negative.');
                $amount = null;
            }

        } while (is_null($amount));

        return $amount;
    }

    protected function askExpiresAt(): ?string
    {
        $hasExpiry = $this->confirm('Should this coupon have an expiry date?', true);

        if (!$hasExpiry) {
            return null;
        }

        do {
            $raw = $this->ask('Expiry date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)');
            $parsed = strtotime($raw);

            if (!$parsed) {
                $this->components->error('Invalid date format. Example: 2025-12-31');
                $expiresAt = null;
                continue;
            }

            if ($parsed <= time()) {
                $this->components->error('Expiry date must be in the future.');
                $expiresAt = null;
                continue;
            }

            $expiresAt = date('Y-m-d H:i:s', $parsed);

        } while (is_null($expiresAt));

        return $expiresAt;
    }

    // ──────────────────────────────────────────────────────────
    // Display Helpers
    // ──────────────────────────────────────────────────────────

    protected function showPreview(
        string $code,
        string $type,
        float $value,
        float $minOrderAmount,
        ?string $expiresAt,
        bool $isActive,
        int $usageLimit,
        string $audience,
        int $userCount,
    ): void {
        $this->components->info('📋  Coupon Summary');

        $displayValue = $type === 'percentage'
            ? "{$value}%"
            : '₹' . number_format($value, 2);

        $displayMin = $minOrderAmount > 0
            ? '₹' . number_format($minOrderAmount, 2)
            : '<fg=gray>No minimum</>';

        $displayExpiry = $expiresAt
            ? "<fg=yellow>{$expiresAt}</>"
            : '<fg=gray>Never</>';

        $displayActive = $isActive
            ? '<fg=green>Yes</>'
            : '<fg=red>No</>';

        $audienceLabels = [
            'all' => 'All users',
            'new_customers' => 'New customers (last 30 days)',
            'inactive' => 'Inactive users (no order in 60 days)',
            'top_buyers' => 'Top buyers (5+ orders)',
        ];

        $this->table(
            headers: ['Field', 'Value'],
            rows: [
                ['Code', "<fg=green;options=bold>{$code}</>"],
                ['Type', ucfirst($type)],
                ['Discount Value', $displayValue],
                ['Min. Order Amount', $displayMin],
                ['Expires At', $displayExpiry],
                ['Active', $displayActive],
                ['Usage Limit/User', $usageLimit],
                ['Target Audience', $audienceLabels[$audience] ?? $audience],
                ['Users Matched', "<fg=cyan>{$userCount}</>"],
            ],
        );
    }

    // ──────────────────────────────────────────────────────────
    // Persist & Assign
    // ──────────────────────────────────────────────────────────

    protected function saveCoupon(
        string $code,
        string $type,
        float $value,
        float $minOrderAmount,
        ?string $expiresAt,
        bool $isActive,
        int $usageLimit,
        $users,
    ): Coupon {
        $this->newLine();
        $this->line('  <fg=gray>Saving coupon...</>');

        $coupon = Coupon::create([
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min_order_amount' => $minOrderAmount,
            'expires_at' => $expiresAt,
            'is_active' => $isActive,
        ]);

        $this->line('  <fg=gray>Assigning to users...</>');

        $userIds = $users->pluck('id')->toArray();
        $result = $this->couponService->assignToUsers($coupon, $userIds, $usageLimit);


        $this->newLine();
        $this->components->success("Coupon \"{$code}\" created and {$result['message']} 🎉");

        return $coupon;
    }
}