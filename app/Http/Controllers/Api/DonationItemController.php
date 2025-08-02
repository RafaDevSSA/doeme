<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonationItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Donation Items",
 *     description="Operações relacionadas aos itens de doação"
 * )
 */
class DonationItemController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/donation-items",
     *     summary="Listar itens de doação",
     *     tags={"Donation Items"},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="location",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"available", "reserved", "donated"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de itens de doação"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = DonationItem::with(['user', 'category'])
            ->available()
            ->latest();

        // Filtros
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->paginate(15);

        return response()->json($items);
    }

    /**
     * @OA\Post(
     *     path="/api/donation-items",
     *     summary="Criar novo item de doação",
     *     tags={"Donation Items"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "category_id", "condition", "location"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="condition", type="string"),
     *             @OA\Property(property="location", type="string"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="latitude", type="number"),
     *             @OA\Property(property="longitude", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Item criado com sucesso"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'condition' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $item = DonationItem::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'description' => $request->description,
            'condition' => $request->condition,
            'location' => $request->location,
            'images' => $request->images,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        $item->load(['user', 'category']);

        return response()->json([
            'message' => 'Item criado com sucesso',
            'data' => $item
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/donation-items/{id}",
     *     summary="Obter item específico",
     *     tags={"Donation Items"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalhes do item"
     *     )
     * )
     */
    public function show(DonationItem $donationItem)
    {
        $donationItem->load(['user', 'category', 'reviews.reviewer']);

        return response()->json([
            'data' => $donationItem
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/donation-items/{id}",
     *     summary="Atualizar item de doação",
     *     tags={"Donation Items"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item atualizado com sucesso"
     *     )
     * )
     */
    public function update(Request $request, DonationItem $donationItem)
    {
        // Verificar se o usuário é o dono do item
        if ($donationItem->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Não autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'condition' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'images' => 'sometimes|nullable|array',
            'images.*' => 'string|url',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
            'status' => 'sometimes|in:available,reserved,donated',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $donationItem->update($request->only([
            'title', 'description', 'category_id', 'condition', 
            'location', 'images', 'latitude', 'longitude', 'status'
        ]));

        $donationItem->load(['user', 'category']);

        return response()->json([
            'message' => 'Item atualizado com sucesso',
            'data' => $donationItem
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/donation-items/{id}",
     *     summary="Excluir item de doação",
     *     tags={"Donation Items"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item excluído com sucesso"
     *     )
     * )
     */
    public function destroy(Request $request, DonationItem $donationItem)
    {
        // Verificar se o usuário é o dono do item
        if ($donationItem->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Não autorizado'
            ], 403);
        }

        $donationItem->delete();

        return response()->json([
            'message' => 'Item excluído com sucesso'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/my-donations",
     *     summary="Listar meus itens de doação",
     *     tags={"Donation Items"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista dos meus itens"
     *     )
     * )
     */
    public function myDonations(Request $request)
    {
        $items = DonationItem::with(['category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json($items);
    }
}

