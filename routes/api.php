<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DonationItemController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ReviewController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/**
 * @OA\Info(
 *     title="Doe Me API",
 *     version="1.0.0",
 *     description="API para o aplicativo Doe Me - plataforma de doações",
 *     @OA\Contact(
 *         email="contato@doeme.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Servidor de desenvolvimento"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

// Rotas públicas
Route::prefix('auth')->group(function () {
    // Autenticação tradicional
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Autenticação social
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])
        ->where('provider', 'google|facebook');
    Route::get('/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
        ->where('provider', 'google|facebook');
});

// Rotas públicas para categorias e itens (visualização)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/donation-items', [DonationItemController::class, 'index']);
Route::get('/donation-items/{donationItem}', [DonationItemController::class, 'show']);
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/users/{user}/reviews', [ReviewController::class, 'userReviews']);

// Rotas protegidas por autenticação
Route::middleware('auth:sanctum')->group(function () {
    // Autenticação
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });

    // Categorias (apenas admin pode criar/editar/deletar)
    Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);

    // Itens de doação
    Route::apiResource('donation-items', DonationItemController::class)->except(['index', 'show']);
    Route::get('/my-donations', [DonationItemController::class, 'myDonations']);

    // Chat
    Route::apiResource('chats', ChatController::class)->only(['index', 'store', 'show']);
    Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage']);

    // Avaliações
    Route::apiResource('reviews', ReviewController::class)->except(['index']);
});

// Rota para obter informações do usuário autenticado (compatibilidade)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

