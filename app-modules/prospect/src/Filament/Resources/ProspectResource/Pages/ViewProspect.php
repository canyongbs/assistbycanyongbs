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

namespace AdvisingApp\Prospect\Filament\Resources\ProspectResource\Pages;

use Filament\Actions\EditAction;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use AdvisingApp\Prospect\Models\Prospect;
use AdvisingApp\Prospect\Concerns\ProspectHolisticViewPage;
use AdvisingApp\Prospect\Filament\Resources\ProspectResource;
use AdvisingApp\Notification\Filament\Actions\SubscribeHeaderAction;
use AdvisingApp\Prospect\Filament\Resources\ProspectResource\Actions\ConvertToStudent;
use AdvisingApp\Prospect\Filament\Resources\ProspectResource\Actions\ProspectTagsAction;
use AdvisingApp\Prospect\Filament\Resources\ProspectResource\Actions\DisassociateStudent;
use AdvisingApp\Prospect\Filament\Resources\ProspectResource\Schemas\ProspectProfileInfolist;
use AdvisingApp\StudentDataModel\Filament\Resources\StudentResource\Pages\Concerns\HasStudentHeader;

class ViewProspect extends ViewRecord
{
    use ProspectHolisticViewPage;
    use HasStudentHeader;

    protected static string $resource = ProspectResource::class;

    // TODO: Automatically set from Filament
    protected static ?string $navigationLabel = 'View';

    protected static string $view = 'prospect::filament.resources.prospect-resource.view-prospect';

    public string $name = 'prospect';

    public function profile(Infolist $infolist): Infolist
    {
        return ProspectProfileInfolist::configure($infolist);
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            ProspectTagsAction::make()->visible(fn (): bool => auth()->user()?->can('prospect.tags.manage')),
            ConvertToStudent::make()->visible(fn (Prospect $record) => ! $record->student()->exists()),
            DisassociateStudent::make()->visible(fn (Prospect $record) => $record->student()->exists()),
            EditAction::make(),
            SubscribeHeaderAction::make(),
        ];
    }
}
