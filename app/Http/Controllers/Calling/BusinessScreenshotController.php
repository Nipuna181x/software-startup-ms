<?php

namespace App\Http\Controllers\Calling;

use App\Http\Controllers\Controller;
use App\Models\Calling\BusinessScreenshot;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves business screenshots from the private disk.
 *
 * The route model binding for BusinessScreenshot resolves through the
 * organization global scope, so a screenshot belonging to another
 * organization is simply not found (404), never served. Guests never reach
 * this controller at all: the route requires authentication.
 */
class BusinessScreenshotController extends Controller
{
    /**
     * Stream the screenshot file to the browser.
     */
    public function show(BusinessScreenshot $screenshot): StreamedResponse
    {
        Gate::authorize('view', $screenshot->business);

        $disk = Storage::disk(config('startsuite.business_screenshot.disk'));

        abort_unless($disk->exists($screenshot->path), 404);

        return $disk->response($screenshot->path);
    }
}
