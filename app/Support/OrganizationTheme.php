<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the viewer's organization and its theme once per request.
 *
 * Views are rendered many times over (layouts, components, Flux internals), so
 * the lookup and the colour maths are memoised rather than repeated.
 */
class OrganizationTheme
{
    private bool $resolved = false;

    private ?Organization $organization = null;

    private string $style = '';

    /**
     * Get the viewer's organization, if they are signed in.
     */
    public function organization(): ?Organization
    {
        $this->resolve();

        return $this->organization;
    }

    /**
     * Get the CSS custom properties for the viewer's organization.
     */
    public function style(): string
    {
        $this->resolve();

        return $this->style;
    }

    /**
     * Resolve the organization and build its style declaration once.
     */
    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }

        $this->resolved = true;
        $this->organization = Auth::guard('web')->user()?->organization;

        if ($this->organization === null) {
            return;
        }

        $declarations = [];

        foreach ($this->organization->themeVariables() as $name => $value) {
            $declarations[] = $name.':'.$value;
        }

        $this->style = implode(';', $declarations);
    }
}
