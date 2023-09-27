<?php

namespace Assist\Engagement\Filament\Concerns;

use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Assist\Engagement\Enums\EngagementDeliveryStatus;

// TODO Re-use this trait across other places where infolist is rendered
trait EngagementInfolist
{
    public function engagementInfolist(): array
    {
        return [
            TextEntry::make('user.name')
                ->label('Created By'),
            Fieldset::make('Content')
                ->schema([
                    TextEntry::make('subject'),
                    TextEntry::make('body'),
                ]),
            RepeatableEntry::make('deliverables')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('channel'),
                    IconEntry::make('delivery_status')
                        ->icon(fn (EngagementDeliveryStatus $state): string => match ($state) {
                            EngagementDeliveryStatus::SUCCESSFUL => 'heroicon-o-check-circle',
                            EngagementDeliveryStatus::AWAITING => 'heroicon-o-clock',
                            EngagementDeliveryStatus::FAILED => 'heroicon-o-x-circle',
                        })
                        ->color(fn (EngagementDeliveryStatus $state): string => match ($state) {
                            EngagementDeliveryStatus::SUCCESSFUL => 'success',
                            EngagementDeliveryStatus::AWAITING => 'info',
                            EngagementDeliveryStatus::FAILED => 'danger',
                        }),
                    TextEntry::make('delivered_at'),
                    TextEntry::make('delivery_response'),
                ])
                ->columns(2),
        ];
    }
}
