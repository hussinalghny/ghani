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
// الكورسات الحضورية (تسجيل بمقاعد محدودة + الدفع المباشر)
Route::get('/in-person/{cohort}', [App\Http\Controllers\CohortController::class, 'show'])->name('cohort.show');
Route::post('/in-person/{cohort}/register', [App\Http\Controllers\CohortController::class, 'register'])->name('cohort.register')->middleware('throttle:20,1,cohort');
Route::get('/smart-defense', fn () => redirect()->route('cohort.show', 'smart-defense'));
Route::middleware('auth')->group(function () {
    Route::get('/ticket/{registration}', [App\Http\Controllers\CohortController::class, 'ticket'])->name('cohort.ticket');
    Route::post('/ticket/{registration}/discount', [App\Http\Controllers\CohortController::class, 'discount'])->name('cohort.discount')->middleware('throttle:15,1,discount');
    Route::post('/ticket/{registration}/wallet', [App\Http\Controllers\CohortController::class, 'wallet'])->name('cohort.wallet');
});
Route::get('/thumb/{id}', App\Http\Controllers\ThumbController::class)->name('thumb')->whereNumber('id')->middleware('throttle:120,1');
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
    Route::post('/course/{course}/review', [CourseController::class, 'review'])->name('courses.review')->middleware('throttle:10,1');
    Route::post('/learn/lesson/{lesson}/ask', [LearnController::class, 'ask'])->name('learn.ask')->middleware('throttle:10,1');

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
    Route::post('/pay', [App\Http\Controllers\PaymentController::class, 'store'])->name('pay.store')->middleware('throttle:10,1');
    Route::get('/pay/{order}', [App\Http\Controllers\PaymentController::class, 'show'])->name('pay.show');
    Route::get('/pay/{order}/status', [App\Http\Controllers\PaymentController::class, 'status'])->name('pay.status');
    Route::post('/pay/{order}/cancel', [App\Http\Controllers\PaymentController::class, 'cancel'])->name('pay.cancel');
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
Route::middleware(['auth', 'role:admin,instructor,support'])->get('/inbox', [App\Http\Controllers\ChatController::class, 'inbox'])->name('inbox');

// ---------- إدارة محتوى الكورس (الأدمن، والمدرب على كورساته فقط) ----------
Route::middleware(['auth', 'role:admin,instructor', 'course.owner'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('courses/{course}/edit', [Admin\CourseController::class, 'edit'])->name('courses.edit');
    Route::put('courses/{course}', [Admin\CourseController::class, 'update'])->name('courses.update');
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
});

// ---------- لوحة المدرب ----------
Route::middleware(['auth', 'role:admin,instructor'])->get('/teach', [App\Http\Controllers\TeachController::class, 'index'])->name('teach');
Route::middleware(['auth', 'role:admin,instructor'])->post('/teach/questions/{question}', [App\Http\Controllers\TeachController::class, 'answer'])->name('teach.answer');
Route::middleware(['auth', 'role:admin'])->post('/teach/reviews/{review}', [App\Http\Controllers\TeachController::class, 'toggleReview'])->name('teach.review');

// ---------- الدعم الفني (والأدمن): الطلاب، المحادثات، الحضوري ----------
Route::middleware(['auth', 'role:admin,support'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('students', [Admin\StudentController::class, 'index'])->name('students.index');
    Route::get('students/{user}', [Admin\StudentController::class, 'show'])->name('students.show')->whereNumber('user');
    Route::put('students/{user}', [Admin\StudentController::class, 'update'])->name('students.update')->whereNumber('user');
    Route::post('students/{user}/devices/reset', [Admin\StudentController::class, 'resetDevices'])->name('students.devices.reset');
    Route::post('devices/{device}/block', [Admin\StudentController::class, 'toggleDevice'])->name('devices.toggle');
    Route::get('cohorts', [Admin\CohortController::class, 'index'])->name('cohorts');
    Route::get('cohorts/{cohort}', [Admin\CohortController::class, 'show'])->name('cohorts.show');
    Route::get('cohorts/{cohort}/checkin', [Admin\CheckinController::class, 'index'])->name('cohorts.checkin');
    Route::post('cohorts/{cohort}/checkin', [Admin\CheckinController::class, 'scan'])->name('cohorts.checkin.scan');
    Route::post('cohort-registrations/{registration}/{action}', [Admin\CohortController::class, 'act'])->name('cohorts.act')->whereIn('action', ['paid', 'cancel', 'checkin', 'pending']);
});

// ---------- لوحة التحكم ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('instructors', Admin\InstructorController::class)->except('show');
    Route::resource('courses', Admin\CourseController::class)->except(['show', 'edit', 'update']);

    Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments');
    Route::post('payments/settings', [Admin\PaymentController::class, 'settings'])->name('payments.settings');
    Route::post('payments/{order}/approve', [Admin\PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('payments/notifications/{notification}/attach', [Admin\PaymentController::class, 'attach'])->name('payments.attach');
    Route::post('payments/notifications/{notification}/ignore', [Admin\PaymentController::class, 'ignore'])->name('payments.ignore');
    Route::get('vouchers', [Admin\VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('vouchers/create', [Admin\VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('vouchers', [Admin\VoucherController::class, 'store'])->name('vouchers.store');
    Route::get('vouchers/search', [Admin\VoucherController::class, 'search'])->name('vouchers.search');
    Route::get('vouchers/batch/{batch}', [Admin\VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('vouchers/batch/{batch}/print', [Admin\VoucherController::class, 'print'])->name('vouchers.print');
    Route::get('vouchers/batch/{batch}/csv', [Admin\VoucherController::class, 'csv'])->name('vouchers.csv');
    Route::post('vouchers/batch/{batch}/disable', [Admin\VoucherController::class, 'disableBatch'])->name('vouchers.disableBatch');
    Route::post('vouchers/{voucher}/toggle', [Admin\VoucherController::class, 'toggle'])->name('vouchers.toggle');

    Route::get('students/create', [Admin\StudentController::class, 'create'])->name('students.create');
    Route::post('students', [Admin\StudentController::class, 'store'])->name('students.store');
    Route::post('students/{user}/wallet', [Admin\StudentController::class, 'wallet'])->name('students.wallet');
    Route::post('students/{user}/enroll', [Admin\StudentController::class, 'enroll'])->name('students.enroll');
    Route::delete('students/{user}/enroll/{enrollment}', [Admin\StudentController::class, 'unenroll'])->name('students.unenroll');

    Route::get('app', [Admin\AppReleaseController::class, 'edit'])->name('app');
    Route::post('app', [Admin\AppReleaseController::class, 'update'])->name('app.update');
    Route::post('app/banner', [Admin\AppReleaseController::class, 'banner'])->name('app.banner');

    Route::get('notifications', [Admin\NotificationController::class, 'create'])->name('notifications');
    Route::post('notifications', [Admin\NotificationController::class, 'send'])->name('notifications.send');
    Route::post('notifications/credentials', [Admin\NotificationController::class, 'credentials'])->name('notifications.credentials');

    Route::get('storage', [Admin\StorageController::class, 'index'])->name('storage');
    Route::put('storage', [Admin\StorageController::class, 'update'])->name('storage.update');
    Route::get('storage/import', [Admin\StorageController::class, 'import'])->name('storage.import');
    Route::post('storage/import', [Admin\StorageController::class, 'importStore'])->name('storage.import.store');
    Route::post('storage/durations', [Admin\StorageController::class, 'durations'])->name('storage.durations');
    Route::post('storage/move/{lesson}', [Admin\StorageController::class, 'move'])->name('storage.move');
    Route::put('cohorts/{cohort}', [Admin\CohortController::class, 'update'])->name('cohorts.update');
    Route::post('cohorts/{cohort}/discounts', [Admin\CohortController::class, 'storeDiscount'])->name('cohorts.discounts.store');
    Route::post('discounts/{discount}/toggle', [Admin\CohortController::class, 'toggleDiscount'])->name('cohorts.discounts.toggle');
    Route::get('discounts', [Admin\DiscountController::class, 'index'])->name('discounts');
    Route::post('discounts', [Admin\DiscountController::class, 'store'])->name('discounts.store');
    Route::post('discounts/{discount}/active', [Admin\DiscountController::class, 'toggle'])->name('discounts.toggle');
    Route::get('reports/sales', [Admin\ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/sales.csv', [Admin\ReportController::class, 'csv'])->name('reports.sales.csv');
    Route::post('backup', [Admin\StorageController::class, 'backup'])->name('backup');
    Route::get('backup/download', [Admin\StorageController::class, 'backupDownload'])->name('backup.download');
    Route::get('cohorts/{cohort}/csv', [Admin\CohortController::class, 'csv'])->name('cohorts.csv');

    Route::get('settings', [Admin\SettingsController::class, 'edit'])->name('settings');
    Route::put('settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
});
