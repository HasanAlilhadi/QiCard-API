<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'performed_by',
        'ip_address',
        'user_agent',
        'previous_state',
        'new_state',
        'additional_data',
    ];

    protected $casts = [
        'previous_state' => 'array',
        'new_state' => 'array',
        'additional_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
