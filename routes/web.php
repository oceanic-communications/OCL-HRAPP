<?php

use App\Http\Controllers\Admin\InductionEmployeeProgressController;
use App\Http\Controllers\Admin\InductionPolicyAdminController;
use App\Http\Controllers\Admin\RoleAdminController;
use App\Http\Controllers\Admin\RoleTemplatePermissionController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Portal\EmployeePortalController;
use App\Http\Controllers\Portal\InductionEmployeeController;
use App\Http\Controllers\Portal\PortalNotificationController;
use App\Support\PortalPermissions;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.post');
    Route::get('/login/verify', fn () => redirect()->route('login'));
    Route::post('/login/verify', [AuthController::class, 'verifyLoginOtp'])
        ->middleware('throttle:20,1')
        ->name('login.verify');
    Route::post('/login/resend-otp', [AuthController::class, 'resendLoginOtp'])
        ->middleware('throttle:3,1')
        ->name('login.resend-otp');
    Route::post('/login/cancel-otp', [AuthController::class, 'cancelLoginOtp'])->name('login.cancel-otp');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/notifications/{notification}/read', [PortalNotificationController::class, 'markRead'])
        ->name('portal.notifications.read');

    Route::get('/induction/certificate', [InductionEmployeeController::class, 'certificate'])->name('portal.induction.certificate');
    Route::get('/induction/master-policy/{induction_policy_version}', [InductionEmployeeController::class, 'masterPolicy'])->name('portal.induction.master-pdf');
    Route::get('/induction', [InductionEmployeeController::class, 'index'])->name('portal.induction');
    Route::get('/induction/sections/{induction_section}', [InductionEmployeeController::class, 'show'])->name('portal.induction.section');
    Route::post('/induction/sections/{induction_section}/complete', [InductionEmployeeController::class, 'complete'])->name('portal.induction.section.complete');

    $portal = EmployeePortalController::class;
    Route::get('/probation', [$portal, 'page'])->defaults('portalPage', 'probation')->name('portal.probation');
    Route::get('/training', [$portal, 'page'])->defaults('portalPage', 'training')->name('portal.training');
    Route::get('/training/assign', [$portal, 'page'])->defaults('portalPage', 'training.assign')->name('portal.training.assign');
    Route::get('/training/approvals', [$portal, 'page'])->defaults('portalPage', 'training.approvals')->name('portal.training.approvals');
    Route::get('/leave', [$portal, 'page'])->defaults('portalPage', 'leave')->name('portal.leave');
    Route::get('/documents', [$portal, 'page'])->defaults('portalPage', 'documents')->name('portal.documents');
    Route::get('/approvals', [$portal, 'page'])->defaults('portalPage', 'approvals')->name('portal.approvals');
    Route::get('/blockouts', [$portal, 'page'])->defaults('portalPage', 'blockouts')->name('portal.blockouts');
    Route::get('/organization', [$portal, 'page'])->defaults('portalPage', 'organization')->name('portal.organization');
    Route::get('/performance', [$portal, 'page'])->defaults('portalPage', 'performance')->name('portal.performance');
    Route::get('/appraisals', [$portal, 'page'])->defaults('portalPage', 'appraisals')->name('portal.appraisals');
    Route::get('/reports', [$portal, 'page'])->defaults('portalPage', 'reports')->name('portal.reports');
    Route::get('/settings', [$portal, 'page'])->defaults('portalPage', 'settings')->name('portal.settings');
    Route::get('/conduct/notices', [$portal, 'page'])->defaults('portalPage', 'conduct.notices')->name('portal.conduct.notices');
    Route::get('/conduct/improvement-plans', [$portal, 'page'])->defaults('portalPage', 'conduct.improvement-plans')->name('portal.conduct.improvement-plans');
    Route::get('/conduct/meetings', [$portal, 'page'])->defaults('portalPage', 'conduct.meetings')->name('portal.conduct.meetings');
    Route::get('/conduct/acknowledge', [$portal, 'page'])->defaults('portalPage', 'conduct.acknowledge')->name('portal.conduct.acknowledge');
    Route::get('/conduct/response', [$portal, 'page'])->defaults('portalPage', 'conduct.response')->name('portal.conduct.response');
    Route::get('/conduct/team-concerns', [$portal, 'page'])->defaults('portalPage', 'conduct.team-concerns')->name('portal.conduct.team-concerns');
    Route::get('/conduct/create-incident', [$portal, 'page'])->defaults('portalPage', 'conduct.create-incident')->name('portal.conduct.create-incident');
    Route::get('/conduct/pending-actions', [$portal, 'page'])->defaults('portalPage', 'conduct.pending-actions')->name('portal.conduct.pending-actions');
    Route::get('/conduct/escalations', [$portal, 'page'])->defaults('portalPage', 'conduct.escalations')->name('portal.conduct.escalations');
    Route::get('/conduct/investigations', [$portal, 'page'])->defaults('portalPage', 'conduct.investigations')->name('portal.conduct.investigations');
    Route::get('/conduct/analytics', [$portal, 'page'])->defaults('portalPage', 'conduct.analytics')->name('portal.conduct.analytics');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware(['admin.only'])->group(function () {
            Route::get('/role-templates', [RoleTemplatePermissionController::class, 'index'])->name('role-templates.index');
            Route::get('/role-templates/create', [RoleTemplatePermissionController::class, 'create'])->name('role-templates.create');
            Route::post('/role-templates', [RoleTemplatePermissionController::class, 'store'])->name('role-templates.store');
            Route::get('/role-templates/{roleTemplate}/permissions', [RoleTemplatePermissionController::class, 'edit'])->name('role-templates.permissions.edit');
            Route::put('/role-templates/{roleTemplate}/permissions', [RoleTemplatePermissionController::class, 'update'])->name('role-templates.permissions.update');
        });

        Route::middleware(['permission:'.PortalPermissions::STAFF_USER_READ])->group(function () {
            Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
        });

        Route::get('/users/create', [UserAdminController::class, 'create'])
            ->middleware('admin.user.create')
            ->name('users.create');
        Route::post('/users', [UserAdminController::class, 'store'])
            ->middleware('admin.user.create')
            ->name('users.store');

        Route::middleware(['permission:'.PortalPermissions::STAFF_USER_UPDATE])->group(function () {
            Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserAdminController::class, 'update'])->name('users.update');
        });

        Route::post('/users/{user}/archive', [UserAdminController::class, 'archive'])
            ->middleware(['permission:'.PortalPermissions::STAFF_USER_ARCHIVE])
            ->name('users.archive');

        Route::middleware(['permission:'.PortalPermissions::STAFF_ROLE_READ])->group(function () {
            Route::get('/roles', [RoleAdminController::class, 'index'])->name('roles.index');
        });

        Route::middleware(['permission:'.PortalPermissions::STAFF_ROLE_UPDATE])->group(function () {
            Route::get('/roles/create', [RoleAdminController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleAdminController::class, 'store'])->name('roles.store');
            Route::get('/roles/{role}/edit', [RoleAdminController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{role}', [RoleAdminController::class, 'update'])->name('roles.update');
            Route::post('/roles/{role}/archive', [RoleAdminController::class, 'archive'])->name('roles.archive');
        });

        Route::middleware(['permission:'.PortalPermissions::STAFF_ROLE_READ])->group(function () {
            Route::get('/roles/{role}', [RoleAdminController::class, 'show'])->name('roles.show');
        });

        Route::prefix('induction')->name('induction.')->group(function () {
            Route::middleware(['permission:'.implode('|', [
                PortalPermissions::INDUCTION_POLICY_MANAGE,
                PortalPermissions::INDUCTION_POLICY_READ,
                PortalPermissions::INDUCTION_POLICY_CREATE,
                PortalPermissions::INDUCTION_POLICY_UPDATE,
                PortalPermissions::INDUCTION_POLICY_ARCHIVE,
                PortalPermissions::INDUCTION_ENROLLMENT_READ,
            ])])->group(function () {
                Route::get('/', [InductionPolicyAdminController::class, 'index'])->name('index');
            });

            Route::middleware(['permission:'.PortalPermissions::INDUCTION_ENROLLMENT_READ])->group(function () {
                Route::get('/progress', [InductionEmployeeProgressController::class, 'index'])->name('progress.index');
                Route::get('/progress/{user}', [InductionEmployeeProgressController::class, 'show'])->name('progress.show');
            });

            Route::middleware(['permission:'.PortalPermissions::INDUCTION_POLICY_READ])->group(function () {
                Route::get('/policies/{policy}', [InductionPolicyAdminController::class, 'showPolicy'])->name('policies.show');
                Route::get('/policies/{policy}/clauses/{section}', [InductionPolicyAdminController::class, 'showSection'])->name('policies.clauses.show');
                Route::get('/policies/{policy}/clauses/{section}/sub-clauses/{sub_clause}', [InductionPolicyAdminController::class, 'showSubClause'])->name('policies.clauses.sub-clauses.show');
            });

            Route::middleware(['permission:'.PortalPermissions::INDUCTION_POLICY_CREATE])->group(function () {
                Route::post('/policies', [InductionPolicyAdminController::class, 'storePolicy'])->name('policies.store');
                Route::get('/policies/{policy}/clauses/create', [InductionPolicyAdminController::class, 'createSection'])->name('policies.clauses.create');
                Route::post('/policies/{policy}/clauses', [InductionPolicyAdminController::class, 'storeSection'])->name('policies.clauses.store');
                Route::get('/policies/{policy}/clauses/{section}/sub-clauses/create', [InductionPolicyAdminController::class, 'createSubClause'])->name('policies.clauses.sub-clauses.create');
                Route::post('/policies/{policy}/clauses/{section}/sub-clauses', [InductionPolicyAdminController::class, 'storeSubClause'])->name('policies.clauses.sub-clauses.store');
            });

            Route::middleware(['permission:'.PortalPermissions::INDUCTION_POLICY_UPDATE])->group(function () {
                Route::put('/policies/{policy}', [InductionPolicyAdminController::class, 'updatePolicy'])->name('policies.update');
                Route::get('/policies/{policy}/clauses/{section}/edit', [InductionPolicyAdminController::class, 'editSection'])->name('policies.clauses.edit');
                Route::put('/policies/{policy}/clauses/{section}', [InductionPolicyAdminController::class, 'updateSection'])->name('policies.clauses.update');
                Route::get('/policies/{policy}/clauses/{section}/sub-clauses/{sub_clause}/edit', [InductionPolicyAdminController::class, 'editSubClause'])->name('policies.clauses.sub-clauses.edit');
                Route::put('/policies/{policy}/clauses/{section}/sub-clauses/{sub_clause}', [InductionPolicyAdminController::class, 'updateSubClause'])->name('policies.clauses.sub-clauses.update');
            });

            Route::middleware(['permission:'.PortalPermissions::INDUCTION_POLICY_ARCHIVE])->group(function () {
                Route::post('/policies/{policy}/clauses/{section}/archive', [InductionPolicyAdminController::class, 'archiveSection'])->name('policies.clauses.archive');
                Route::post('/policies/{policy}/clauses/{section}/sub-clauses/{sub_clause}/archive', [InductionPolicyAdminController::class, 'archiveSubClause'])->name('policies.clauses.sub-clauses.archive');
            });
        });
    });
});
