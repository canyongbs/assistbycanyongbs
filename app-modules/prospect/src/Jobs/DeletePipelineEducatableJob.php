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

namespace AdvisingApp\Prospect\Jobs;

use AdvisingApp\Prospect\Models\Pipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeletePipelineEducatableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Pipeline $pipeline
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::table('pipeline_educatable')
                ->where('educatable_type', 'prospect')
                ->orderBy('created_at','DESC')
                ->where('pipeline_id', $this->pipeline->getKey())
                ->whereNotIn('educatable_id', function($query) {
                    $query->select('id')
                        ->fromSub(
                            $this->pipeline?->segment->retrieveEducatablesRecords()->select('id'), 
                            'educatables'
                        );
                })
                ->chunk(100,function($educatables) {
                    $educatables->each(function($educatable) {
                        $this->pipeline?->educatables()->detach($educatable->educatable_id);
                    });
                });
    }

    public function failed(?Throwable $exception): void
    {
        Log::debug(__('Failed to sync pipeline at time of removal prospect :pipeline',[
            'pipeline' => $this->pipeline->name
        ]));

        report($exception); 
    }
}
