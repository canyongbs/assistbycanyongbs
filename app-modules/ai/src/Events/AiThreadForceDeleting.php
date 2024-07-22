<?php

namespace AdvisingApp\Ai\Events;

use AdvisingApp\Ai\Models\AiThread;
use Illuminate\Foundation\Events\Dispatchable;
use AdvisingApp\Ai\Listeners\AiThreadCascadeDeleteAiMessages;

class AiThreadForceDeleting
{
    use Dispatchable;

    public const LISTENERS = [
        AiThreadCascadeDeleteAiMessages::class,
    ];

    public function __construct(public AiThread $aiThread) {}
}
