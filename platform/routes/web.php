<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\ResellerController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// ---------- الواجهة العامة ----------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/category/{category}', [CourseController::class, 'category'])->name('courses.category');
Route::get('/course/{course}', [CourseController::class, 'show'])->name('courses.show');
// رابط موقع لمدة 10 دقائق يحمل رقم الطالب، يشتغل للموقع والتطبيق
Route::get('/file/{lesson}', [LearnController::class, 'download'])->name('learn.file')->middleware(['signed', 'throttle:30,1']);
Route::get('/app', [App\Http\Controllers\AppDownloadController::class, 'show'])->name('app');
Route::get('/app/download/{platform}', [App\Http\Controllers\AppDownloadController::class, 'download'])->name('app.download')->middleware('throttle:20,1');
Route::get('/verify/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');

// ---------- الدخول ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// فيديو الدرس: رابط موقع مؤقت (للموقع والتطبيق)
Route::get('/privacy', fn () => view('site.privacy'))->name('privacy');
Route::get('/terms', fn () => view('site.terms'))->name('terms');

// ---------- الطالب ----------
Route::middleware('auth')->group(function () {
    Route::get('/messages', [App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::get('/messages/support', [App\Http\Controllers\ChatController::class, 'support'])->name('chat.support');
    Route::post('/course/{course}/chat', [App\Http\Controllers\ChatController::class, 'instructor'])->name('chat.instructor');
    Route::get('/messages/{conversation}', [App\Http\Controllers\ChatController::class, 'show'])->name('chat.show');
    Route::get('/messages/{conversation}/poll', [App\Http\Controllers\ChatController::class, 'poll'])->name('chat.poll');
    Route::post('/messages/{conversation}', [App\Http\Controllers\ChatController::class, 'send'])->name('chat.send')->middleware('throttle:30,1');

    Route::post('/course/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');

    Route::get('/learn/{course}/{lesson?}', [LearnController::class, 'show'])->name('learn.show');
    Route::post('/learn/lesson/{lesson}/progress', [LearnController::class, 'progress'])->name('learn.progress');

    Route::get('/account', [AccountController::class, 'index'])->name('account');
    Route::get('/account/settings', [AccountController::class, 'settings'])->name('account.settings');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');
    Route::post('/learn/lesson/{lesson}/source', [LearnController::class, 'source'])->name('learn.source')->middleware('throttle:60,1');
    Route::get('/learn/lesson/{lesson}/stream', [App\Http\Controllers\VideoController::class, 'web'])->name('video.web')->middleware(['signed', 'throttle:300,1']);
    Route::put('/account/settings', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');

    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::post('/wallet/redeem', [WalletController::class, 'redeem'])->name('wallet.redeem')->middleware('throttle:redeem');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
});

// ---------- الموزعين (بيع القسائم) ----------
Route::middleware(['auth', 'role:reseller'])->prefix('reseller')->name('reseller.')->group(function () {
    Route::get('/', [ResellerController::class, 'index'])->name('index');
    Route::post('/issue', [ResellerController::class, 'issue'])->name('issue');
    Route::get('/batch/{batch}', [ResellerController::class, 'batch'])->name('batch');
    Route::get('/batch/{batch}/print', [ResellerController::class, 'print'])->name('print');
    Route::get('/batch/{batch}/csv', [ResellerController::class, 'csv'])->name('csv');
    Route::post('/voucher/{voucher}/sold', [ResellerController::class, 'markSold'])->name('sold');
});

// ---------- صندوق المحادثات (الأدمن والمدربين) ----------
Route::middleware(['auth', 'role:admin,instructor'])->get('/inbox', [App\Http\Controllers\ChatController::class, 'inbox'])->name('inbox');

// ---------- لوحة التحكم ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('instructors', Admin\InstructorController::class)->except('show');
    Route::resource('courses', Admin\CourseController::class)->except('show');
    Route::get('courses/{course}/curriculum', [Admin\CourseController::class, 'curriculum'])->name('courses.curriculum');

    Route::post('courses/{course}/sections', [Admin\CurriculumController::class, 'storeSection'])->name('sections.store');
    Route::put('sections/{section}', [Admin\CurriculumController::class, 'updateSection'])->name('sections.update');
    Route::delete('sections/{section}', [Admin\CurriculumController::class, 'destroySection'])->name('sections.destroy');
    Route::get('courses/{course}/lessons/create', [Admin\CurriculumController::class, 'createLesson'])->name('lessons.create');
    Route::post('courses/{course}/lessons', [Admin\CurriculumController::class, 'storeLesson'])->name('lessons.store');
    Route::get('lessons/{lesson}/edit', [Admin\CurriculumController::class, 'editLesson'])->name('lessons.edit');
    Route::put('lessons/{lesson}', [Admin\CurriculumController::class, 'updateLesson'])->name('lessons.update');
    Route::delete('lessons/{lesson}', [Admin\CurriculumController::class, 'destroyLesson'])->name('lessons.destroy');
    Route::post('courses/{course}/reorder', [Admin\CurriculumController::class, 'reorder'])->name('curriculum.reorder');
    Route::post('bunny/prepare', [Admin\CurriculumController::class, 'bunnyPrepare'])->name('bunny.prepare');
    Route::post('videos/chunk', [App\Http\Controllers\VideoController::class, 'chunk'])->name('videos.chunk');

    Route::get('vouchers', [Admin\VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('vouchers/create', [Admin\VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('vouchers', [Admin\VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('vouchers/search', [Admin\VoucherController::class, 'search'])->name('vouchers.search');
    Route::get('vouchers/batch/{batch}', [Admin\VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('vouchers/batch/{batch}/print', [Admin\VoucherController::class, 'print'])->name('vouchers.print');
    Route::get('vouchers/batch/{batch}/csv', [Admin\VoucherController::class, 'csv'])->name('vouchers.csv');
    Route::post('vouchers/batch/{batch}/disable', [Admin\VoucherController::class, 'disableBatch'])->name('vouchers.disableBatch');
    Route::post('vouchers/{voucher}/toggle', [Admin\VoucherController::class, 'toggle'])->name('vouchers.toggle');

    Route::get('students', [Admin\StudentController::class, 'index'])->name('students.index');
    Route::get('students/create', [Admin\StudentController::class, 'create'])->name('students.create');
    Route::post('students', [Admin\StudentController::class, 'store'])->name('students.store');
    Route::get('students/{user}', [Admin\StudentController::class, 'show'])->name('students.show');
    Route::put('students/{user}', [Admin\StudentController::class, 'update'])->name('students.update');
    Route::post('students/{user}/wallet', [Admin\StudentController::class, 'wallet'])->name('students.wallet');
    Route::post('students/{user}/enroll', [Admin\StudentController::class, 'enroll'])->name('students.enroll');
    Route::delete('students/{user}/enroll/{enrollment}', [Admin\StudentController::class, 'unenroll'])->name('students.unenroll');
    Route::post('students/{user}/devices/reset', [Admin\StudentController::class, 'resetDevices'])->name('students.devices.reset');
    Route::post('devices/{device}/block', [Admin\StudentController::class, 'toggleDevice'])->name('devices.toggle');

    Route::get('app', [Admin\AppReleaseController::class, 'edit'])->name('app');
    Route::post('app', [Admin\AppReleaseController::class, 'update'])->name('app.update');

    Route::get('notifications', [Admin\NotificationController::class, 'create'])->name('notifications');
    Route::post('notifications', [Admin\NotificationController::class, 'send'])->name('notifications.send');
    Route::post('notifications/credentials', [Admin\NotificationController::class, 'credentials'])->name('notifications.credentials');

    Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings');
    Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
});
