<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Lista danych wejściowych, które nigdy nie będą zapisywane w sesji podczas wyjątków walidacji.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Rejestracja callbacków obsługi wyjątków dla aplikacji.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
        });
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->view('errors.404', [], 404);
        }

        if ($e instanceof AuthorizationException) {
            return response()->view('errors.403', [], 403);
        }

        if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        }

        if ($e instanceof TokenMismatchException) {
            return response()->view('errors.419', [], 419);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->view('errors.405', [], 405);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return response()->view('errors.429', [], 429);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->view('errors.404', [], 404);
        }

        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            if (view()->exists("errors.{$statusCode}")) {
                return response()->view("errors.{$statusCode}", [], $statusCode);
            }
        }

        if ($e instanceof ValidationException) {
            return parent::render($request, $e);
        }

        if (config('app.debug')) {
            return parent::render($request, $e);
        }

        return response()->view('errors.500', [], 500);
    }

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Brak autoryzacji.'], 401);
        }

        return response()->view('errors.401', [], 401);
    }

    /**
     * Obsługa wyjątków API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleApiException($request, Throwable $exception)
    {
        $statusCode = 500;
        $message = $exception->getMessage() ?: 'Wystąpił nieoczekiwany błąd.';

        if ($exception instanceof AuthenticationException) {
            $statusCode = 401;
            $message = 'Brak autoryzacji.';
        } elseif ($exception instanceof AuthorizationException) {
            $statusCode = 403;
            $message = 'Brak uprawnień do wykonania tej operacji.';
        } elseif ($exception instanceof ValidationException) {
            $statusCode = 422;
            return response()->json([
                'message' => 'Dane walidacji są niepoprawne.',
                'errors' => $exception->errors(),
            ], $statusCode);
        } elseif ($exception instanceof NotFoundHttpException) {
            $statusCode = 404;
            $message = 'Nie znaleziono żądanego zasobu.';
        } elseif ($exception instanceof MethodNotAllowedHttpException) {
            $statusCode = 405;
            $message = 'Metoda HTTP jest niedozwolona dla tego zasobu.';
        } elseif ($exception instanceof TooManyRequestsHttpException) {
            $statusCode = 429;
            $message = 'Zbyt wiele żądań. Spróbuj ponownie później.';
        } elseif ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
        }

        // Ukrywanie szczegółów błędu 500 na produkcji
        if ($statusCode === 500 && !config('app.debug')) {
            $message = 'Wystąpił wewnętrzny błąd serwera.';
        }

        return response()->json([
            'message' => $message,
            'status' => $statusCode
        ], $statusCode);
    }
}
