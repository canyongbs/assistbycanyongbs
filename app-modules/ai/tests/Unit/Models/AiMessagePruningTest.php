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

use AdvisingApp\Ai\Models\AiMessage;
use AdvisingApp\Ai\Models\AiMessageFile;
use Illuminate\Database\Console\PruneCommand;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\artisan;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

it('properly prunes AiMessage models', function (AiMessage $message, bool $shouldPrune) {
    assertTrue($message->exists);

    Event::fake();

    artisan(PruneCommand::class, [
        '--model' => AiMessage::class,
    ]);

    if ($shouldPrune) {
        assertNull($message->fresh());
    } else {
        assertTrue($message->fresh()->exists);
    }
})->with([
    'Not soft deleted' => [
        fn () => AiMessage::factory()->create(),
        false,
    ],
    'Not soft deleted with a file' => [
        fn () => AiMessage::factory()->has(AiMessageFile::factory(), 'files')->create(),
        false,
    ],
    'Soft deleted recently' => [
        fn () => AiMessage::factory()->create(['deleted_at' => now()]),
        false,
    ],
    'Soft deleted recently with a file' => [
        fn () => AiMessage::factory()->has(AiMessageFile::factory(), 'files')->create(['deleted_at' => now()]),
        false,
    ],
    'Soft deleted more than a week ago' => [
        fn () => AiMessage::factory()->create(['deleted_at' => now()->subDays(8)]),
        true,
    ],
    'Soft deleted more than a week ago with messages' => [
        fn () => AiMessage::factory()->has(AiMessageFile::factory(), 'files')->create(['deleted_at' => now()->subDays(8)]),
        false,
    ],
    'Soft deleted more than a week ago with a soft deleted file' => [
        fn () => AiMessage::factory()->has(AiMessageFile::factory()->state(['deleted_at' => now()]), 'files')->create(['deleted_at' => now()->subDays(8)]),
        false,
    ],
]);
