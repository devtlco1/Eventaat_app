<?php

namespace App\Filament\Platform\Pages;

use App\Filament\Concerns\AuthorizesPlatformOperations;
use App\Filament\Concerns\AuthorizesSuperAdmin;
use App\Filament\Support\FilamentSchemaLayout;
use App\Models\MessagingSettings;
use App\Services\Notifications\MessagingSettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Phase 7L — Platform Messaging Settings.
 *
 * Allows platform admins to view and change the OTP + booking notification
 * drivers without editing .env files.
 *
 * Access:
 *  super_admin      → view + edit (Save button shown)
 *  operations_admin → view-only  (all fields disabled, no Save button)
 */
class MessagingSettingsPage extends Page
{
    use AuthorizesPlatformOperations;
    use AuthorizesSuperAdmin;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'messaging-settings';

    protected static ?string $navigationLabel = 'Messaging settings';

    protected static ?string $title = 'Messaging settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    // -------------------------------------------------------------------------
    // Navigation / access
    // -------------------------------------------------------------------------

    public static function shouldRegisterNavigation(): bool
    {
        return self::isPlatformUser();
    }

    public function mount(): void
    {
        abort_unless(self::isPlatformUser(), 403);

        $settings = MessagingSettings::getOrCreate();

        $this->form->fill([
            'external_messaging_enabled' => $settings->external_messaging_enabled,
            'otp_driver' => $settings->otp_driver,
            'notification_driver' => $settings->notification_driver,
            'whatsapp_otp_enabled' => $settings->whatsapp_otp_enabled,
            'notes' => $settings->notes,
        ]);
    }

    // -------------------------------------------------------------------------
    // Header actions
    // -------------------------------------------------------------------------

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        if (! self::authIsSuperAdmin()) {
            return [];
        }

        return [
            Action::make('save')
                ->label('Save settings')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('primary')
                ->action('save'),
        ];
    }

    // -------------------------------------------------------------------------
    // Form
    // -------------------------------------------------------------------------

    public function form(Schema $schema): Schema
    {
        $isSuperAdmin = self::authIsSuperAdmin();
        $service = app(MessagingSettingsService::class);

        return $schema
            ->statePath('data')
            ->components([
                // ----- Kill-switch -----
                Section::make('External messaging')
                    ->description('Master toggle. When disabled, all outbound channels are forced to safe/local drivers regardless of other settings below.')
                    ->compact()
                    ->schema([
                        Toggle::make('external_messaging_enabled')
                            ->label('Enable external messaging (SMS / WhatsApp)')
                            ->helperText('Off → OTP driver forced to "log", notification driver forced to "dry_run".')
                            ->disabled(! $isSuperAdmin)
                            ->dehydrated(),
                    ]),

                // ----- OTP driver -----
                Section::make('OTP driver')
                    ->description('Controls how one-time passwords are delivered to customers during login/registration.')
                    ->compact()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('otp_driver')
                                ->label('OTP driver')
                                ->placeholder('Use env / config default')
                                ->helperText('Leave blank to fall back to OTP_DRIVER env variable (default: log).')
                                ->native(false)
                                ->options([
                                    MessagingSettings::OTP_DRIVER_LOG => 'log — write to Laravel log only (no SMS)',
                                    MessagingSettings::OTP_DRIVER_TWILIO_SMS => 'twilio_sms — Twilio Messaging Service (SMS)',
                                    MessagingSettings::OTP_DRIVER_TWILIO_WHATSAPP => 'twilio_whatsapp — Twilio WhatsApp template',
                                ])
                                ->nullable()
                                ->disabled(! $isSuperAdmin)
                                ->dehydrated(),

                            Placeholder::make('effective_otp_driver')
                                ->label('Effective OTP driver right now')
                                ->content(fn () => new HtmlString(
                                    '<span class="font-mono text-sm">'.e($service->effectiveOtpDriver()).'</span>'
                                )),
                        ]),
                    ]),

                // ----- WhatsApp guard -----
                Section::make('WhatsApp OTP')
                    ->description('WhatsApp OTP requires an approved Twilio Authentication template. Keep this off until Meta/Twilio approval is confirmed.')
                    ->compact()
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('whatsapp_otp_enabled')
                                ->label('WhatsApp OTP approved and ready')
                                ->helperText('Must be enabled before "twilio_whatsapp" can be selected as OTP driver.')
                                ->disabled(! $isSuperAdmin)
                                ->dehydrated(),

                            Placeholder::make('whatsapp_config_present')
                                ->label('WhatsApp credentials in config')
                                ->content(fn () => $service->hasTwilioWhatsAppConfig()
                                    ? new HtmlString('<span class="text-success-600 font-medium">✓ Present</span>')
                                    : new HtmlString('<span class="text-danger-600 font-medium">✗ Missing</span>')
                                ),
                        ]),
                    ]),

                // ----- Booking notification driver -----
                Section::make('Booking notification driver')
                    ->description('Controls how booking confirmations and arrival reminders are sent.')
                    ->compact()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('notification_driver')
                                ->label('Notification driver')
                                ->placeholder('Use env / config default')
                                ->helperText('Leave blank to fall back to NOTIFICATION_DRIVER env variable (default: dry_run).')
                                ->native(false)
                                ->options([
                                    MessagingSettings::NOTIFICATION_DRIVER_DRY_RUN => 'dry_run — record attempt only (no SMS sent)',
                                    MessagingSettings::NOTIFICATION_DRIVER_TWILIO_SMS => 'twilio_sms — Twilio Messaging Service (SMS)',
                                ])
                                ->nullable()
                                ->disabled(! $isSuperAdmin)
                                ->dehydrated(),

                            Placeholder::make('effective_notification_driver')
                                ->label('Effective notification driver right now')
                                ->content(fn () => new HtmlString(
                                    '<span class="font-mono text-sm">'.e($service->effectiveNotificationDriver()).'</span>'
                                )),
                        ]),
                    ]),

                // ----- Twilio credential presence -----
                Section::make('Twilio credentials (env only — values never shown)')
                    ->description('These indicators show whether the required Twilio credentials are set in the server environment. Values are never displayed.')
                    ->compact()
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            Placeholder::make('twilio_sms_config')
                                ->label('SMS credentials (account_sid + auth_token + messaging_service_sid)')
                                ->content(fn () => $service->hasTwilioSmsConfig()
                                    ? new HtmlString('<span class="text-success-600 font-medium">✓ All present</span>')
                                    : new HtmlString('<span class="text-danger-600 font-medium">✗ One or more missing</span>')
                                ),

                            Placeholder::make('twilio_whatsapp_config')
                                ->label('WhatsApp credentials (above + whatsapp_from + otp_content_sid)')
                                ->content(fn () => $service->hasTwilioWhatsAppConfig()
                                    ? new HtmlString('<span class="text-success-600 font-medium">✓ All present</span>')
                                    : new HtmlString('<span class="text-danger-600 font-medium">✗ One or more missing</span>')
                                ),
                        ]),
                    ]),

                // ----- Notes -----
                Section::make('Admin notes')
                    ->description('Optional — record why a driver was changed.')
                    ->compact()
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(! $isSuperAdmin)
                            ->dehydrated(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return FilamentSchemaLayout::stackSections($schema)
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('messaging-settings-form')
                    ->livewireSubmitHandler('save'),
            ]);
    }

    // -------------------------------------------------------------------------
    // Save action
    // -------------------------------------------------------------------------

    public function save(): void
    {
        abort_unless(self::authIsSuperAdmin(), 403);

        $data = $this->form->getState();

        // WhatsApp guard: cannot activate twilio_whatsapp driver unless whatsapp_otp_enabled
        if (
            ($data['otp_driver'] ?? null) === MessagingSettings::OTP_DRIVER_TWILIO_WHATSAPP
            && ! ($data['whatsapp_otp_enabled'] ?? false)
        ) {
            Notification::make()
                ->danger()
                ->title('WhatsApp OTP not approved')
                ->body('Enable "WhatsApp OTP approved and ready" before selecting the twilio_whatsapp driver.')
                ->send();

            return;
        }

        $settings = MessagingSettings::getOrCreate();
        $settings->fill([
            'external_messaging_enabled' => $data['external_messaging_enabled'] ?? false,
            'otp_driver' => $data['otp_driver'] ?: null,
            'notification_driver' => $data['notification_driver'] ?: null,
            'whatsapp_otp_enabled' => $data['whatsapp_otp_enabled'] ?? false,
            'notes' => $data['notes'] ?? null,
            'updated_by' => Filament::auth()->id(),
        ]);
        $settings->save();

        Notification::make()
            ->success()
            ->title('Messaging settings saved')
            ->send();
    }
}
