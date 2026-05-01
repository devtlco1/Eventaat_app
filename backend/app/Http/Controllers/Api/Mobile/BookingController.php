<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\CreateBookingRequest;
use App\Http\Resources\Mobile\MobileBookingResource;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\Bookings\BookingTransitionException;
use App\Services\Bookings\BookingTransitionService;
use App\Services\Notifications\BookingNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $status = trim((string) $request->query('status', ''));

        $query = Booking::query()
            ->where('customer_id', $user->id)
            ->with(['restaurant', 'branch', 'seatingArea', 'table'])
            ->orderByDesc('starts_at');

        if ($status !== '' && in_array($status, array_map(fn (BookingStatus $s) => $s->value, BookingStatus::cases()), true)) {
            $query->where('status', $status);
        }

        return MobileBookingResource::collection(
            $query->paginate($perPage)->withQueryString()
        );
    }

    public function store(
        CreateBookingRequest $request,
        BookingNotificationService $bookingNotifications,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $seatingAreaId = $request->input('seating_area_id');
        $tableId = $request->input('restaurant_table_id');

        if ($seatingAreaId === null && $tableId !== null) {
            /** @var RestaurantTable|null $table */
            $table = RestaurantTable::query()->with('seatingArea')->find((int) $tableId);
            $seatingAreaId = $table?->seating_area_id;
        }

        $booking = Booking::create([
            'customer_id' => $user->id,
            'restaurant_id' => (int) $request->input('restaurant_id'),
            'branch_id' => (int) $request->input('branch_id'),
            'seating_area_id' => $seatingAreaId !== null ? (int) $seatingAreaId : null,
            'restaurant_table_id' => $tableId !== null ? (int) $tableId : null,
            'starts_at' => $request->date('starts_at'),
            'party_size' => (int) $request->input('party_size'),
            'status' => BookingStatus::Pending,
            'customer_note' => $request->input('customer_note'),
            'restaurant_note' => null,
        ]);

        $booking->load(['restaurant', 'branch', 'seatingArea', 'table', 'customer']);

        $bookingNotifications->record($booking, BookingNotification::EVENT_BOOKING_CREATED);

        return response()->json([
            'booking' => new MobileBookingResource($booking),
        ], 201);
    }

    public function show(Request $request, Booking $booking): MobileBookingResource
    {
        /** @var User $user */
        $user = $request->user();

        if ($booking->customer_id !== $user->id) {
            abort(404);
        }

        $booking->load(['restaurant', 'branch', 'seatingArea', 'table']);

        return new MobileBookingResource($booking);
    }

    public function cancel(
        Request $request,
        Booking $booking,
        BookingTransitionService $transitions,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        if ($booking->customer_id !== $user->id) {
            abort(404);
        }

        try {
            $transitions->cancel($booking);
        } catch (BookingTransitionException $e) {
            return response()->json([
                'message' => 'Cannot cancel booking.',
                'errors' => [
                    'status' => [$e->getMessage()],
                ],
            ], 422);
        }

        $booking->refresh()->load(['restaurant', 'branch', 'seatingArea', 'table']);

        return response()->json([
            'booking' => new MobileBookingResource($booking),
        ]);
    }
}
