<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait LogsFluxioActivity
{
    protected function logFluxioActivity(
        Request $request,
        string $menu,
        string $action,
        ?Model $subject = null,
        array $extra = [],
    ): void {
        $logger = activity('fluxio');

        if ($request->user()) {
            $logger->causedBy($request->user());
        }

        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger
            ->event($action)
            ->withProperties(array_merge([
                'menu' => $menu,
                'action' => $action,
                'ip' => $request->ip(),
                'device' => $this->resolveDevice($request->userAgent() ?? ''),
                'user_agent' => $request->userAgent(),
            ], $extra))
            ->log($action);
    }

    protected function resolveDevice(string $userAgent): string
    {
        if (Str::contains($userAgent, ['iPhone', 'Android', 'Mobile'])) {
            return 'Mobile';
        }

        if (Str::contains($userAgent, ['iPad', 'Tablet'])) {
            return 'Tablet';
        }

        return 'Desktop';
    }

    protected function flashToast(string $type, string $message): array
    {
        return ['toast' => ['type' => $type, 'message' => $message]];
    }
}
