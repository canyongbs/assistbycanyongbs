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

    For more information or inquiries please visit our website at
    https://www.canyongbs.com or contact us via email at legal@canyongbs.com.

</COPYRIGHT>
*/

namespace AdvisingApp\Ai\Policies;

use AdvisingApp\Ai\Models\Prompt;
use AdvisingApp\Authorization\Enums\LicenseType;
use App\Concerns\PerformsLicenseChecks;
use App\Models\Authenticatable;
use App\Policies\Contracts\PerformsChecksBeforeAuthorization;
use Illuminate\Auth\Access\Response;

class PromptPolicy implements PerformsChecksBeforeAuthorization
{
    use PerformsLicenseChecks;

    public function before(Authenticatable $authenticatable): ?Response
    {
        if (! is_null($response = $this->hasLicenses($authenticatable, LicenseType::ConversationalAi))) {
            return $response;
        }

        return null;
    }

    public function viewAny(Authenticatable $authenticatable): Response
    {
        return $authenticatable->canOrElse(
            abilities: 'prompt.view-any',
            denyResponse: 'You do not have permission to view prompts.'
        );
    }

    public function view(Authenticatable $authenticatable, Prompt $prompt): Response
    {
        return $authenticatable->canOrElse(
            abilities: ["prompt.{$prompt->getKey()}.view"],
            denyResponse: 'You do not have permission to view this prompt.'
        );
    }

    public function create(Authenticatable $authenticatable): Response
    {
        return $authenticatable->canOrElse(
            abilities: 'prompt.create',
            denyResponse: 'You do not have permission to create prompts.'
        );
    }

    public function update(Authenticatable $authenticatable, Prompt $prompt): Response
    {
        if ($prompt->is_smart) {
            return auth()->user()->hasRole(Authenticatable::SUPER_ADMIN_ROLE)
                ? Response::allow()
                : Response::deny('You do not have permission to update this smart prompt.');
        }

        return $authenticatable->canOrElse(
            abilities: ["prompt.{$prompt->getKey()}.update"],
            denyResponse: 'You do not have permission to update this prompt.'
        );
    }

    public function delete(Authenticatable $authenticatable, Prompt $prompt): Response
    {
        if ($prompt->is_smart) {
            return auth()->user()->hasRole(Authenticatable::SUPER_ADMIN_ROLE)
                ? Response::allow()
                : Response::deny('You do not have permission to delete this smart prompt.');
        }

        return $authenticatable->canOrElse(
            abilities: ["prompt.{$prompt->getKey()}.delete"],
            denyResponse: 'You do not have permission to delete this prompt.'
        );
    }

    public function restore(Authenticatable $authenticatable, Prompt $prompt): Response
    {
        return $authenticatable->canOrElse(
            abilities: ["prompt.{$prompt->getKey()}.restore"],
            denyResponse: 'You do not have permission to restore this prompt.'
        );
    }

    public function forceDelete(Authenticatable $authenticatable, Prompt $prompt): Response
    {
        return $authenticatable->canOrElse(
            abilities: ["prompt.{$prompt->getKey()}.force-delete"],
            denyResponse: 'You do not have permission to permanently delete this prompt.'
        );
    }
}
