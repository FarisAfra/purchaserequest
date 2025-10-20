<?php

namespace Modules\Approval\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Approval\DataTables\ApprovalRulesDataTable;
use Modules\Approval\Entities\ApprovalType;
use Modules\Approval\Entities\ApprovalRule; 
use Modules\Approval\Entities\ApprovalRuleLevel; 
use Modules\Approval\Entities\ApprovalRuleUser;
use App\Models\User;
use DB;

class ApprovalRulesController extends Controller
{
    public function index(ApprovalRulesDataTable $dataTable)
    {
        $types = ApprovalType::all();
        $users = User::all();
        return $dataTable->render('approval::approval_rules.index', compact('types', 'users'));
    }

    public function create()
    {
        $types = ApprovalType::all();
        $users = User::all();
        return view('approval::approval_rules.create', compact('types', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'approval_types_id' => 'required|exists:approval_types,id',
            'rule_name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'levels' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $rule = ApprovalRule::create([
                'approval_types_id' => $request->approval_types_id,
                'rule_name' => $request->rule_name,
                'is_active' => $request->is_active,
            ]);

            if ($request->has('levels') && is_array($request->levels)) {
                foreach ($request->levels as $lvl) {
                    $levelNumber = $lvl['level'] ?? null;
                    $amountLimit = $lvl['amount_limit'] ?? null;

                    $level = ApprovalRuleLevel::create([
                        'approval_rules_id' => $rule->id,
                        'level' => $levelNumber,
                        'amount_limit' => $amountLimit, 
                        'is_active' => 1,
                    ]);

                    if (!empty($lvl['pairs']) && is_array($lvl['pairs'])) {
                        foreach ($lvl['pairs'] as $pairIdx => $pair) { // <-- Gunakan $pairIdx
                            
                            if (!empty($pair['requester'])) {
                                foreach ($pair['requester'] as $uid) {
                                    ApprovalRuleUser::create([
                                        'approval_rule_levels_id' => $level->id,
                                        'user_id' => $uid,
                                        'role' => 'requester',
                                        'sequence' => $pairIdx, // <-- Simpan urutannya
                                    ]);
                                }
                            }
                    
                            if (!empty($pair['approver'])) {
                                foreach ($pair['approver'] as $uid) {
                                    ApprovalRuleUser::create([
                                        'approval_rule_levels_id' => $level->id,
                                        'user_id' => $uid,
                                        'role' => 'approver',
                                        'sequence' => $pairIdx, // <-- Simpan urutannya
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            toast('Approval Rule Created!', 'success');
            return redirect()->route('approval_rules.index')->with('success', 'Rule saved.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit($id)
    {
        $rule = ApprovalRule::with('levels.users.user')->findOrFail($id);
        $types = ApprovalType::all();
        $users = User::all();
        return view('approval::approval_rules.edit', compact('rule', 'types', 'users'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'approval_types_id' => 'required|exists:approval_types,id',
            'rule_name' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'levels' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $rule = ApprovalRule::findOrFail($id);
            $rule->update([
                'approval_types_id' => $request->approval_types_id,
                'rule_name' => $request->rule_name,
                'is_active' => $request->is_active,
            ]);

            // Delete old levels and users, then re-create
            foreach ($rule->levels as $old) {
                $old->users()->delete();
            }
            $rule->levels()->delete();

            if ($request->has('levels') && is_array($request->levels)) {
                foreach ($request->levels as $lvl) {
                    $levelNumber = $lvl['level'] ?? null;
                    $amountLimit = $lvl['amount_limit'] ?? null;

                    $level = ApprovalRuleLevel::create([
                        'approval_rules_id' => $rule->id,
                        'level' => $levelNumber,
                        'amount_limit' => $amountLimit,
                        'is_active' => 1,
                    ]);

                    if (!empty($lvl['pairs']) && is_array($lvl['pairs'])) {
                        foreach ($lvl['pairs'] as $pairIdx => $pair) { // <-- Gunakan $pairIdx
                            
                            if (!empty($pair['requester'])) {
                                foreach ($pair['requester'] as $uid) {
                                    ApprovalRuleUser::create([
                                        'approval_rule_levels_id' => $level->id,
                                        'user_id' => $uid,
                                        'role' => 'requester',
                                        'sequence' => $pairIdx, // <-- Simpan urutannya
                                    ]);
                                }
                            }
                    
                            if (!empty($pair['approver'])) {
                                foreach ($pair['approver'] as $uid) {
                                    ApprovalRuleUser::create([
                                        'approval_rule_levels_id' => $level->id,
                                        'user_id' => $uid,
                                        'role' => 'approver',
                                        'sequence' => $pairIdx, // <-- Simpan urutannya
                                    ]);
                                }
                            }
                        }
                    } 
                }
            }

            DB::commit();
            toast('Approval Rule Updated!', 'info');
            return redirect()->route('approval_rules.index')->with('success', 'Rule updated.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $rule = ApprovalRule::findOrFail($id);
        $rule->delete();

        toast('Approval Rule Deleted!', 'warning');
        return redirect()->route('approval_rules.index');
    }

}
