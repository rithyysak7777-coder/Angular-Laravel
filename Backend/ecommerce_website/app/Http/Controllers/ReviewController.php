<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    /**
     * Display a listing of verified customer reviews for the atelier apparel.
     */
    public function index(): JsonResponse
    {
        $reviews = [
            [
                'id' => 1,
                'user_name' => 'Alexandre Dubois',
                'user_avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop',
                'rating' => 5,
                'comment' => 'The 320GSM cotton weight is unparalleled. Beautiful architectural drop-shoulder silhouette.',
                'date' => '2026-10-24',
                'verified_buyer' => true,
                'product_name' => 'Cyber Skull Core Obsidian Tee',
            ],
            [
                'id' => 2,
                'user_name' => 'Marcus Chen',
                'user_avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=200&auto=format&fit=crop',
                'rating' => 5,
                'comment' => 'Exceptional screenprint quality. After 10+ washes, the collar remains crisp and structured.',
                'date' => '2026-10-22',
                'verified_buyer' => true,
                'product_name' => 'Electric Butterfly Heavyweight Tee',
            ],
            [
                'id' => 3,
                'user_name' => 'Elena Rostova',
                'user_avatar' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=200&auto=format&fit=crop',
                'rating' => 5,
                'comment' => 'The angel wings design on washed black is stunning. Fast worldwide dispatch.',
                'date' => '2026-10-18',
                'verified_buyer' => true,
                'product_name' => 'Samothrace Wings Washed Tee',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }
}

