{{--
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
--}}
<span wire:key="paginator-{{ $pageName }}-page{{ $page }}">
    @if ($page == $currentPage)
        <span aria-current="page">
            <span
                class="relative -ml-px inline-flex cursor-default items-center bg-transparent px-4 py-2 text-sm font-medium leading-5 text-primary-500 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:ring-gray-700 hover:dark:bg-gray-700"
            >{{ $page }}</span>
        </span>
    @else
        <button
            class="relative -ml-px inline-flex cursor-pointer items-center bg-transparent px-4 py-2 text-sm font-medium leading-5 text-gray-500 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 dark:text-gray-400 dark:ring-gray-700 hover:dark:bg-gray-700"
            type="button"
            aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
            wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
        >
            {{ $page }}
        </button>
    @endif
</span>
