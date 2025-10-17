<?php

namespace Modules\Approval\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApprovalRule extends Model
{
    use HasFactory;

    protected $table = 'approval_rules'; // pastikan nama tabel sesuai

    protected $fillable = [
        'approval_types_id',
        'rule_name',
        'is_active',
    ];

    /**
     * Relasi ke ApprovalType
     * Satu ApprovalType bisa punya banyak ApprovalRule
     */
    public function type()
    {
        return $this->belongsTo(\Modules\Approval\Entities\ApprovalType::class, 'approval_types_id');
    }

    /**
     * Relasi ke ApprovalRuleUsers
     * Satu ApprovalRule bisa punya banyak user (requester/approver)
     */

    public function levels() {
        return $this->hasMany(ApprovalRuleLevel::class,'approval_rules_id')->orderBy('level');
    }
}
