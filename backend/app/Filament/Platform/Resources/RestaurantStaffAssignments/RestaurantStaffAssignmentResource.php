<?php

namespace App\Filament\Platform\Resources\RestaurantStaffAssignments;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\RestaurantStaffAssignments\Pages\CreateRestaurantStaffAssignment;
use App\Filament\Platform\Resources\RestaurantStaffAssignments\Pages\EditRestaurantStaffAssignment;
use App\Filament\Platform\Resources\RestaurantStaffAssignments\Pages\ListRestaurantStaffAssignments;
use App\Filament\Platform\Resources\RestaurantStaffAssignments\Pages\ViewRestaurantStaffAssignment;
use App\Filament\Platform\Resources\RestaurantStaffAssignments\Schemas\RestaurantStaffAssignmentForm;
use App\Filament\Platform\Resources\RestaurantStaffAssignments\Schemas\RestaurantStaffAssignmentInfolist;
use App\Filament\Platform\Resources\RestaurantStaffAssignments\Tables\RestaurantStaffAssignmentsTable;
use App\Models\RestaurantStaffAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantStaffAssignmentResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = RestaurantStaffAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant Setup';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return RestaurantStaffAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RestaurantStaffAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantStaffAssignmentsTable::configure($table);
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
            'index' => ListRestaurantStaffAssignments::route('/'),
            'create' => CreateRestaurantStaffAssignment::route('/create'),
            'view' => ViewRestaurantStaffAssignment::route('/{record}'),
            'edit' => EditRestaurantStaffAssignment::route('/{record}/edit'),
        ];
    }
}
