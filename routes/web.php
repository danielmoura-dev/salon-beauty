<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login')
);
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfessionalController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\BookingLinkController;
use App\Http\Controllers\Public\BookingController as PublicBookingController;
use App\Http\Controllers\Public\BookingAuthController as PublicBookingAuthController;


// Verifica código de afiliado (público, sem auth)
Route::get('/affiliate-code/check', function (\Illuminate\Http\Request $request) {
    $code = strtoupper(trim($request->query('code', '')));
    if (! $code) {
        return response()->json(['valid' => null]);
    }
    $affiliate = \App\Models\Affiliate::where('code', $code)->where('is_active', true)->first();
    if ($affiliate) {
        return response()->json(['valid' => true, 'discount_pct' => (float) $affiliate->discount_pct]);
    }
    return response()->json(['valid' => false]);
})->name('affiliate.check');

// --- Rotas públicas ---
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

    Route::get('/password/forgot', [ForgotPasswordController::class, 'showForm'])->name('password.request');
    Route::post('/password/forgot', [ForgotPasswordController::class, 'sendLink'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Webhooks — sem CSRF, sem auth
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])->name('webhooks.stripe');
Route::post('/webhooks/mercadopago', [WebhookController::class, 'mercadoPago'])->name('webhooks.mercadopago');

// --- Verificação de email por código ---
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', fn() => view('auth.verify-email'))->name('verification.notice');
    Route::post('/email/verify', function (\Illuminate\Http\Request $request) {
        $request->validate(['code' => 'required|string|size:6']);
        $user = $request->user();
        if (! $user->verifyCode($request->code)) {
            return back()->withErrors(['code' => 'Código inválido ou expirado.']);
        }
        $user->markEmailAsVerified();
        $user->update(['email_verification_code' => null, 'email_verification_code_expires_at' => null]);
        return redirect()->route('onboarding');
    })->name('verification.verify');
    Route::post('/email/resend', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('success', 'Novo código enviado para o seu e-mail!');
    })->middleware('throttle:6,1')->name('verification.send');
});

// --- Onboarding (autenticado + email verificado) ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'complete'])->name('onboarding.complete');
});

// --- App principal (autenticado + verificado + assinatura ativa) ---
Route::middleware(['auth', 'verified', 'subscription.active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/agenda', [AppointmentController::class, 'index'])->name('agenda');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/form-data', [OrderController::class, 'formData'])->name('orders.form-data');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/data', [OrderController::class, 'data'])->name('orders.data');
    Route::post('/orders/{order}/items', [OrderController::class, 'addItem'])->name('orders.items.add');
    Route::patch('/orders/{order}/items/{item}', [OrderController::class, 'updateItem'])->name('orders.items.update')->scopeBindings();
    Route::delete('/orders/{order}/items/{item}', [OrderController::class, 'removeItem'])->name('orders.items.remove')->scopeBindings();
    Route::post('/orders/{order}/payments', [OrderController::class, 'addPayment'])->name('orders.payments.add');
    Route::post('/orders/{order}/payments/clear', [OrderController::class, 'clearPayments'])->name('orders.payments.clear');
    Route::post('/orders/{order}/close', [OrderController::class, 'close'])->name('orders.close');
    Route::post('/orders/{order}/reopen', [OrderController::class, 'reopen'])->name('orders.reopen');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Configurações
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/advanced', [SettingsController::class, 'updateAdvanced'])->name('settings.advanced');

    // Assinatura
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/stripe', [SubscriptionController::class, 'checkoutStripe'])->name('subscription.stripe');
    Route::post('/subscription/pix', [SubscriptionController::class, 'pixCheckout'])->name('subscription.pix')->middleware('throttle:5,10');
    Route::get('/subscription/pix/status', [SubscriptionController::class, 'pixStatus'])->name('subscription.pix.status');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/portal', [SubscriptionController::class, 'stripePortal'])->name('subscription.portal');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelStripe'])->name('subscription.cancel');

    // Clientes
    Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::get('/clients', [ClientController::class, 'index'])->name('clients');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    // Profissionais
    Route::get('/professionals', [ProfessionalController::class, 'index'])->name('professionals');
    Route::post('/professionals', [ProfessionalController::class, 'store'])->name('professionals.store');
    Route::put('/professionals/{professional}', [ProfessionalController::class, 'update'])->name('professionals.update');
    Route::delete('/professionals/{professional}', [ProfessionalController::class, 'destroy'])->name('professionals.destroy');

    // Comissões
    Route::get('/professionals/commissions', [CommissionController::class, 'index'])->name('professionals.commissions');
    Route::get('/professionals/{professional}/commissions/detail', [CommissionController::class, 'detail'])->name('professionals.commissions.detail');
    Route::post('/professionals/{professional}/commissions/pay', [CommissionController::class, 'pay'])->name('professionals.commissions.pay');
    Route::delete('/professionals/commissions/{payment}/cancel', [CommissionController::class, 'cancel'])->name('professionals.commissions.cancel');

    // Vales
    Route::get('/professionals/vouchers', [VoucherController::class, 'index'])->name('professionals.vouchers');
    Route::post('/professionals/{professional}/vouchers', [VoucherController::class, 'store'])->name('professionals.vouchers.store');
    Route::delete('/professionals/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('professionals.vouchers.destroy');

    // Serviços
    Route::get('/services', [ServiceController::class, 'index'])->name('services');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

    // Produtos
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Categorias (API interna)
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Despesas
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::post('/expenses/{expense}/toggle', [ExpenseController::class, 'togglePaid'])->name('expenses.toggle');
    Route::delete('/expenses/{expense}/cancel-recurrence', [ExpenseController::class, 'cancelRecurrence'])->name('expenses.cancel-recurrence');

    // Relatórios
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    // Meu Link (link público de agendamento)
    Route::get('/booking-link',                                        [BookingLinkController::class, 'index'])->name('booking-link');
    Route::post('/booking-link',                                       [BookingLinkController::class, 'update'])->name('booking-link.update');
    Route::post('/booking-link/professionals/{professional}/toggle',   [BookingLinkController::class, 'toggleProfessional'])->name('booking-link.professional.toggle');
    Route::post('/booking-link/professionals/{professional}/services', [BookingLinkController::class, 'updateProfessionalServices'])->name('booking-link.professional.services');
});

// --- Página pública de agendamento (sem auth, scoped por slug) ---
Route::prefix('agendar/{slug}')->name('public.booking.')->group(function () {
    Route::get('/',                                  [PublicBookingController::class, 'show'])->name('show');
    Route::post('/auth/check',                       [PublicBookingAuthController::class, 'check'])->name('auth.check');
    Route::post('/auth/register',                    [PublicBookingAuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/logout',                      [PublicBookingAuthController::class, 'logout'])->name('auth.logout');
    Route::get('/professionals',                     [PublicBookingController::class, 'professionals'])->name('professionals');
    Route::get('/slots',                             [PublicBookingController::class, 'slots'])->name('slots');
    Route::post('/book',                             [PublicBookingController::class, 'book'])->name('book');
    Route::get('/agendamentos',                      [PublicBookingController::class, 'myAppointments'])->name('my-appointments');
    Route::post('/agendamentos/{appointment}/cancel',[PublicBookingController::class, 'cancelAppointment'])->name('cancel');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/subscription/expired', fn() => view('subscription.expired'))->name('subscription.expired');
// --- Admin panel (software owner only) ---
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AffiliateController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',                         [AdminController::class, 'dashboard'])->name('dashboard');
    Route::patch('/tenants/{tenant}/trial', [AdminController::class, 'extendTrial'])->name('tenants.trial');

    // Afiliados
    Route::get('/affiliates',                         [AffiliateController::class, 'index'])->name('affiliates.index');
    Route::post('/affiliates',                        [AffiliateController::class, 'store'])->name('affiliates.store');
    Route::get('/affiliates/{affiliate}',             [AffiliateController::class, 'show'])->name('affiliates.show');
    Route::patch('/affiliates/{affiliate}',           [AffiliateController::class, 'update'])->name('affiliates.update');
    Route::post('/affiliates/{affiliate}/toggle',     [AffiliateController::class, 'toggleActive'])->name('affiliates.toggle');
    Route::post('/affiliates/{affiliate}/pay',        [AffiliateController::class, 'markPaid'])->name('affiliates.pay');
});
