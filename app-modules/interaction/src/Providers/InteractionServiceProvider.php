<?php

/*
<COPYRIGHT>

    Copyright © 2016-2024, Canyon GBS LLC. All rights reserved.

    Advising App™ is licensed under the Elastic License 2.0. For more details,
    see https://github.com/canyongbs/advisingapp/blob/main/LICENSE.

    Notice:

    - You may not provide the software to third parties as a hosted or managed
      service, where the service provides users with access to any substantial set of
      the features or functionality of the software.
    - You may not move, change, disable, or circumvent the license key functionality
      in the software, and you may not remove or obscure any functionality in the
      software that is protected by the license key.
    - You may not alter, remove, or obscure any licensing, copyright, or other notices
      of the licensor in the software. Any use of the licensor’s trademarks is subject
      to applicable law.
    - Canyon GBS LLC respects the intellectual property rights of others and expects the
      same in return. Canyon GBS™ and Advising App™ are registered trademarks of
      Canyon GBS LLC, and we are committed to enforcing and protecting our trademarks
      vigorously.
    - The software solution, including services, infrastructure, and code, is offered as a
      Software as a Service (SaaS) by Canyon GBS LLC.
    - Use of this software implies agreement to the license terms and conditions as stated
      in the Elastic License 2.0.
    - Test

    For more information or inquiries please visit our website at
    https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace AdvisingApp\Interaction\Providers;

use Filament\Panel;
use App\Concerns\GraphSchemaDiscovery;
use Illuminate\Support\ServiceProvider;
use AdvisingApp\Interaction\InteractionPlugin;
use AdvisingApp\Interaction\Models\Interaction;
use AdvisingApp\Interaction\Models\InteractionType;
use Illuminate\Database\Eloquent\Relations\Relation;
use AdvisingApp\Interaction\Models\InteractionDriver;
use AdvisingApp\Interaction\Models\InteractionStatus;
use AdvisingApp\Interaction\Models\InteractionOutcome;
use AdvisingApp\Interaction\Models\InteractionCampaign;
use AdvisingApp\Interaction\Models\InteractionRelation;
use AdvisingApp\Authorization\AuthorizationRoleRegistry;
use AdvisingApp\Interaction\Observers\InteractionObserver;
use AdvisingApp\Authorization\AuthorizationPermissionRegistry;
use AdvisingApp\Interaction\Enums\InteractionStatusColorOptions;

class InteractionServiceProvider extends ServiceProvider
{
    use GraphSchemaDiscovery;

    public function register()
    {
        Panel::configureUsing(fn (Panel $panel) => $panel->plugin(new InteractionPlugin()));
    }

    public function boot()
    {
        Relation::morphMap([
            'interaction' => Interaction::class,
            'interaction_campaign' => InteractionCampaign::class,
            'interaction_driver' => InteractionDriver::class,
            'interaction_outcome' => InteractionOutcome::class,
            'interaction_relation' => InteractionRelation::class,
            'interaction_status' => InteractionStatus::class,
            'interaction_type' => InteractionType::class,
        ]);

        $this->registerRolesAndPermissions();
        $this->registerObservers();

        $this->discoverSchema(__DIR__ . '/../../graphql/interaction.graphql');

        $this->registerEnum(InteractionStatusColorOptions::class);
    }

    protected function registerRolesAndPermissions()
    {
        $permissionRegistry = app(AuthorizationPermissionRegistry::class);

        $permissionRegistry->registerApiPermissions(
            module: 'interaction',
            path: 'permissions/api/custom'
        );

        $permissionRegistry->registerWebPermissions(
            module: 'interaction',
            path: 'permissions/web/custom'
        );

        $roleRegistry = app(AuthorizationRoleRegistry::class);

        $roleRegistry->registerApiRoles(
            module: 'interaction',
            path: 'roles/api'
        );

        $roleRegistry->registerWebRoles(
            module: 'interaction',
            path: 'roles/web'
        );
    }

    protected function registerObservers(): void
    {
        Interaction::observe(InteractionObserver::class);
    }
}
