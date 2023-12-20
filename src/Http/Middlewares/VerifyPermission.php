<?php

namespace dmitryrogolev\Can\Http\Middlewares;

use Closure;
use dmitryrogolev\Can\Contracts\Permissionable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class VerifyPermission
{
    protected Guard $auth;

    /**
     * Создать новый экземпляр посредника.
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Обработать входящий запрос.
     */
    public function handle(Request $request, Closure $next, mixed ...$permission): mixed
    {
        if ($this->auth->check() && $this->auth->user() instanceof Permissionable && $this->auth->user()->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'Доступ запрещен. Нет требуемого разрешения "'.implode(',', $permission).'".');
    }
}
