<?php
namespace Modules\Approval\Entities;

use Illuminate\Database\Eloquent\Model;

class ApprovalRuleLevel extends Model
{
    protected $table = 'approval_rule_levels';

    protected $fillable = [
        'approval_rules_id',
        'level',
        'amount_limit',
        'is_active',
    ];

    public function rule()
    {
        return $this->belongsTo(ApprovalRule::class, 'approval_rules_id');
    }

    public function users()
    {
        return $this->hasMany(ApprovalRuleUser::class, 'approval_rule_levels_id');
    }
    
    public function approvers() {
        return $this->users()->where('role','approver');
    }
    
    public function requesters() {
        return $this->users()->where('role','requester');
    }
}
