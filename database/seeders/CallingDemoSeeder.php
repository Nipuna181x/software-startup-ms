<?php

namespace Database\Seeders;

use App\Enums\Calling\CallOutcome;
use App\Models\Calling\Business;
use App\Models\Calling\CallCategory;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds sample Calling module data for the demo organization only.
 *
 * Depends on DemoOrganizationSeeder having already run; if the demo
 * organization does not exist (for example, DEMO_ADMIN_PASSWORD was not
 * set), this seeder skips entirely rather than creating one of its own.
 */
class CallingDemoSeeder extends Seeder
{
    /**
     * @var array<string, array<string, array<int, array{name: string, number: string}>>>
     */
    private const STRUCTURE = [
        'Marketing Agencies' => [
            'Digital Agencies' => [
                ['name' => 'Brightpath Digital', 'number' => '0771234567'],
                ['name' => 'Skyline Media Group', 'number' => '0779876543'],
                ['name' => 'Northline Marketing', 'number' => '0712223344'],
            ],
            'Print & Signage Agencies' => [
                ['name' => 'Redline Print Studio', 'number' => '0765554433'],
                ['name' => 'Coastal Signage Co', 'number' => '0701112233'],
            ],
        ],
        'Small Businesses' => [
            'Phone Shops' => [
                ['name' => 'ABC Mobile World', 'number' => '0712345678'],
                ['name' => 'Metro Cellular', 'number' => '0719998877'],
                ['name' => 'Prime Phones', 'number' => '0754443322'],
            ],
            'Cushion Shops' => [
                ['name' => 'Comfort Home Furnishings', 'number' => '0723334455'],
                ['name' => 'Soft Living Interiors', 'number' => '0787776655'],
            ],
            'Spare Parts Shops' => [
                ['name' => 'Reliable Auto Spares', 'number' => '0741112222'],
                ['name' => 'Quickfix Parts Depot', 'number' => '0776665544'],
            ],
        ],
    ];

    private int $sequence = 0;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', config('startsuite.demo.slug'))
            ->first();

        if ($organization === null) {
            $this->command->warn('Skipping Calling demo seed: the demo organization does not exist yet.');

            return;
        }

        $admin = User::query()
            ->where('organization_id', $organization->id)
            ->where('email', config('startsuite.demo.email'))
            ->first();

        Tenancy::actAs($organization, function () use ($organization, $admin): void {
            foreach (self::STRUCTURE as $categoryName => $types) {
                $category = CallCategory::firstOrCreate(
                    ['organization_id' => $organization->id, 'slug' => Str::slug($categoryName)],
                    ['name' => $categoryName],
                );

                foreach ($types as $typeName => $businesses) {
                    $type = $category->businessTypes()->firstOrCreate(
                        ['slug' => Str::slug($typeName)],
                        ['organization_id' => $organization->id, 'name' => $typeName],
                    );

                    foreach ($businesses as $index => $data) {
                        if ($type->businesses()->where('name', $data['name'])->exists()) {
                            continue;
                        }

                        $business = $type->businesses()->create([
                            'organization_id' => $organization->id,
                            'name' => $data['name'],
                            'address' => fake()->streetAddress().', '.fake()->city(),
                            'owner_name' => fake()->name(),
                            'website' => fake()->optional(0.6)->domainName(),
                            'notes' => fake()->optional(0.5)->sentence(),
                        ]);

                        $business->phones()->create([
                            'organization_id' => $organization->id,
                            'number' => $data['number'],
                            'is_primary' => true,
                        ]);

                        $this->seedCallHistory($business, $organization, $admin, $this->sequence++);
                    }
                }
            }

            $this->command->info('Calling module demo data seeded.');
        });
    }

    /**
     * Give a mix of businesses a realistic call history, so the demo shows
     * both the "To Call" and "Answered" lists with real data, not just
     * empty states.
     */
    private function seedCallHistory(Business $business, Organization $organization, ?User $admin, int $sequence): void
    {
        if ($admin === null) {
            return;
        }

        $phone = $business->phones()->first();

        // Leave roughly a third of businesses untouched, so "To call" is
        // never empty in the demo.
        if ($sequence % 3 === 0) {
            return;
        }

        // Another third: one or two unanswered attempts, still "To call".
        if ($sequence % 3 === 1) {
            $business->calls()->create([
                'organization_id' => $organization->id,
                'business_phone_id' => $phone->id,
                'user_id' => $admin->id,
                'outcome' => CallOutcome::NotAnswered,
                'called_at' => now()->subDays(2),
            ]);

            return;
        }

        // The rest: answered, with a rotating outcome so the demo shows all three.
        $outcomes = [CallOutcome::Pending, CallOutcome::Interested, CallOutcome::Rejected];
        $outcome = $outcomes[intdiv($sequence, 3) % count($outcomes)];

        $business->calls()->create([
            'organization_id' => $organization->id,
            'business_phone_id' => $phone->id,
            'user_id' => $admin->id,
            'outcome' => CallOutcome::NotAnswered,
            'called_at' => now()->subDays(3),
        ]);

        $business->calls()->create([
            'organization_id' => $organization->id,
            'business_phone_id' => $phone->id,
            'user_id' => $admin->id,
            'outcome' => $outcome,
            'note' => match ($outcome) {
                CallOutcome::Pending => 'Asked to call back next week.',
                CallOutcome::Interested => 'Wants a proposal sent over email.',
                CallOutcome::Rejected => 'Already working with another provider.',
            },
            'follow_up_at' => $outcome === CallOutcome::Pending ? now()->addWeek()->toDateString() : null,
            'called_at' => now()->subDay(),
        ]);
    }
}
