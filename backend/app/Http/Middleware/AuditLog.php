<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

final class AuditLog
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $throwable) {
            $this->writeAuditEntry($request, null, $throwable);

            throw $throwable;
        }

        $this->writeAuditEntry($request, $response);

        return $response;
    }

    private function writeAuditEntry(Request $request, ?Response $response, ?Throwable $throwable = null): void
    {
        if (! $this->shouldLog($request, $response, $throwable)) {
            return;
        }

        $user = $request->user();
        $statusCode = $response?->getStatusCode() ?? ($throwable !== null ? $this->statusCodeForThrowable($throwable) : 500);
        $route = $request->route();
        $routeName = is_object($route) ? $route->getName() : null;
        $outcome = $statusCode >= 400 ? 'failure' : 'success';
        $event = ($routeName ?? 'request') . '.' . $outcome;

        $properties = [
            'request_id' => (string) $request->attributes->get('request_id', $request->header('X-Request-Id', '')),
            'method' => $request->method(),
            'path' => '/' . $request->path(),
            'route_name' => $routeName,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $statusCode,
            'outcome' => $outcome,
            'district_ulid' => $this->routeModelUlid($request->route('district')),
            'customer_ulid' => $this->routeModelUlid($request->route('customer')),
            'debt_ulid' => $this->routeModelUlid($request->route('debt')),
            'payment_ulid' => $this->routeModelUlid($request->route('payment')),
            'subject_user_ulid' => $this->routeModelUlid($request->route('user')),
            'input' => $this->sanitizedInput($request),
        ];

        if ($user instanceof User) {
            $properties['actor'] = [
                'user_ulid' => $user->ulid,
                'email' => $user->email,
            ];
        }

        if ($throwable !== null) {
            $properties['exception'] = [
                'class' => $throwable::class,
                'message' => $throwable->getMessage(),
            ];
        }

        activity('security')
            ->event($event)
            ->causedBy($user instanceof User ? $user : null)
            ->withProperties($properties)
            ->log($routeName ?? $request->path());
    }

    private function shouldLog(Request $request, ?Response $response, ?Throwable $throwable): bool
    {
        if (! $request->is('api/*')) {
            return false;
        }

        if ($throwable !== null) {
            return true;
        }

        $method = $request->method();
        $routeName = $request->route()?->getName() ?? '';
        $statusCode = $response?->getStatusCode() ?? 500;

        if ($statusCode >= 400) {
            return true;
        }

        if (str_starts_with($routeName, 'api.v1.auth.')) {
            return true;
        }

        return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function statusCodeForThrowable(Throwable $throwable): int
    {
        if ($throwable instanceof HttpException) {
            return $throwable->getStatusCode();
        }

        return 500;
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizedInput(Request $request): array
    {
        $input = $request->except([
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'mfa_secret',
            'remember_token',
        ]);

        return $this->sanitizeValue($input);
    }

    /**
     * TODO: recursive request sanitization must accept and return arbitrary
     * request payload values after removing sensitive keys.
     *
     * @param  mixed  $value
     * @return mixed
     */
    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->sanitizeValue($item);
            }

            return $sanitized;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return $value;
    }

    private function routeModelUlid(object|string|null $model): ?string
    {
        return match (true) {
            $model instanceof Customer => $model->ulid,
            $model instanceof Debt => $model->ulid,
            $model instanceof District => $model->ulid,
            $model instanceof Payment => $model->ulid,
            $model instanceof User => $model->ulid,
            default => null,
        };
    }
}
