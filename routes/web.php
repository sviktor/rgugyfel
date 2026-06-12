<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Royal Telekom Ügyfélkapu - web routes
|--------------------------------------------------------------------------
|
| MVP - no real auth yet (the `customers` table comes from rgadmin once
| that migration lands). For now the login/register forms fake-succeed
| and redirect into the portal. Every portal route is publicly reachable
| but visually requires a "session" (we'll add a guard later).
|
*/

// ---- Auth screens (no middleware) ----
Route::get('/login',                 [AuthController::class, 'showLogin'])    ->name('login');
Route::post('/login',                [AuthController::class, 'login'])        ->name('login.submit');
Route::get('/regisztracio',          [AuthController::class, 'showRegister']) ->name('register');
Route::post('/regisztracio',         [AuthController::class, 'register'])     ->name('register.submit');
Route::get('/elfelejtett-jelszo',    [AuthController::class, 'showForgot'])   ->name('forgot');
Route::post('/elfelejtett-jelszo',   [AuthController::class, 'forgot'])       ->name('forgot.submit');
Route::post('/logout',               [AuthController::class, 'logout'])       ->name('logout');

// ---- Portal pages ----
Route::get('/',               [PortalController::class, 'dashboard'])->name('dashboard');
Route::get('/szamlak',        [PortalController::class, 'invoices']) ->name('invoices');
Route::get('/szerzodeseim',   [PortalController::class, 'plans'])    ->name('plans');
Route::get('/forgalom',       [PortalController::class, 'usage'])    ->name('usage');
Route::get('/hibabejelentes', [PortalController::class, 'tickets'])  ->name('tickets');
Route::get('/dokumentumok',   [PortalController::class, 'docs'])     ->name('docs');
Route::get('/profil',         [PortalController::class, 'profile'])  ->name('profile');
