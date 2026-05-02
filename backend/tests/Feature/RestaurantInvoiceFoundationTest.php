<?php

namespace Tests\Feature;

use App\Enums\RestaurantInvoiceStatus;
use App\Enums\RestaurantStatus;
use App\Enums\RestaurantSubscriptionStatus;
use App\Filament\Platform\Resources\RestaurantInvoices\Pages\CreateRestaurantInvoice as PlatformCreateRestaurantInvoice;
use App\Filament\Platform\Resources\RestaurantInvoices\Pages\ListRestaurantInvoices as PlatformListRestaurantInvoices;
use App\Models\Restaurant;
use App\Models\RestaurantInvoice;
use App\Models\RestaurantSubscription;
use App\Models\User;
use App\Services\Finance\RestaurantInvoiceService;
use Database\Seeders\RolesAndTestUsersSeeder;
use Database\Seeders\SubscriptionPlansSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RestaurantInvoiceFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
        $this->seed(SubscriptionPlansSeeder::class);
    }

    private function makeRestaurant(string $slug): Restaurant
    {
        static $n = 0;
        $n++;

        return Restaurant::create([
            'name' => 'Invoice Restaurant '.$n,
            'slug' => $slug.'-'.$n,
            'status' => RestaurantStatus::Active,
        ]);
    }

    private function invoiceAttrs(Restaurant $restaurant, string $invoiceNumber): array
    {
        return [
            'restaurant_id' => $restaurant->id,
            'restaurant_subscription_id' => null,
            'invoice_number' => $invoiceNumber,
            'status' => RestaurantInvoiceStatus::Draft,
            'issue_date' => null,
            'due_date' => null,
            'paid_at' => null,
            'subtotal_amount' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'currency' => 'IQD',
            'notes' => null,
        ];
    }

    public function test_invoice_numbers_are_sequential_per_year(): void
    {
        $restaurant = $this->makeRestaurant('inv-seq');
        $svc = app(RestaurantInvoiceService::class);

        $n1 = $svc->generateUniqueInvoiceNumber();
        RestaurantInvoice::create($this->invoiceAttrs($restaurant, $n1));

        $n2 = $svc->generateUniqueInvoiceNumber();
        $this->assertNotSame($n1, $n2);
        $this->assertSame('INV-'.now()->year.'-000002', $n2);
    }

    public function test_total_amount_is_recalculated_on_save(): void
    {
        $restaurant = $this->makeRestaurant('inv-total');
        $svc = app(RestaurantInvoiceService::class);

        $invoice = RestaurantInvoice::create(array_merge($this->invoiceAttrs($restaurant, $svc->generateUniqueInvoiceNumber()), [
            'subtotal_amount' => 100,
            'discount_amount' => 25,
            'tax_amount' => 10,
        ]));

        $this->assertSame('85.00', $invoice->fresh()->total_amount);

        $invoice->fill([
            'subtotal_amount' => 50,
            'discount_amount' => 80,
            'tax_amount' => 5,
        ])->save();

        $invoice->refresh();
        $this->assertSame('50.00', $invoice->discount_amount);
        $this->assertSame('5.00', $invoice->total_amount);
    }

    public function test_platform_admin_can_access_invoice_index_restaurant_staff_cannot(): void
    {
        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        $this->get('/platform/restaurant-invoices')->assertOk();

        $owner = User::where('email', 'restaurant_owner@eventaat.test')->firstOrFail();
        $this->actingAs($owner);

        $this->get('/platform/restaurant-invoices')->assertForbidden();
    }

    public function test_platform_can_create_invoice_via_filament_without_manual_invoice_number(): void
    {
        $restaurant = $this->makeRestaurant('inv-lw');

        $admin = User::where('email', 'operations_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformCreateRestaurantInvoice::class)
            ->set('data.restaurant_id', $restaurant->id)
            ->set('data.restaurant_subscription_id', null)
            ->set('data.invoice_number', '')
            ->set('data.status', RestaurantInvoiceStatus::Draft->value)
            ->set('data.subtotal_amount', 120)
            ->set('data.discount_amount', 20)
            ->set('data.tax_amount', 10)
            ->set('data.currency', 'IQD')
            ->call('create')
            ->assertHasNoErrors();

        $invoice = RestaurantInvoice::query()->where('restaurant_id', $restaurant->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{6}$/', $invoice->invoice_number);
        $this->assertSame('110.00', $invoice->total_amount);
    }

    public function test_mark_paid_table_action_updates_status_and_paid_at(): void
    {
        $restaurant = $this->makeRestaurant('inv-paid');
        $svc = app(RestaurantInvoiceService::class);

        $invoice = RestaurantInvoice::create(array_merge($this->invoiceAttrs($restaurant, $svc->generateUniqueInvoiceNumber()), [
            'status' => RestaurantInvoiceStatus::Issued,
            'issue_date' => now()->toDateString(),
        ]));

        $admin = User::where('email', 'super_admin@eventaat.test')->firstOrFail();
        Filament::setCurrentPanel('platform');
        $this->actingAs($admin);

        Livewire::test(PlatformListRestaurantInvoices::class)
            ->callTableAction('mark_paid', $invoice);

        $invoice->refresh();
        $this->assertSame(RestaurantInvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_subscription_must_belong_to_selected_restaurant(): void
    {
        $r1 = $this->makeRestaurant('inv-a');
        $r2 = $this->makeRestaurant('inv-b');

        $subscription = RestaurantSubscription::create([
            'restaurant_id' => $r2->id,
            'subscription_plan_id' => null,
            'status' => RestaurantSubscriptionStatus::Active,
            'notes' => null,
        ]);

        $svc = app(RestaurantInvoiceService::class);

        $this->expectException(ValidationException::class);

        RestaurantInvoice::create(array_merge($this->invoiceAttrs($r1, $svc->generateUniqueInvoiceNumber()), [
            'restaurant_subscription_id' => $subscription->id,
        ]));
    }
}
