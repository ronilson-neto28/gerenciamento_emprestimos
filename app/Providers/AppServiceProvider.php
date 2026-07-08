<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\Emprestimo;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->isAdmin() ? true : null;
        });

        Gate::define('manage-cobradores', fn (User $user) => $user->isAdmin());
        Gate::define('view-dashboard', fn (User $user) => $user->isAdmin());
        Gate::define('view-financeiro', fn (User $user) => $user->isAdmin());
        Gate::define('manage-financeiro', fn (User $user) => $user->isAdmin());
        Gate::define('delete-clientes', fn (User $user) => $user->isAdmin());
        Gate::define('delete-emprestimos', fn (User $user) => $user->isAdmin());
        Gate::define('create-clientes', fn (User $user) => in_array($user->role, ['admin', 'cobrador'], true));
        Gate::define('create-emprestimos', fn (User $user) => in_array($user->role, ['admin', 'cobrador'], true));
        Gate::define('view-relatorios', fn (User $user) => in_array($user->role, ['admin', 'cobrador'], true));

        Gate::define('access-cliente', fn (User $user, Cliente $cliente) => AdminAccess::canAccessClient($user, $cliente));
        Gate::define('access-emprestimo', fn (User $user, Emprestimo $emprestimo) => AdminAccess::canAccessLoan($user, $emprestimo));
    }
}
