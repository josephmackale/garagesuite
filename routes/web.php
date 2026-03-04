<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SmsCampaignController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Vault\VaultController;
use App\Http\Controllers\Vault\VaultAttachController;
use App\Http\Controllers\Partners\InsurerController;
use App\Http\Controllers\Partners\InsurerController as PartnersInsurerController;
use App\Http\Controllers\Insurance\ApprovalPackController;
use App\Http\Controllers\Insurance\InsuranceInvoiceController;
use App\Http\Controllers\Insurance\InsuranceClaimController;


use App\Http\Controllers\Admin\AdminGarageController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminActivityController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminImpersonationController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Jobs\JobCreateWizardController;
use App\Http\Controllers\Insurance\InsuranceApprovalController;
use App\Http\Controllers\Insurance\InsuranceRepairController;

use App\Http\Controllers\Auth\RegisteredUserController; // optional legacy
use App\Http\Controllers\Auth\PhoneRegisterController;
use App\Http\Controllers\Repair\RepairController;

use App\Http\Controllers\SettingsController;
use App\Http\Controllers\GarageLogoController;
use App\Http\Controllers\Settings\PaymentsController;
use App\Http\Controllers\Billing\InvoicePaymentsController;
use App\Http\Controllers\Payments\MpesaCallbackController;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Job;
use App\Models\InventoryItem;
use App\Models\SmsCampaign;

Route::get('/', function () {
    return view('welcome');
});

// -------------------------------------------------
// ✅ PHONE OTP REGISTRATION FLOW (GUEST ONLY)
// -------------------------------------------------
Route::middleware('guest')->group(function () {

    // Step 1: Garage Name + Phone → Send OTP
    Route::get('/register', [PhoneRegisterController::class, 'step1Form'])->name('register');
    Route::post('/register/step1', [PhoneRegisterController::class, 'step1SendOtp'])->name('register.step1');

    // Step 2: OTP verify
    Route::get('/register/verify', [PhoneRegisterController::class, 'otpForm'])->name('register.otp.form');
    Route::post('/register/verify', [PhoneRegisterController::class, 'verifyOtp'])->name('register.otp.verify');

    // Step 3: Complete registration
    Route::get('/register/complete', [PhoneRegisterController::class, 'completeForm'])->name('register.complete.form');
    Route::post('/register/complete', [PhoneRegisterController::class, 'completeStore'])->name('register.complete.store');
});

// ✅ Optional legacy thankyou page AFTER register (only if method exists)
Route::middleware('auth')->get('/register/success', [RegisteredUserController::class, 'thankyou'])
    ->name('register.thankyou');


// -------------------------------------------------
// ✅ Payments settings (AUTH)
// -------------------------------------------------
Route::middleware(['web', 'auth'])
    ->prefix('settings')
    ->name('settings.')
    ->group(function () {
        Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index');
        Route::get('/payments/{paymentConfig}/edit', [PaymentsController::class, 'edit'])->name('payments.edit');
        Route::put('/payments/{paymentConfig}', [PaymentsController::class, 'update'])->name('payments.update');

        Route::post('/payments/{paymentConfig}/test-stk', [PaymentsController::class, 'testStk'])
            ->name('payments.testStk');
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/invoices/{invoice}/pay/stk', [InvoicePaymentsController::class, 'stkPush']);
});

Route::post('/payments/mpesa/stk/callback', [MpesaCallbackController::class, 'stkResult'])
    ->name('payments.mpesa.stk.callback');

Route::middleware(['web','auth'])
  ->post('/invoices/{invoice}/payments/manual', [InvoicePaymentsController::class, 'storeManual'])
  ->name('invoices.payments.manual');

  
// -------------------------------------------
// ✅ SETTINGS (AUTH ONLY) — OUTSIDE subscription.mode
// Reason: garages should still access branding/billing even when locked.
// -------------------------------------------
Route::middleware(['auth'])->group(function () {

    // ✅ Settings Home (hub)
    Route::get('/settings', [SettingsController::class, 'home'])
        ->name('settings.home');

    // ✅ Branding page
    Route::get('/settings/branding', function () {
        return view('settings.branding');
    })->name('settings.branding');

    Route::get('/settings/profile', [SettingsController::class, 'profile'])
        ->name('settings.profile');

    Route::get('/settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');

    Route::post('/settings/profile/legal-documents/{docType}', [SettingsController::class, 'uploadLegalDocument'])
        ->name('settings.profile.legal.upload');

    Route::delete('/settings/profile/legal-documents/{docType}', [SettingsController::class, 'deleteLegalDocument'])
        ->name('settings.profile.legal.delete');

    // ✅ SAVE Garage Profile + Payment Methods
    Route::post('/settings', [SettingsController::class, 'update'])
        ->name('settings.update');

    // Branding: Logo upload/remove
    Route::post('/settings/garage/logo', [GarageLogoController::class, 'store'])
        ->name('garage.logo.store');

    // Insurers under Settings > Preferences
    Route::post('/settings/insurers', [PartnersInsurerController::class, 'storeFromPreferences'])
        ->name('settings.insurers.store');

    Route::delete('/settings/insurers/{id}', [PartnersInsurerController::class, 'destroyFromPreferences'])
        ->name('settings.insurers.destroy');

    Route::delete('/settings/garage/logo', [GarageLogoController::class, 'destroy'])
        ->name('garage.logo.destroy');

    Route::get('/billing', [BillingController::class, 'index'])
        ->name('billing.index');

    Route::get('/billing/locked', function () {
    return view('billing.locked');
    })->name('billing.locked');


    Route::get('/settings/preferences', [SettingsController::class, 'preferences'])
        ->name('settings.preferences');

    Route::post('/settings/preferences', [SettingsController::class, 'updatePreferences'])
        ->name('settings.preferences.update');
    
    Route::prefix('partners')->name('partners.')->group(function () {
        Route::get('insurers', [InsurerController::class, 'index'])->name('insurers.index');
        Route::get('insurers/create', [InsurerController::class, 'create'])->name('insurers.create');
        Route::post('insurers', [InsurerController::class, 'store'])->name('insurers.store');
        Route::get('insurers/{insurer}/edit', [InsurerController::class, 'edit'])->name('insurers.edit');
        Route::put('insurers/{insurer}', [InsurerController::class, 'update'])->name('insurers.update');
        Route::patch('insurers/{insurer}/toggle', [InsurerController::class, 'toggle'])->name('insurers.toggle');
    });

    Route::prefix('insurance')
    ->name('insurance.')
    ->middleware(['auth'])
    ->group(function () {

        Route::post('jobs/{job}/approval-pack/generate', [ApprovalPackController::class, 'generate'])
            ->name('approval-pack.generate');

        Route::post('approval-packs/{pack}/submit', [ApprovalPackController::class, 'submit'])
            ->name('approval-pack.submit');

    });
});

// -------------------------------------------
// ✅ SUPER ADMIN ROUTES (AUTH + isSuperAdmin)
// -------------------------------------------
Route::middleware(['auth', 'isSuperAdmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Garages
        Route::get('/garages', [AdminGarageController::class, 'index'])->name('garages.index');
        Route::get('/garages/create', [AdminGarageController::class, 'create'])->name('garages.create');
        Route::post('/garages', [AdminGarageController::class, 'store'])->name('garages.store');
        Route::get('/garages/{garage}', [AdminGarageController::class, 'show'])->name('garages.show');

        Route::post('/garages/{garage}/extend-trial', [AdminGarageController::class, 'extendTrial'])->name('garages.extend-trial');
        Route::post('/garages/{garage}/activate',    [AdminGarageController::class, 'activate'])->name('garages.activate');
        Route::post('/garages/{garage}/suspend',     [AdminGarageController::class, 'suspend'])->name('garages.suspend');

        // Users
        Route::get('/users',        [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');

        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/activate',[AdminUserController::class, 'activate'])->name('users.activate');
        Route::post('/users/{user}/role',    [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::post('/users/{user}/garage',  [AdminUserController::class, 'updateGarage'])->name('users.garage');

        // 🔥 START IMPERSONATION (SUPER ADMIN ONLY)
        // Start impersonation (by GARAGE)
        Route::post('/garages/{garage}/impersonate', [AdminImpersonationController::class, 'start'])
            ->name('impersonation.start');

        // Stop impersonation
        Route::post('/impersonation/stop', [AdminImpersonationController::class, 'stop'])
            ->name('impersonation.stop');

        // Activity + Settings
        Route::get('/activity', [AdminActivityController::class, 'index'])->name('activity.index');

        // ✅ SMS Settings (Super Admin only)
        Route::post('/settings/sms', [AdminSettingsController::class, 'updateSms'])->name('settings.sms.update');

        Route::get('/settings', [AdminSettingsController::class, 'index'])
            ->name('settings.index');

        Route::resource('organizations', OrganizationController::class)
            ->except(['show','destroy']);

        Route::post(
            'garages/{garage}/organizations',
            [AdminGarageController::class, 'updateOrganizations']
        )->name('admin.garages.organizations.update');
    });


// -------------------------------------------
// 🔁 STOP IMPERSONATION (AUTH ONLY)
// Must be OUTSIDE isSuperAdmin middleware
// -------------------------------------------
Route::middleware('auth')
    ->post('/admin/impersonate/stop', [AdminImpersonationController::class, 'stop'])
    ->name('admin.impersonate.stop');


// -------------------------------------------
// 🔥 DASHBOARD — WITH JOB PIPELINE (AUTH + subscription.mode)
// -------------------------------------------
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'subscription.mode'])
    ->name('dashboard');


// -------------------------------------------
// 📁 DOCUMENTS (AUTH + subscription.mode)
// -------------------------------------------
Route::middleware(['auth', 'subscription.mode'])->prefix('documents')->name('documents.')->group(function () {

    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('/invoices',  [DocumentController::class, 'invoices'])->name('invoices');
    Route::get('/job-cards', [DocumentController::class, 'jobCards'])->name('job-cards');
    Route::get('/receipts',  [DocumentController::class, 'receipts'])->name('receipts');
    Route::get('/other',     [DocumentController::class, 'other'])->name('other');

    Route::get('/{document}/view', [DocumentController::class, 'view'])->name('view');
    Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
    Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');

    // ✅ Bulk actions
    Route::post('/bulk/download', [DocumentController::class, 'bulkDownload'])->name('bulk.download');
    Route::delete('/bulk/delete', [DocumentController::class, 'bulkDelete'])->name('bulk.delete');
});

Route::get('/invoices/{invoice}/share', [InvoiceController::class, 'share'])
    ->middleware('signed')
    ->name('invoices.share');

// -------------------------------------------
// ✅ APP ROUTES (AUTH + subscription.mode)
// -------------------------------------------
Route::middleware(['auth', 'subscription.mode'])->group(function () {

    // Profile
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Customers, Vehicles, Jobs
    Route::resource('customers', CustomerController::class);
    // ✅ Customer-context vehicle create/store (auto-fill + read-only customer)
    Route::get('/customers/{customer}/vehicles/create', [VehicleController::class, 'createForCustomer'])
        ->name('customers.vehicles.create');

    Route::post('/customers/{customer}/vehicles', [VehicleController::class, 'storeForCustomer'])
        ->name('customers.vehicles.store');
    Route::resource('vehicles',  VehicleController::class);

    // -------------------------------------------
    // ✅ PDF ROUTES (AUTH + subscription.mode)
    // NOTE: Locked users will be redirected by EnsureSubscriptionMode.
    // -------------------------------------------
    // Invoice PDF (saved to documents by your controller)
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoices.pdf');

    // Receipt PDF (saved to documents by your controller)
    Route::get('/invoices/{invoice}/receipt-pdf', [InvoiceController::class, 'receiptPdf'])
        ->name('invoices.receipt-pdf');
});

/*
|--------------------------------------------------------------------------
| JOB CREATE WIZARD (STEP FLOW)
|--------------------------------------------------------------------------
*/
Route::prefix('jobs/create')->name('jobs.create.')->group(function () {

    Route::get('step-1',  [\App\Http\Controllers\Jobs\JobCreateWizardController::class, 'step1'])
        ->name('step1');

    Route::post('step-1', [\App\Http\Controllers\Jobs\JobCreateWizardController::class, 'postStep1'])
        ->name('step1.post');

    Route::get('step-2',  [\App\Http\Controllers\Jobs\JobCreateWizardController::class, 'step2'])
        ->name('step2');

    Route::post('step-2', [\App\Http\Controllers\Jobs\JobCreateWizardController::class, 'postStep2'])
        ->name('step2.post');

    // STEP 3 (GET form)
    Route::get('step-3', [\App\Http\Controllers\Jobs\JobCreateWizardController::class, 'step3'])
        ->name('step3');

    // STEP 3 (POST save/continue)
    Route::post('step-3', [\App\Http\Controllers\Jobs\JobCreateWizardController::class, 'postStep3'])
        ->name('step3.post');

    // STEP 4 (Review / Success)
    Route::get('step-4', [\App\Http\Controllers\Jobs\JobCreateWizardController::class, 'step4'])
        ->name('step4');

    // ✅ CONFIRM (creates the job via legacy store)
    Route::post('confirm', [\App\Http\Controllers\Jobs\JobCreateWizardController::class, 'confirm'])
        ->name('confirm');

    Route::get('resume', [JobCreateWizardController::class, 'resume'])
        ->name('resume');

    Route::post('draft/save', [JobCreateWizardController::class, 'saveDraft'])
        ->name('draft.save');
});

// -----------------------------
// VAULT (Garage Media Library)
// -----------------------------
Route::middleware(['auth'])->prefix('vault')->name('vault.')->group(function () {

    // Library
    Route::get('/', [VaultController::class, 'index'])->name('index');

    // Upload into vault (creates media_items)
    Route::post('/upload', [VaultController::class, 'store'])->name('upload');

    // Attach from vault
    Route::prefix('attach')->name('attach.')->group(function () {
        Route::post('/job/{job}', [VaultAttachController::class, 'attachToJob'])->name('job');
        Route::post('/vehicle/{vehicle}', [VaultAttachController::class, 'attachToVehicle'])->name('vehicle');
    });

    // Detach (unlink, not delete)
    Route::prefix('detach')->name('detach.')->group(function () {
        Route::delete('/job/{job}/{mediaItem}', [VaultAttachController::class, 'detachFromJob'])->name('job');
        Route::delete('/vehicle/{vehicle}/{mediaItem}', [VaultAttachController::class, 'detachFromVehicle'])->name('vehicle');
    });
});

Route::prefix('jobs/{job}/insurance')
    ->name('jobs.insurance.')
    ->middleware(['web','auth'])
    ->group(function () {

        // =============================
        // Inspection
        // =============================
        Route::post('inspection/save', [JobController::class, 'insuranceInspectionSave'])->name('inspection.save');
        Route::post('inspection/complete', [JobController::class, 'insuranceInspectionComplete'])->name('inspection.complete');
        Route::get('inspection/checklist', [JobController::class, 'insuranceInspectionChecklistLoad'])->name('inspection.checklist');
        Route::post('inspection/checklist', [JobController::class, 'insuranceInspectionChecklistSave'])->name('inspection.checklist.save');

        // =============================
        // Vault
        // =============================
        Route::get('vault', [JobController::class, 'insuranceVaultPicker'])->name('vault');
        Route::post('vault/upload', [JobController::class, 'insuranceVaultUpload'])->name('vault.upload');
        Route::post('vault/attach', [\App\Http\Controllers\Vault\VaultAttachController::class, 'attachToJob'])
            ->name('vault.attach');
        Route::post('vault/detach', [JobController::class, 'insuranceVaultDetachFromInspection'])->name('vault.detach');

        // =============================
        // Invoice
        // =============================
        Route::post('invoice/create', [InsuranceInvoiceController::class, 'create'])->name('invoice.create');
        Route::post('invoice/generate', [InsuranceInvoiceController::class, 'generate'])->name('invoice.generate');

        // =============================
        // Approval
        // =============================
        Route::post('approval/submit', [InsuranceApprovalController::class, 'submit'])->name('approval.submit');
        Route::post('approval/approve', [InsuranceApprovalController::class, 'approve'])->name('approval.approve');
        Route::post('approval/reject', [InsuranceApprovalController::class, 'reject'])->name('approval.reject');

        // =============================
        // Claim  ✅ 
        // =============================
        Route::get('claim', [InsuranceClaimController::class, 'show'])->name('claim.show');      // full page
        Route::get('claim/card', [InsuranceClaimController::class, 'card'])->name('claim.card'); // partial
        Route::post('claim/save', [InsuranceClaimController::class, 'save'])->name('claim.save');
        Route::post('claim/submit', [InsuranceClaimController::class, 'submit'])->name('claim.submit');

        // Claim Pack ✅
        Route::get('claim/pack', [InsuranceClaimController::class, 'downloadPack'])->name('claim.pack');
        Route::post('claim/completion-photos/attach', [InsuranceClaimController::class, 'attachCompletionPhotos'])
            ->name('claim.completion-photos.attach');
        
        Route::post('claim/generate', [\App\Http\Controllers\Insurance\InsuranceClaimController::class, 'generatePack'])
            ->name('claim.generate');
        
        Route::post('claim/submit', [InsuranceClaimController::class, 'submit'])->name('claim.submit');
    });

Route::resource('jobs', JobController::class)->except(['create']);
    // Job Card (HTML)
    Route::get('/jobs/{job}/job-card', [JobController::class, 'jobCard'])
        ->name('jobs.job-card');

    // Job Card PDF
    Route::get('/jobs/{job}/job-card/download', [JobController::class, 'downloadJobCard'])
        ->name('jobs.job-card.download');

    Route::patch('/jobs/{job}/status', [JobController::class, 'updateStatus'])
        ->name('jobs.status.update');

    Route::get('/jobs/{job}/insurance', [JobController::class, 'insuranceShow'])
        ->name('jobs.insurance.show');

    Route::get('/jobs/{job}/insurance/quotation-card', [\App\Http\Controllers\Insurance\InsuranceCardsController::class, 'quotationCard'])
        ->name('jobs.insurance.quotation-card');

    Route::get('/jobs/{job}/insurance/approval-card', [\App\Http\Controllers\Insurance\InsuranceCardsController::class, 'approvalCard'])
        ->name('jobs.insurance.approval-card');

    Route::get('jobs/{job}/insurance/repair-card', [\App\Http\Controllers\Insurance\InsuranceCardsController::class, 'repairCard'])
    ->name('jobs.insurance.repair-card');

    // ✅ Approval Pack PDF (download/share)
    Route::get('/jobs/{job}/insurance/approval/packs/{pack}/pdf', [ApprovalPackController::class, 'pdf'])
        ->name('insurance.approval.packs.pdf');

    Route::post('/jobs/insurance/{job}/completion/complete', [\App\Http\Controllers\Insurance\InsuranceCompletionController::class, 'complete'])
    ->name('jobs.insurance.completion.complete');
        
    // Inventory
    Route::resource('inventory-items', InventoryItemController::class);

    Route::get('/inventory-items/{inventoryItem}/adjust', [InventoryItemController::class, 'adjustForm'])
        ->name('inventory-items.adjust-form');

    Route::post('/inventory-items/{inventoryItem}/adjust', [InventoryItemController::class, 'adjust'])
        ->name('inventory-items.adjust');

    // Invoices
    Route::post('/jobs/{job}/invoice', [InvoiceController::class, 'storeFromJob'])
        ->name('jobs.invoice.store');

    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);

    Route::post('/invoices/{invoice}/issue', [InvoiceController::class, 'issue'])
        ->name('invoices.issue');

    Route::post('/invoices/{invoice}/payment', [InvoiceController::class, 'updatePayment'])
        ->name('invoices.updatePayment');

    Route::post('/invoices/{invoice}/items', [InvoiceController::class, 'addItem'])
        ->name('invoices.items.store');

    Route::put('/invoices/{invoice}/items/{item}', [InvoiceController::class, 'updateItem'])
        ->name('invoices.items.update');

    Route::delete('/invoices/{invoice}/items/{item}', [InvoiceController::class, 'deleteItem'])
        ->name('invoices.items.delete');

    Route::post('/invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])
        ->name('invoices.sendEmail');

    Route::post('/invoices/{invoice}/send-whatsapp', [InvoiceController::class, 'sendWhatsApp'])
        ->name('invoices.sendWhatsApp');

    // SMS Campaigns
    Route::resource('sms-campaigns', SmsCampaignController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::post('sms-campaigns/{campaign}/send', [SmsCampaignController::class, 'send'])
        ->name('sms-campaigns.send');

    // Quick customer add
    Route::post('/customers/quick-store', [CustomerController::class, 'quickStore'])
        ->name('customers.quick-store');

    Route::post('/invoices/{invoice}/share-email', [\App\Http\Controllers\InvoiceController::class, 'shareEmail'])
        ->name('invoices.shareEmail');

    Route::get('/insurance/approval-packs/{pack}/share', [ApprovalPackController::class, 'share'])
    ->middleware('signed')
    ->name('insurance.approval-packs.share');

    Route::get('/insurance/approval-packs/{pack}/pdf', [ApprovalPackController::class, 'sharePdf'])
    ->middleware('signed')
    ->name('insurance.approval-packs.pdf.share');

    Route::get('/insurance/claims/{job}/pack/{version}/zip', [InsuranceClaimController::class, 'downloadArchivedPack'])
        ->middleware('signed')
        ->name('insurance.claim-pack.zip');

    Route::get('/insurance/claims/{job}/pack/{version}/share', [InsuranceClaimController::class, 'shareArchivedPack'])
        ->middleware('signed')
        ->name('insurance.claim-pack.share');

    // Repair (keep these INSIDE the same group as jobs.insurance.show)
    Route::prefix('/jobs/{job}/insurance/repair')->group(function () {
        Route::post('/start', [InsuranceRepairController::class, 'start'])
            ->name('jobs.insurance.repair.start');

        Route::post('/complete', [InsuranceRepairController::class, 'complete'])
            ->name('jobs.insurance.repair.complete');

        Route::post('/item/{item}/status', [InsuranceRepairController::class, 'updateStatus'])
            ->name('jobs.insurance.repair.updateStatus');
    });

    Route::get('insurance/claim-packs/{job}/share', 
        [\App\Http\Controllers\Insurance\InsuranceClaimController::class, 'sharePack']
    )->name('insurance.claim-packs.share')
    ->middleware('signed');

Route::get('/__diag/csrf', function () {
    return response()->json([
        'host'       => request()->getHost(),
        'scheme'     => request()->getScheme(),
        'user_id'    => auth()->id(),
        'session_id' => session()->getId(),
        'csrf'       => csrf_token(),
        'cookies'    => array_keys(request()->cookies->all()),
    ]);
})->middleware(['web', 'auth']);


// Breeze auth routes
require __DIR__.'/auth.php';
