<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPreparationItem extends Model
{
    protected $fillable = [
        'event_id',
        'label',
        'assigned_team_member_id',
        'is_completed',
        'completion_note',
        'completed_at',
        'completed_by_user_id',
        'review_status',
        'reviewed_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(
            Event::class,
            'event_id'
        );
    }

    public function assignedTeamMember() { return $this->belongsTo(TeamMember::class, 'assigned_team_member_id'); }
    public function completedBy() { return $this->belongsTo(User::class, 'completed_by_user_id'); }
}
