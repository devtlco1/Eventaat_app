<?php

namespace App\Filament\Platform\Resources\SubscriptionPlans;

use App\Filament\Concerns\GrantsPlatformOperationsCrud;
use App\Filament\Platform\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan;
use App\Filament\Platform\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use App\Filament\Platform\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use App\Filament\Platform\Resources\SubscriptionPlans\Pages\ViewSubscriptionPlan;
use App\Filament\Platform\Resources\SubscriptionPlans\Schemas\SubscriptionPlanForm;
use App\Filament\Platform\Resources\SubscriptionPlans\Schemas\SubscriptionPlanInfolist;
use App\Filament\Platform\Resources\SubscriptionPlans\Tables\SubscriptionPlansTable;
use App\Models\SubscriptionPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionPlanResource extends Resource
{
    use GrantsPlatformOperationsCrud;

    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 24;

    protected static ?string $navigationLabel = 'Subscription plans';

    protected static ?string $modelLabel = 'subscription plan';

    protected static ?string $pluralModelLabel = 'subscription plans';

    public static function form(Schema $schema): Schema
    {
        return SubscriptionPlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SubscriptionPlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionPlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionPlans::route('/'),
            'create' => CreateSubscriptionPlan::route('/create'),
            'view' => ViewSubscriptionPlan::route('/{record}'),
            'edit' => EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
