<?php

namespace App\Filament\Platform\Resources\Branches;

use App\Filament\Platform\Resources\Branches\Pages\CreateBranch;
use App\Filament\Platform\Resources\Branches\Pages\EditBranch;
use App\Filament\Platform\Resources\Branches\Pages\ListBranches;
use App\Filament\Platform\Resources\Branches\Pages\ViewBranch;
use App\Filament\Platform\Resources\Branches\Schemas\BranchForm;
use App\Filament\Platform\Resources\Branches\Schemas\BranchInfolist;
use App\Filament\Platform\Resources\Branches\Tables\BranchesTable;
use App\Models\Branch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BranchInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'view' => ViewBranch::route('/{record}'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
