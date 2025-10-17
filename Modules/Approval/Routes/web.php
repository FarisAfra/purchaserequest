<?php

use Illuminate\Support\Facades\Route;
use Modules\Approval\Http\Controllers\ApprovalTypesController;
use Modules\Approval\Http\Controllers\ApprovalRulesController;
use Modules\Approval\Http\Controllers\ApprovalRuleUsersController;
use Modules\Approval\Http\Controllers\ApprovalRequestController;

Route::group(['middleware' => 'auth'], function () {
    Route::resource('approval_types', ApprovalTypesController::class);
    Route::resource('approval_rules', ApprovalRulesController::class);
    Route::get('approval_requests', [ApprovalRequestController::class,'index'])->name('approval_requests.index');
    Route::get('approval_requests/{id}', [ApprovalRequestController::class,'show'])->name('approval_requests.show');
    Route::post('approval_requests', [ApprovalRequestController::class,'store'])->name('approval_requests.store');
    Route::post('approval_requests/{id}/approve', [ApprovalRequestController::class,'approve'])->name('approval_requests.approve');
    Route::post('approval_requests/{id}/reject', [ApprovalRequestController::class,'reject'])->name('approval_requests.reject');
    Route::get('{id}/edit', [ApprovalTypesController::class, 'edit'])->name('approval_types.edit');
});