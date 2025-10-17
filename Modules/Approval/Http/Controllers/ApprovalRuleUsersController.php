<?php

namespace Modules\Approval\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Modules\Approval\Entities\ApprovalRuleUser;
use Modules\Approval\Entities\ApprovalRule;
use App\Models\User;
use Modules\Approval\DataTables\ApprovalRuleUsersDataTable;

class ApprovalRuleUsersController extends Controller
{
    public function index(ApprovalRuleUsersDataTable $dataTable)
    {
        abort_if(Gate::denies('access_approval_rule_users'), 403);

        return $dataTable->render('approval::approval_rule_users.index');
    }

    public function create()
    {
        abort_if(Gate::denies('access_approval_rule_users'), 403);

        $rules = ApprovalRule::all();
        $users = User::all();
        $roles = ['Approver', 'Reviewer', 'Final Approver'];

        return view('approval::approval_rule_users.create', compact('rules', 'users', 'roles'));
    }

    public function store(Request $request)
    {
        abort_if(Gate::denies('access_approval_rule_users'), 403);

        $request->validate([
            'approval_rules_id' => 'required|exists:approval_rules,id',
            'user_id'          => 'required|exists:users,id',
            'role'             => 'required|string|max:100',
            // hapus kalau kolom ini ga ada
            'is_active'        => 'nullable|boolean',
        ]);

        ApprovalRuleUser::create($request->only(['approval_rules_id', 'user_id', 'role', 'is_active']));

        session()->flash('success', 'Approval Rule User Created!');

        return redirect()->route('approval_rule_users.index');
    }

    public function edit($id)
    {
        abort_if(Gate::denies('access_approval_rule_users'), 403);

        $approvalRuleUser = ApprovalRuleUser::findOrFail($id);
        $rules = ApprovalRule::all();
        $users = User::all();
        $roles = ['Approver', 'Reviewer', 'Final Approver'];

        return view('approval::approval_rule_users.edit', compact('approvalRuleUser', 'rules', 'users', 'roles'));
    }

    public function update(Request $request, $id)
    {
        abort_if(Gate::denies('access_approval_rule_users'), 403);

        $request->validate([
            'approval_rules_id' => 'required|exists:approval_rules,id',
            'user_id'          => 'required|exists:users,id',
            'role'             => 'required|string|max:100',
            'is_active'        => 'nullable|boolean',
        ]);

        ApprovalRuleUser::findOrFail($id)->update($request->only(['approval_rules_id', 'user_id', 'role', 'is_active']));

        session()->flash('info', 'Approval Rule User Updated!');

        return redirect()->route('approval_rule_users.index');
    }

    public function destroy($id)
    {
        abort_if(Gate::denies('access_approval_rule_users'), 403);

        $approvalRuleUser = ApprovalRuleUser::findOrFail($id);
        $approvalRuleUser->delete();

        session()->flash('warning', 'Approval Rule User Deleted!');

        return redirect()->route('approval_rule_users.index');
    }
}
