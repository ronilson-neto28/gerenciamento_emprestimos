<?php

use App\Http\Controllers\Admin\ClienteController;
use App\Http\Controllers\Admin\CobradorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmprestimoController;
use App\Http\Controllers\Admin\FinanceiroController;
use App\Http\Controllers\Admin\RelatorioController;
use App\Http\Controllers\Admin\Api\Clientes\DestroyController as ClientesDestroyController;
use App\Http\Controllers\Admin\Api\Clientes\ShowController as ClientesShowController;
use App\Http\Controllers\Admin\Api\Clientes\StoreController as ClientesStoreController;
use App\Http\Controllers\Admin\Api\Clientes\UpdateController as ClientesUpdateController;
use App\Http\Controllers\Admin\Api\Cobradores\IndexController as CobradoresIndexController;
use App\Http\Controllers\Admin\Api\Emprestimos\DestroyController as EmprestimosDestroyController;
use App\Http\Controllers\Admin\Api\Emprestimos\ShowController as EmprestimosShowController;
use App\Http\Controllers\Admin\Api\Emprestimos\StoreController as EmprestimosStoreController;
use App\Http\Controllers\Admin\Api\Emprestimos\UpdateController as EmprestimosUpdateController;
use App\Http\Controllers\Admin\Api\Emprestimos\Parcelas\IndexController as EmprestimosParcelasIndexController;
use App\Http\Controllers\Admin\Api\Emprestimos\Parcelas\ReceiveController as EmprestimosParcelasReceiveController;
use App\Http\Controllers\Admin\Api\Emprestimos\Parcelas\SyncController as EmprestimosParcelasSyncController;
use App\Http\Controllers\Admin\Api\Financeiro\Lancamentos\StoreController as FinanceiroLancamentosStoreController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyRegistrationCodeController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(auth()->user()?->isAdmin() ? 'admin.dashboard' : 'admin.emprestimos')
        : redirect()->route('login');
});

Route::get('/admin/mongo-ping', function () {
    abort_unless(app()->environment('local'), 404);

    try {
        DB::connection('mongodb')->command(['ping' => 1]);
        return response()->json(['ok' => true]);
    } catch (\Throwable $e) {
        report($e);

        return response()->json(['ok' => false], 500);
    }
});

Route::prefix('assets/admin')->name('assets.admin.')->group(function () {
    Route::get('/admin.css', function () {
        $path = resource_path('css/admin.css');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('css');

    Route::get('/dashboard.css', function () {
        $path = resource_path('css/admin/dashboard.css');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('dashboard_css');

    Route::get('/clientes.css', function () {
        $path = resource_path('css/admin/clientes.css');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('clientes_css');

    Route::get('/emprestimos.css', function () {
        $path = resource_path('css/admin/emprestimos.css');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('emprestimos_css');

    Route::get('/relatorios.css', function () {
        $path = resource_path('css/admin/relatorios.css');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('relatorios_css');

    Route::get('/financeiro.css', function () {
        $path = resource_path('css/admin/financeiro.css');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('financeiro_css');

    Route::get('/cobradores.css', function () {
        $path = resource_path('css/admin/cobradores.css');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('cobradores_css');

    Route::get('/cliente.js', function () {
        $path = resource_path('js/admin/cliente.js');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('cliente');

    Route::get('/emprestimo.js', function () {
        $path = resource_path('js/admin/emprestimo.js');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('emprestimo');

    Route::get('/financeiro.js', function () {
        $path = resource_path('js/admin/financeiro.js');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('financeiro');

    Route::get('/admin.js', function () {
        $path = resource_path('js/admin/admin.js');

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    })->name('admin_js');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/register/verify', [VerifyRegistrationCodeController::class, 'create'])->name('register.verify');
    Route::post('/register/verify', [VerifyRegistrationCodeController::class, 'store'])->name('register.verify.store');
    Route::post('/register/verify/resend', [VerifyRegistrationCodeController::class, 'resend'])->name('register.verify.resend');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:6,1');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard')->middleware('can:view-dashboard');
    Route::get('/clientes', ClienteController::class)->name('clientes');
    Route::get('/emprestimos', EmprestimoController::class)->name('emprestimos');
    Route::get('/relatorios', RelatorioController::class)->name('relatorios')->middleware(['admin.finance', 'can:view-relatorios']);
    Route::get('/cobradores', [CobradorController::class, 'index'])->name('cobradores')->middleware('can:manage-cobradores');
    Route::post('/cobradores', [CobradorController::class, 'store'])->name('cobradores.store')->middleware('can:manage-cobradores');
    Route::get('/financeiro', FinanceiroController::class)->name('financeiro')->middleware(['admin.finance', 'can:view-financeiro']);
});

Route::prefix('admin/api')->middleware('auth')->name('admin.api.')->group(function () {
    Route::post('/clientes', ClientesStoreController::class)->name('clientes.store')->middleware('can:create-clientes');
    Route::get('/clientes/{id}', ClientesShowController::class)->name('clientes.show');
    Route::patch('/clientes/{id}', ClientesUpdateController::class)->name('clientes.update');
    Route::delete('/clientes/{id}', ClientesDestroyController::class)->name('clientes.destroy')->middleware(['admin.delete', 'can:delete-clientes']);

    Route::get('/cobradores', CobradoresIndexController::class)->name('cobradores.index')->middleware('can:create-emprestimos');

    Route::post('/emprestimos', EmprestimosStoreController::class)->name('emprestimos.store')->middleware('can:create-emprestimos');
    Route::get('/emprestimos/{id}', EmprestimosShowController::class)->name('emprestimos.show');
    Route::patch('/emprestimos/{id}', EmprestimosUpdateController::class)->name('emprestimos.update');
    Route::delete('/emprestimos/{id}', EmprestimosDestroyController::class)->name('emprestimos.destroy')->middleware(['admin.delete', 'can:delete-emprestimos']);
    Route::get('/emprestimos/{id}/parcelas', EmprestimosParcelasIndexController::class)->name('emprestimos.parcelas.index');
    Route::post('/emprestimos/{id}/parcelas/sync', EmprestimosParcelasSyncController::class)->name('emprestimos.parcelas.sync');
    Route::post('/parcelas/{id}/receber', EmprestimosParcelasReceiveController::class)->name('parcelas.receber');

    Route::post('/financeiro/lancamentos', FinanceiroLancamentosStoreController::class)->name('financeiro.lancamentos.store')->middleware(['admin.finance', 'can:manage-financeiro']);
});
