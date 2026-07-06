<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

trait RedirectsWithOptionalPrint
{
    protected function indexRedirectWithOptionalPrint(
        Request $request,
        string $indexRoute,
        string $printRoute,
        mixed $printModel,
        string $successMessage,
    ): RedirectResponse {
        $redirect = redirect()->route($indexRoute)->with('success', $successMessage);

        if ($request->boolean('print_after_save')) {
            $redirect->with('print_url', route($printRoute, $printModel));
        }

        return $redirect;
    }
}
