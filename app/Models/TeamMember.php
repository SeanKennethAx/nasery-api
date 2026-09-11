<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $fillable = ['organizer_id', 'user_id', 'position', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function organizer() { return $this->belongsTo(Organizer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function tasks() { return $this->hasMany(EventPreparationItem::class, 'assigned_team_member_id'); }
}
