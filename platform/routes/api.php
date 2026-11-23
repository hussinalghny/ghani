<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

/*
| API للتطبيق (Flutter) — كل المسارات تبدأ بـ /api/v1
| المصادقة: Authorization: Bearer {token}
*/
Route::prefix('v1')->middleware('throttle:api')->group(function () {
    Route::get('config', [Api\CatalogController::class, 'config']);
    Route::get('home', [Api\CatalogController::class, 'home']);
    Route::get('categories', [Api\CatalogController::class, 'categories']);
    Route::get('courses', [Api\CatalogController::class, 'courses']);
    Route::get('courses/{course}', [Api\CatalogController::class, 'course']);
    Route::get('courses/{course}/reviews', [Api\CommunityController::class, 'reviews']);
    Route::get('verify/{certificate}', [Api\CatalogController::class, 'verifyCertificate']);
    // فيديو تطبيق الدسكتوب: رابط موقع مربوط بتوكن الجهاز (مشغل ويندوز ما يرسل هيدرات)
    Route::get('lessons/{lesson}/desktop', [App\Http\Controllers\VideoController::class, 'desktop'])->name('video.desktop')->middleware(['signed', 'throttle:600,1']);
    // إشعارات الاستلام من تطبيق غني باي (موقعة بمفتاح سري)
    Route::post('payments/hook', App\Http\Controllers\Api\PaymentHookController::class)->name('payments.hook')->withoutMiddleware('throttle:api')->middleware('throttle:120,1');
    Route::prefix('payments/console')->withoutMiddleware('throttle:api')->middleware('throttle:240,1')->controller(App\Http\Controllers\Api\PayConsoleController::class)->group(function () {
        Route::get('feed', 'feed');
        Route::post('orders/{order}/approve', 'approve');
        Route::post('notifications/{notification}/attach', 'attach');
        Route::post('notifications/{notification}/ignore', 'ignore');
    });
    Route::get('pages', fn () => response()->json(['privacy' => route('privacy'), 'terms' => route('terms')]));

    Route::post('auth/register', [Api\AuthController::class, 'register'])->middleware('throttle:login');
    Route::post('auth/login', [Api\AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'device'])->group(function () {
        Route::post('auth/logout', [Api\AuthController::class, 'logout']);
        Route::get('me', [Api\AccountController::class, 'me']);
        Route::put('me', [Api\AccountController::class, 'update']);
        Route::put('me/password', [Api\AccountController::class, 'password']);
        Route::get('me/devices', [Api\AccountController::class, 'devices']);
        Route::get('me/courses', [Api\LearnController::class, 'myCourses']);
        Route::get('me/certificates', [Api\AccountController::class, 'certificates']);

        Route::post('courses/{course}/enroll', [Api\LearnController::class, 'enroll']);
        Route::get('courses/{course}/curriculum', [Api\LearnController::class, 'curriculum']);
        Route::get('lessons/{lesson}', [Api\LearnController::class, 'lesson']);
        Route::get('lessons/{lesson}/stream', [App\Http\Controllers\VideoController::class, 'stream'])->name('api.video')->middleware('throttle:600,1');
        Route::post('me/push-token', [Api\DeviceController::class, 'storeToken']);
        Route::delete('me/push-token', [Api\DeviceController::class, 'deleteToken']);
        Route::delete('me', [Api\DeviceController::class, 'destroyAccount']);
        Route::post('lessons/{lesson}/progress', [Api\LearnController::class, 'progress']);
        Route::post('courses/{course}/review', [Api\CommunityController::class, 'review'])->middleware('throttle:10,1');
        Route::get('lessons/{lesson}/questions', [Api\CommunityController::class, 'questions']);
        Route::post('lessons/{lesson}/questions', [Api\CommunityController::class, 'ask'])->middleware('throttle:10,1');

        Route::get('chats', [Api\ChatController::class, 'index']);
        Route::post('chats/support', [Api\ChatController::class, 'support']);
        Route::post('courses/{course}/chat', [Api\ChatController::class, 'instructor']);
        Route::get('chats/{conversation}/messages', [Api\ChatController::class, 'messages']);
        Route::post('chats/{conversation}/messages', [Api\ChatController::class, 'send'])->middleware('throttle:30,1');

        Route::get('wallet', [Api\WalletController::class, 'index']);
        Route::post('wallet/redeem', [Api\WalletController::class, 'redeem'])->middleware('throttle:redeem');
    });
});
