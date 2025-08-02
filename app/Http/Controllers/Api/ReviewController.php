<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\DonationItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Reviews",
 *     description="Operações relacionadas às avaliações"
 * )
 */
class ReviewController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reviews",
     *     summary="Listar avaliações",
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de avaliações"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Review::with(['reviewer', 'reviewedUser', 'donationItem']);

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        $reviews = $query->latest()->paginate(15);

        return response()->json($reviews);
    }

    /**
     * @OA\Post(
     *     path="/api/reviews",
     *     summary="Criar nova avaliação",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"donation_item_id", "reviewed_user_id", "rating"},
     *             @OA\Property(property="donation_item_id", type="integer"),
     *             @OA\Property(property="reviewed_user_id", type="integer"),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="comment", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Avaliação criada com sucesso"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'donation_item_id' => 'required|exists:donation_items,id',
            'reviewed_user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $userId = $request->user()->id;
        $donationItem = DonationItem::findOrFail($request->donation_item_id);

        // Verificar se o usuário não está tentando avaliar a si mesmo
        if ($request->reviewed_user_id === $userId) {
            return response()->json([
                'error' => 'Você não pode avaliar a si mesmo'
            ], 422);
        }

        // Verificar se o usuário participou da transação
        $canReview = ($donationItem->user_id === $userId && $donationItem->donated_to_user_id === $request->reviewed_user_id) ||
                     ($donationItem->donated_to_user_id === $userId && $donationItem->user_id === $request->reviewed_user_id);

        if (!$canReview) {
            return response()->json([
                'error' => 'Você só pode avaliar usuários com quem teve uma transação'
            ], 422);
        }

        // Verificar se já existe uma avaliação
        $existingReview = Review::where('donation_item_id', $request->donation_item_id)
            ->where('reviewer_id', $userId)
            ->where('reviewed_user_id', $request->reviewed_user_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'error' => 'Você já avaliou este usuário para esta doação'
            ], 422);
        }

        $review = Review::create([
            'donation_item_id' => $request->donation_item_id,
            'reviewer_id' => $userId,
            'reviewed_user_id' => $request->reviewed_user_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load(['reviewer', 'reviewedUser', 'donationItem']);

        return response()->json([
            'message' => 'Avaliação criada com sucesso',
            'data' => $review
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/reviews/{id}",
     *     summary="Obter avaliação específica",
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes da avaliação"
     *     )
     * )
     */
    public function show(Review $review)
    {
        $review->load(['reviewer', 'reviewedUser', 'donationItem']);

        return response()->json([
            'data' => $review
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/reviews/{id}",
     *     summary="Atualizar avaliação",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="comment", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avaliação atualizada com sucesso"
     *     )
     * )
     */
    public function update(Request $request, Review $review)
    {
        // Verificar se o usuário é o autor da avaliação
        if ($review->reviewer_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Não autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $review->update($request->only(['rating', 'comment']));
        $review->load(['reviewer', 'reviewedUser', 'donationItem']);

        return response()->json([
            'message' => 'Avaliação atualizada com sucesso',
            'data' => $review
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/reviews/{id}",
     *     summary="Excluir avaliação",
     *     tags={"Reviews"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avaliação excluída com sucesso"
     *     )
     * )
     */
    public function destroy(Request $request, Review $review)
    {
        // Verificar se o usuário é o autor da avaliação
        if ($review->reviewer_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Não autorizado'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Avaliação excluída com sucesso'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}/reviews",
     *     summary="Obter avaliações de um usuário",
     *     tags={"Reviews"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Avaliações do usuário"
     *     )
     * )
     */
    public function userReviews(User $user)
    {
        $reviews = $user->reviewsReceived()
            ->with(['reviewer', 'donationItem'])
            ->latest()
            ->paginate(15);

        $stats = [
            'average_rating' => $user->average_rating,
            'total_reviews' => $user->total_reviews,
            'rating_distribution' => [
                '5' => $user->reviewsReceived()->byRating(5)->count(),
                '4' => $user->reviewsReceived()->byRating(4)->count(),
                '3' => $user->reviewsReceived()->byRating(3)->count(),
                '2' => $user->reviewsReceived()->byRating(2)->count(),
                '1' => $user->reviewsReceived()->byRating(1)->count(),
            ]
        ];

        return response()->json([
            'user' => $user,
            'stats' => $stats,
            'reviews' => $reviews
        ]);
    }
}

