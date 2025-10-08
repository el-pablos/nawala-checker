<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

abstract class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a success JSON response.
     */
    protected function successResponse(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error JSON response.
     */
    protected function errorResponse(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Return a redirect with success message.
     */
    protected function redirectWithSuccess(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->with('success', $message);
    }

    /**
     * Return a redirect with error message.
     */
    protected function redirectWithError(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->with('error', $message);
    }
}

