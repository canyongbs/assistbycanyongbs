<?php

/*
<COPYRIGHT>

    Copyright © 2016-2025, Canyon GBS LLC. All rights reserved.

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

namespace AdvisingApp\StudentDataModel\Policies;

use AdvisingApp\StudentDataModel\Models\Program;
use AdvisingApp\StudentDataModel\Models\Student;
use AdvisingApp\StudentDataModel\Settings\ManageStudentConfigurationSettings;
use App\Models\Authenticatable;
use Illuminate\Auth\Access\Response;

class ProgramPolicy
{
    public function before(Authenticatable $authenticatable): ?Response
    {
        if (! $authenticatable->hasLicense(Student::getLicenseType())) {
            return Response::deny('You are not licensed for the Retention CRM.');
        }

        return null;
    }

    public function viewAny(Authenticatable $authenticatable): Response
    {
        return $authenticatable->canOrElse(
            abilities: 'program.view-any',
            denyResponse: 'You do not have permission to view programs.'
        );
    }

    public function view(Authenticatable $authenticatable, Program $program): Response
    {
        return $authenticatable->canOrElse(
            abilities: "program.{$program->getKey()}.view",
            denyResponse: 'You do not have permission to view this program.'
        );
    }

    public function create(Authenticatable $authenticatable): Response
    {
        if (! app(ManageStudentConfigurationSettings::class)->is_enabled) {
            return Response::deny('Student data configuration is not enabled.');
        }

        return $authenticatable->canOrElse(
            abilities: 'program.create',
            denyResponse: 'You do not have permission to create programs.'
        );
    }

    public function update(Authenticatable $authenticatable, Program $program): Response
    {
        if (! app(ManageStudentConfigurationSettings::class)->is_enabled) {
            return Response::deny('Student data configuration is not enabled.');
        }

        return $authenticatable->canOrElse(
            abilities: "program.{$program->getKey()}.update",
            denyResponse: 'You do not have permission to update this program.'
        );
    }

    public function delete(Authenticatable $authenticatable, Program $program): Response
    {
        if (! app(ManageStudentConfigurationSettings::class)->is_enabled) {
            return Response::deny('Student data configuration is not enabled.');
        }

        return $authenticatable->canOrElse(
            abilities: "program.{$program->getKey()}.delete",
            denyResponse: 'You do not have permission to delete this program.'
        );
    }

    public function restore(Authenticatable $authenticatable, Program $program): Response
    {
        if (! app(ManageStudentConfigurationSettings::class)->is_enabled) {
            return Response::deny('Student data configuration is not enabled.');
        }

        return $authenticatable->canOrElse(
            abilities: "program.{$program->getKey()}.restore",
            denyResponse: 'You do not have permission to restore this program.'
        );
    }

    public function forceDelete(Authenticatable $authenticatable, Program $program): Response
    {
        if (! app(ManageStudentConfigurationSettings::class)->is_enabled) {
            return Response::deny('Student data configuration is not enabled.');
        }

        return $authenticatable->canOrElse(
            abilities: "program.{$program->getKey()}.force-delete",
            denyResponse: 'You do not have permission to force delete this student.'
        );
    }

    public function import(Authenticatable $authenticatable): Response
    {
        if (! app(ManageStudentConfigurationSettings::class)->is_enabled) {
            return Response::deny('Student data configuration is not enabled.');
        }

        return $authenticatable->canOrElse(
            abilities: 'program.import',
            denyResponse: 'You do not have permission to import programs.'
        );
    }
}
