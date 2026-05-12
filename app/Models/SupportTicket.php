<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    // -------------------------------------------------------------------------
    // Status & Priority constants
    // -------------------------------------------------------------------------

    public const STATUS_OPEN        = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CLOSED      = 'closed';

    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'subject',
        'description',
        'customer_name',
        'customer_email',
        'priority',
        'status',
        'assigned_to',
        'slack_message_ts',
        'slack_channel_id',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The admin user this ticket is assigned to.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Emoji representing the ticket priority.
     */
    public function priorityEmoji(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => '🔴',
            self::PRIORITY_HIGH   => '🟠',
            self::PRIORITY_MEDIUM => '🟡',
            self::PRIORITY_LOW    => '🟢',
            default               => '⚪',
        };
    }

    /**
     * Human-readable priority label.
     */
    public function priorityLabel(): string
    {
        return ucfirst($this->priority);
    }

    /**
     * Human-readable status label.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN        => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_CLOSED      => 'Closed',
            default                  => ucfirst($this->status),
        };
    }

    /**
     * Check if this ticket is still actionable (not closed).
     */
    public function isOpen(): bool
    {
        return $this->status !== self::STATUS_CLOSED;
    }
}
