<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Crm\ActivityController;
use App\Http\Controllers\Crm\CompanyController;
use App\Http\Controllers\Crm\ContactController;
use App\Http\Controllers\Crm\DealController;
use App\Http\Controllers\Crm\TaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdmin\AuditController as SuperAuditController;
use App\Http\Controllers\SuperAdmin\PermissionController as SuperPermissionController;
use App\Http\Controllers\SuperAdmin\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if ($user = auth()->user()) {
        if ($user->is_super_admin) {
            return redirect()->route('super-admin.tenants.index');
        }

        if ($user->tenant) {
            return redirect()->route('tenant.dashboard', $user->tenant->slug);
        }
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invitations/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile/sessions/{sessionId}', [ProfileController::class, 'terminateSession'])->name('profile.sessions.destroy');
    Route::post('/profile/sessions/logout-others', [ProfileController::class, 'logoutOtherSessions'])->name('profile.sessions.logout-others');

    Route::prefix('super-admin')
        ->name('super-admin.')
        ->middleware('super-admin')
        ->group(function () {
            Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
            Route::get('tenants/create', [TenantController::class, 'create'])->name('tenants.create');
            Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
            Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
            Route::put('tenants/{tenant}/toggle', [TenantController::class, 'toggle'])->name('tenants.toggle');
            Route::put('tenants/{tenant}/features/{feature}', [TenantController::class, 'toggleFeature'])->name('tenants.features.toggle');

            Route::get('permissions', [SuperPermissionController::class, 'index'])->name('permissions.index');

            Route::get('audit', [SuperAuditController::class, 'index'])->name('audit.index');
        });

    Route::prefix('t/{tenant}')
        ->middleware('tenant')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'show'])->name('tenant.dashboard');

            Route::prefix('admin')->name('admin.')->group(function () {
                Route::get('users', [UserController::class, 'index'])->name('users.index');
                Route::post('users/invite', [UserController::class, 'invite'])->name('users.invite');
                Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
                Route::put('users/{user}/roles', [UserController::class, 'syncRoles'])->name('users.roles.sync');
                Route::post('users/{user}/roles/temporary', [UserController::class, 'grantTemporaryRole'])->name('users.roles.temporary');
                Route::put('users/{user}/unlock', [UserController::class, 'unlock'])->name('users.unlock');
                Route::put('users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');

                Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
                Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
                Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
                Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
                Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
                Route::put('roles/{role}/parent', [RoleController::class, 'syncParent'])->name('roles.parent.sync');
                Route::put('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.permissions.sync');
                Route::post('roles/{role}/apply-group', [RoleController::class, 'applyGroup'])->name('roles.groups.apply');
                Route::post('roles/{role}/clone', [RoleController::class, 'clone'])->name('roles.clone');
                Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

                Route::get('permissions', [AdminPermissionController::class, 'index'])->name('permissions.index');
                Route::get('permissions/users/{user}', [AdminPermissionController::class, 'userEdit'])->name('permissions.user.edit');
                Route::post('permissions/users/{user}', [AdminPermissionController::class, 'userGrant'])->name('permissions.user.grant');
                Route::delete('permissions/users/{user}/{permission}', [AdminPermissionController::class, 'userRevoke'])->name('permissions.user.revoke');

                Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
                Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
                Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
                Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

                Route::get('audit', [AdminAuditController::class, 'index'])->name('audit.index');
                Route::post('audit/export', [AdminAuditController::class, 'export'])->name('audit.export');
            });

            Route::prefix('crm')->name('crm.')->group(function () {
                Route::resource('companies', CompanyController::class);
                Route::resource('contacts', ContactController::class);
                Route::resource('deals', DealController::class);
                Route::post('deals/{deal}/approve', [DealController::class, 'approve'])->name('deals.approve');
                Route::resource('tasks', TaskController::class);
                Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');

                Route::get('activities', [ActivityController::class, 'index'])->name('activities.index');
                Route::get('activities/create', [ActivityController::class, 'create'])->name('activities.create');
                Route::post('activities', [ActivityController::class, 'store'])->name('activities.store');
                Route::delete('activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
            });
        });
});
