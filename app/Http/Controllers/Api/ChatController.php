<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\DonationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Chat",
 *     description="Operações relacionadas ao chat"
 * )
 */
class ChatController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/chats",
     *     summary="Listar meus chats",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de chats do usuário"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        
        $chats = Chat::with(['donationItem', 'donor', 'interestedUser', 'lastMessage'])
            ->where(function($query) use ($userId) {
                $query->where('donor_id', $userId)
                      ->orWhere('interested_user_id', $userId);
            })
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        return response()->json($chats);
    }

    /**
     * @OA\Post(
     *     path="/api/chats",
     *     summary="Iniciar novo chat",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"donation_item_id"},
     *             @OA\Property(property="donation_item_id", type="integer"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Chat criado com sucesso"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'donation_item_id' => 'required|exists:donation_items,id',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $donationItem = DonationItem::findOrFail($request->donation_item_id);
        $userId = $request->user()->id;

        // Verificar se o usuário não está tentando conversar consigo mesmo
        if ($donationItem->user_id === $userId) {
            return response()->json([
                'error' => 'Você não pode conversar sobre seu próprio item'
            ], 422);
        }

        // Verificar se já existe um chat entre esses usuários para este item
        $existingChat = Chat::where('donation_item_id', $request->donation_item_id)
            ->where('donor_id', $donationItem->user_id)
            ->where('interested_user_id', $userId)
            ->first();

        if ($existingChat) {
            // Se já existe, apenas adicionar a nova mensagem
            $message = ChatMessage::create([
                'chat_id' => $existingChat->id,
                'user_id' => $userId,
                'message' => $request->message,
            ]);

            $existingChat->update(['last_message_at' => now()]);
            $existingChat->load(['donationItem', 'donor', 'interestedUser']);

            return response()->json([
                'message' => 'Mensagem enviada com sucesso',
                'data' => $existingChat
            ]);
        }

        // Criar novo chat
        $chat = Chat::create([
            'donation_item_id' => $request->donation_item_id,
            'donor_id' => $donationItem->user_id,
            'interested_user_id' => $userId,
            'last_message_at' => now(),
        ]);

        // Criar primeira mensagem
        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => $userId,
            'message' => $request->message,
        ]);

        $chat->load(['donationItem', 'donor', 'interestedUser']);

        return response()->json([
            'message' => 'Chat criado com sucesso',
            'data' => $chat
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/chats/{id}",
     *     summary="Obter mensagens do chat",
     *     tags={"Chat"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensagens do chat"
     *     )
     * )
     */
    public function show(Request $request, Chat $chat)
    {
        // Verificar se o usuário participa do chat
        if (!$chat->hasUser($request->user()->id)) {
            return response()->json([
                'error' => 'Não autorizado'
            ], 403);
        }

        $messages = $chat->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Marcar mensagens como lidas
        $chat->messages()
            ->where('user_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'chat' => $chat->load(['donationItem', 'donor', 'interestedUser']),
            'messages' => $messages
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/chats/{id}/messages",
     *     summary="Enviar mensagem",
     *     tags={"Chat"},
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
     *             required={"message"},
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mensagem enviada com sucesso"
     *     )
     * )
     */
    public function sendMessage(Request $request, Chat $chat)
    {
        // Verificar se o usuário participa do chat
        if (!$chat->hasUser($request->user()->id)) {
            return response()->json([
                'error' => 'Não autorizado'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 422);
        }

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
            'message' => $request->message,
        ]);

        $chat->update(['last_message_at' => now()]);
        $message->load('user');

        return response()->json([
            'message' => 'Mensagem enviada com sucesso',
            'data' => $message
        ], 201);
    }
}

