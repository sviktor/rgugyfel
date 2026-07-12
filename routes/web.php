<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractRequestController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Royal Telekom Ügyfélkapu - web routes
|--------------------------------------------------------------------------
|
| Auth: cus_users accounts via the `customer` guard (config/auth.php). The
| portal pages require a logged-in + e-mail-verified customer. Auth forms POST
| via AJAX and return JSON (see resources/js/auth-forms.js).
|
*/

// ---- Guest-only auth screens ----
// The POST submits carry a per-IP `throttle` in addition to the per-account
// lockout (cus_login_attempts): the lockout skips non-existent accounts, so the
// rate limit is what caps password-spraying / registration abuse / reset-mail
// bombing at the source. Same inline-throttle pattern as verification.resend.
Route::middleware('guest:customer')->group(function () {
	Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
	Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');

	Route::get('/regisztracio',  [RegisterController::class, 'show'])->name('register');
	Route::post('/regisztracio', [RegisterController::class, 'store'])->middleware('throttle:6,10')->name('register.submit');

	Route::get('/elfelejtett-jelszo',  [PasswordResetController::class, 'requestForm'])->name('forgot');
	Route::post('/elfelejtett-jelszo', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,10')->name('forgot.submit');

	Route::get('/jelszo-visszaallitas/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
	Route::post('/jelszo-visszaallitas',        [PasswordResetController::class, 'reset'])->name('password.update');
});

// ---- E-mail verification (public; the link is signed, resend is throttled) ----
Route::get('/regisztracio/megerosites', [EmailVerificationController::class, 'notice'])->name('register.verify.notice');
Route::get('/email-megerosites/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');
Route::post('/email-megerosites/ujrakuldes', [EmailVerificationController::class, 'resend'])
	->middleware('throttle:6,1')->name('verification.resend');

// ---- Legal pages (public; CMS-driven once the rgadmin Ügyfélkapu editor lands) ----
Route::get('/aszf',        [LegalController::class, 'terms'])->name('terms');
Route::get('/adatvedelem', [LegalController::class, 'privacy'])->name('privacy');

// ---- Operator impersonation ("Belépés az ügyfélkapuba") ----
// Public accept route: an rgadmin operator is redirected here with a single-use,
// short-lived DB token (shared mc_rg) that logs them in as the linked account.
// Throttled per IP; the token itself is one-time + expiring.
Route::get('/operator-belepes/{token}', [ImpersonationController::class, 'accept'])
	->middleware('throttle:10,1')->name('imperson.accept');

// ---- Portal pages (logged-in + verified customer) ----
Route::middleware(['auth:customer', 'customer.verified'])->group(function () {
	Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

	Route::get('/',               [PortalController::class, 'dashboard'])->name('dashboard');
	Route::get('/szamlak',        [PortalController::class, 'invoices'])->name('invoices');
	Route::get('/szamlak/{id}/letoltes', [PortalController::class, 'invoiceDownload'])->whereNumber('id')->name('invoices.download');
	Route::get('/szerzodeseim',   [PortalController::class, 'plans'])->name('plans');
	Route::get('/dokumentumok',   [PortalController::class, 'docs'])->name('docs');
	Route::get('/dokumentumok/{id}/letoltes', [PortalController::class, 'docDownload'])->whereNumber('id')->name('docs.download');
	Route::get('/profil',         [PortalController::class, 'profile'])->name('profile');
	Route::post('/profil/szemelyes',   [PortalController::class, 'profilePersonalSave'])->name('profile.personal');
	Route::post('/profil/jelszo',      [PortalController::class, 'profilePasswordSave'])->name('profile.password');
	Route::post('/profil/ertesitesek', [PortalController::class, 'notificationsSave'])->name('profile.notifications');

	// Dashboard "Szerződés hozzárendelése" - files a pending contract request.
	Route::post('/szerzodes-igenyles', [ContractRequestController::class, 'store'])->name('contract.request');

	// Topbar customer switcher - picks the ACTIVE customer of a multi-customer account.
	Route::post('/ugyfel-valtas', [PortalController::class, 'switchCustomer'])->name('customer.switch');

	// Leave operator impersonation mode (the topbar banner's "Kilépés" button).
	Route::post('/operator-kilepes', [ImpersonationController::class, 'exit'])->name('imperson.exit');
});
