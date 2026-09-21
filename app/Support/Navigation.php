<?php

namespace App\Support;

use App\Models\User;
use Closure;
use Illuminate\Support\Collection;

/**
 * Registry of dashboard sidebar items.
 *
 * Modules added later register their own entries from a service provider:
 *
 *     Navigation::register(
 *         label: 'Campaigns',
 *         route: 'campaigns.index',
 *         icon: 'megaphone',
 *         group: 'Modules',
 *         visible: fn (User $user) => $user->isSuperAdmin(),
 *     );
 *
 * The sidebar renders whatever is in the registry, so no layout edits are
 * needed to add a module.
 */
class Navigation
{
    /**
     * @var array<int, array{
     *     label: string,
     *     route: string,
     *     icon: string,
     *     group: string,
     *     pattern: string,
     *     order: int,
     *     visible: (Closure(User): bool)|null
     * }>
     */
    private static array $items = [];

    /**
     * Register a sidebar item.
     *
     * @param  (Closure(User): bool)|null  $visible
     */
    public static function register(
        string $label,
        string $route,
        string $icon = 'square',
        string $group = 'Platform',
        ?string $pattern = null,
        int $order = 100,
        ?Closure $visible = null,
    ): void {
        foreach (self::$items as $existing) {
            if ($existing['route'] === $route) {
                return;
            }
        }

        self::$items[] = [
            'label' => $label,
            'route' => $route,
            'icon' => $icon,
            'group' => $group,
            'pattern' => $pattern ?? $route.'*',
            'order' => $order,
            'visible' => $visible,
        ];
    }

    /**
     * Get the sidebar items the given user may see, grouped by heading.
     *
     * @return Collection<array-key, Collection<int, array{
     *     label: string,
     *     route: string,
     *     icon: string,
     *     group: string,
     *     pattern: string,
     *     order: int,
     *     visible: (Closure(User): bool)|null
     * }>>
     */
    public static function for(User $user): Collection
    {
        return collect(self::$items)
            ->filter(fn (array $item): bool => $item['visible'] === null || ($item['visible'])($user))
            ->sortBy('order')
            ->groupBy('group');
    }

    /**
     * Remove every registered item. Intended for tests.
     */
    public static function flush(): void
    {
        self::$items = [];
    }
}
