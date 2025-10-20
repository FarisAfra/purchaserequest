<?php
namespace Modules\Approval\Entities;

use Illuminate\Database\Eloquent\Model;

class ApprovalRuleUser extends Model
{
    protected $table = 'approval_rule_users';

    protected $fillable = [
        'approval_rule_levels_id',
        'user_id',
        'role',
        'sequence',
    ];

    public function level()
    {
        return $this->belongsTo(ApprovalRuleLevel::class, 'approval_rule_levels_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
