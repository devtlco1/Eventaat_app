<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Restaurant;
use Database\Seeders\RolesAndTestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class RestaurantEventValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndTestUsersSeeder::class);
    }

    public function test_branch_must_belong_to_selected_restaurant(): void
    {
        $r1 = Restaurant::create(['name' => 'R1', 'slug' => 'r1', 'status' => 'active']);
        $r2 = Restaurant::create(['name' => 'R2', 'slug' => 'r2', 'status' => 'active']);

        $b2 = Branch::create(['restaurant_id' => $r2->id, 'name' => 'B2', 'code' => 'b2', 'status' => 'active']);

        $data = [
            'restaurant_id' => $r1->id,
            'branch_id' => $b2->id, // wrong restaurant
        ];

        $v = Validator::make($data, [
            'restaurant_id' => ['required'],
            'branch_id' => [
                'nullable',
                Rule::exists(Branch::class, 'id')->where('restaurant_id', $data['restaurant_id']),
            ],
        ]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('branch_id', $v->errors()->toArray());
    }

    public function test_ends_at_must_be_after_starts_at(): void
    {
        $starts = Carbon::now()->addDays(2);
        $ends = (clone $starts)->subHour();

        $data = [
            'starts_at' => $starts->toDateTimeString(),
            'ends_at' => $ends->toDateTimeString(),
        ];

        $v = Validator::make($data, [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $this->assertTrue($v->fails());
        $this->assertArrayHasKey('ends_at', $v->errors()->toArray());
    }
}

