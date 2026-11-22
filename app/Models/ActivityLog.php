<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'action', 'module', 'description', 'ip_address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'viewed' => 'Viewed',
            'searched' => 'Searched',
            'created' => 'Created',
            'updated' => 'Updated',
            'deactivated' => 'Deactivated',
            'restored' => 'Restored',
            'logged in' => 'Logged in',
            'logged out' => 'Logged out',
            default => ucfirst($this->action),
        };
    }
}
