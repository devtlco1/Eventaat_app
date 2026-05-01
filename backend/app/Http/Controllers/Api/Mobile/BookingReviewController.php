<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\StoreReviewRequest;
use App\Http\Resources\Mobile\MobileReviewResource;
use App\Models\Booking;
use App\Models\RestaurantReview;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class BookingReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Booking $booking): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($booking->customer_id !== $user->id) {
            abort(404);
        }

        if ($booking->status !== BookingStatus::Completed) {
            return response()->json([
                'message' => 'Cannot submit review for this booking.',
                'errors' => [
                    'booking' => ['The booking must be completed before submitting a review.'],
                ],
            ], 422);
        }

        if (RestaurantReview::query()->where('booking_id', $booking->id)->exists()) {
            return response()->json([
                'message' => 'A review already exists for this booking.',
                'errors' => [
                    'booking' => ['A review already exists for this booking.'],
                ],
            ], 422);
        }

        $name = trim((string) $user->name);
        $customerName = filled($name) ? $name : 'Customer';

        $review = new RestaurantReview([
            'restaurant_id' => $booking->restaurant_id,
            'branch_id' => $booking->branch_id,
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'customer_name' => $customerName,
            'customer_phone' => $user->phone,
            'rating' => (int) $request->validated('rating'),
            'comment' => $request->validated('comment'),
            'status' => RestaurantReview::STATUS_PENDING_REVIEW,
            'source' => RestaurantReview::SOURCE_MOBILE,
        ]);

        try {
            $review->save();
        } catch (QueryException $e) {
            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'booking_id') && (str_contains($msg, 'unique') || str_contains($msg, 'duplicate'))) {
                return response()->json([
                    'message' => 'A review already exists for this booking.',
                    'errors' => [
                        'booking' => ['A review already exists for this booking.'],
                    ],
                ], 422);
            }

            throw $e;
        }

        $review->load(['restaurant', 'branch']);

        return response()->json([
            'review' => new MobileReviewResource($review),
        ], 201);
    }
}
