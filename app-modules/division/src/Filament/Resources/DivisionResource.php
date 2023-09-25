<?php

namespace Assist\Division\Filament\Resources;

use Filament\Resources\Resource;
use Assist\Division\Models\Division;
use App\Filament\Pages\Concerns\HasNavigationGroup;
use Assist\Division\Filament\Resources\DivisionResource\Pages\EditDivision;
use Assist\Division\Filament\Resources\DivisionResource\Pages\ViewDivision;
use Assist\Division\Filament\Resources\DivisionResource\Pages\ListDivisions;
use Assist\Division\Filament\Resources\DivisionResource\Pages\CreateDivision;

class DivisionResource extends Resource
{
    use HasNavigationGroup;

    protected static ?string $model = Division::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDivisions::route('/'),
            'create' => CreateDivision::route('/create'),
            'view' => ViewDivision::route('/{record}'),
            'edit' => EditDivision::route('/{record}/edit'),
        ];
    }
}
