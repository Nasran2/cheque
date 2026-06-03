<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChequeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

Route::get('/', function () {
    return view('welcome');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'login' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

    if (! Auth::attempt([
        $loginField => $credentials['login'],
        'password' => $credentials['password'],
    ], $request->boolean('remember'))) {
        throw ValidationException::withMessages([
            'login' => 'These credentials do not match our records.',
        ]);
    }

    $request->session()->regenerate();

    return redirect()->intended(route('dashboard'));
})->name('login.attempt');

Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/cheques', [ChequeController::class, 'index'])->name('cheques.index');
    Route::get('/cheques/create', [ChequeController::class, 'create'])->name('cheques.create');
    Route::get('/cheques/pending', [ChequeController::class, 'pending'])->name('cheques.pending');
    Route::get('/cheques/passed', [ChequeController::class, 'passed'])->name('cheques.passed');
    Route::get('/cheques/returned', [ChequeController::class, 'returned'])->name('cheques.returned');
    Route::get('/cheques/upcoming', [ChequeController::class, 'upcoming'])->name('cheques.upcoming');
    Route::post('/cheques', [ChequeController::class, 'store'])->name('cheques.store');
    Route::post('/cheques/{cheque}/mark-passed', [ChequeController::class, 'markPassed'])->name('cheques.mark-passed');
    Route::post('/cheques/{cheque}/mark-returned', [ChequeController::class, 'markReturned'])->name('cheques.mark-returned');
    Route::get('/cheques/{cheque}', [ChequeController::class, 'show'])->name('cheques.show');

    // AJAX Search Routes
    Route::get('/ajax/customers/search', [ChequeController::class, 'searchCustomers'])->name('ajax.customers.search');
    Route::get('/ajax/suppliers/search', [ChequeController::class, 'searchSuppliers'])->name('ajax.suppliers.search');
    Route::get('/ajax/customer-cheques/search', [ChequeController::class, 'searchCustomerCheques'])->name('ajax.customer-cheques.search');
    Route::get('/ajax/customer-cheques/{cheque}', [ChequeController::class, 'customerChequeDetails'])->name('ajax.customer-cheques.show');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/all-cheques', [ReportController::class, 'allCheques'])->name('reports.all-cheques');
    Route::get('/reports/pending-cheques', [ReportController::class, 'pendingCheques'])->name('reports.pending-cheques');
    Route::get('/reports/passed-cheques', [ReportController::class, 'passedCheques'])->name('reports.passed-cheques');
    Route::get('/reports/returned-cheques', [ReportController::class, 'returnedCheques'])->name('reports.returned-cheques');
    Route::get('/reports/upcoming-cheques', [ReportController::class, 'upcomingCheques'])->name('reports.upcoming-cheques');
    Route::get('/reports/bank-wise', [ReportController::class, 'bankWiseCheques'])->name('reports.bank-wise');
    Route::get('/reports/customer-wise', [ReportController::class, 'customerWiseCheques'])->name('reports.customer-wise');
    Route::get('/reports/supplier-wise', [ReportController::class, 'supplierWiseCheques'])->name('reports.supplier-wise');
    Route::get('/reports/monthly-summary', [ReportController::class, 'monthlySummary'])->name('reports.monthly-summary');
    Route::get('/reports/{reportType}/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/reports/{reportType}/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // SMS Settings & Templates
    Route::get('/settings/sms', [SmsController::class, 'settings'])->name('settings.sms');
    Route::post('/settings/sms', [SmsController::class, 'updateSettings'])->name('settings.sms.update');
    Route::get('/settings/sms-templates', [SmsController::class, 'templates'])->name('settings.sms.templates');
    Route::post('/settings/sms-templates/{template}', [SmsController::class, 'updateTemplate'])->name('settings.sms.templates.update');
    Route::post('/settings/sms-templates/{template}/toggle', [SmsController::class, 'toggleTemplate'])->name('settings.sms.templates.toggle');
    Route::post('/settings/sms/test', [SmsController::class, 'testSms'])->name('settings.sms.test');
    Route::get('/settings/sms/logs', [SmsController::class, 'logs'])->name('settings.sms.logs');

    // Manual cheque SMS
    Route::post('/cheques/{cheque}/send-sms', [SmsController::class, 'sendManualSms'])->name('cheques.send-sms');

    Route::resource('customers', CustomerController::class);
    Route::resource('suppliers', SupplierController::class);
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
