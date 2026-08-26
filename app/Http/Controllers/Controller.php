<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Finish a write.
     *
     * Opened in a modal, the browser only needs to know it worked and what to
     * say; it refreshes the list itself. Otherwise this is the usual redirect,
     * so every screen keeps working with JavaScript off.
     */
    protected function done(string $message, string $redirect): JsonResponse|RedirectResponse
    {
        if (is_modal()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return redirect($redirect)->with('status', $message);
    }
}
