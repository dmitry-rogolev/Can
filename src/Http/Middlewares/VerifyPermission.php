<?php

namespace dmitryrogolev\Can\Http\Middlewares;

use dmitryrogolev\Can\Contracts\Permissionable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class VerifyPermission
{
    protected Guard $auth;

    /**
     * Create a new filter instance.
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  int|string  $permission
     */
    public function handle(Request $request, \Closure $next, ...$permission): mixed
    {
        $permission = implode(',', $permission);

        if ($this->auth->check() && $this->auth->user() instanceof Permissionable && $this->auth->user()->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, sprintf('Доступ запрещен. Нет требуемого разрешения "%s".', $permission));
    }
}
