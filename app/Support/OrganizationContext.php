<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the viewer's organization once per request.
 *
 * Views are rendered many times over (layouts, components, Flux internals), so
 * the lookup is memoised rather than repeated.
 */
class OrganizationContext
{
    private bool $resolved = false;

    private ?Organization $organization = null;

    /**
     * Get the viewer's organization, if they are signed in.
     */
    public function organization(): ?Organization
    {
        $this->resolve();

        return $this->organization;
    }

    /**
     * Resolve the organization once.
     */
    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }

        $this->resolved = true;
        $this->organization = Auth::guard('web')->user()?->organization;
    }
}
