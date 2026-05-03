<?php

namespace App\Filament\Platform\Resources\OtpDeliveryAttempts;

use App\Filament\Concerns\AuthorizesPlatformOperations;
use App\Filament\Platform\Resources\OtpDeliveryAttempts\Pages\ListOtpDeliveryAttempts;
use App\Filament\Platform\Resources\OtpDeliveryAttempts\Pages\ViewOtpDeliveryAttempt;
use App\Filament\Platform\Resources\OtpDeliveryAttempts\Tables\OtpDeliveryAttemptsTable;
use App\Filament\Support\FilamentSchemaLayout;
use App\Models\OtpDeliveryAttempt;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OtpDeliveryAttemptResource extends Resource
{
    use AuthorizesPlatformOperations;

    protected static ?string $model = OtpDeliveryAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 14;

    protected static ?string $navigationLabel = 'OTP delivery attempts';

    protected static ?string $modelLabel = 'OTP delivery attempt';

    protected static ?string $pluralModelLabel = 'OTP delivery attempts';

    public static function shouldRegisterNavigation(): bool
    {
        return self::isPlatformUser();
    }

    public static function canViewAny(): bool
    {
        return self::isPlatformUser();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canForceDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)->components([
            Section::make('Delivery')
                ->compact()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('id'),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'delivered', 'sent' => 'success',
                                'failed', 'undelivered' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('driver')->badge(),
                        TextEntry::make('channel')->badge()->placeholder('—'),
                        TextEntry::make('provider')->placeholder('—'),
                        TextEntry::make('provider_message_sid')->label('Twilio message SID')->placeholder('—')->copyable(),
                        TextEntry::make('phone_masked')->label('Phone (masked only)'),
                        TextEntry::make('phone_hash')->label('Phone hash (SHA-256)')->fontFamily(FontFamily::Mono)->copyable(),
                    ]),
                ]),
            Section::make('Failure details')
                ->compact()
                ->collapsed()
                ->visible(fn (mixed $record): bool => $record instanceof OtpDeliveryAttempt && in_array($record->status, [
                    OtpDeliveryAttempt::STATUS_FAILED,
                    OtpDeliveryAttempt::STATUS_UNDELIVERED,
                ], true))
                ->schema([
                    TextEntry::make('error_code')->placeholder('—'),
                    TextEntry::make('error_message')->columnSpanFull()->placeholder('—'),
                ]),
            Section::make('Technical metadata')
                ->compact()
                ->collapsed()
                ->schema([
                    TextEntry::make('metadata')
                        ->columnSpanFull()
                        ->formatStateUsing(fn ($state): string => $state !== null && $state !== []
                            ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                            : '—')
                        ->fontFamily(FontFamily::Mono),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return OtpDeliveryAttemptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOtpDeliveryAttempts::route('/'),
            'view' => ViewOtpDeliveryAttempt::route('/{record}'),
        ];
    }
}
