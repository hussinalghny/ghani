<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function __construct(private WalletService $wallet) {}

    /** شراء كورس من رصيد المحفظة */
    public function purchase(User $user, Course $course): Enrollment
    {
        if (! $course->is_published) {
            throw new AcademyException('هذا الكورس غير متاح حاليا.', 'unavailable');
        }
        if ($user->hasAccessTo($course) && ! $user->isAdmin()) {
            throw new AcademyException('أنت مشترك في هذا الكورس.', 'already_enrolled');
        }

        $price = $course->finalPrice();

        return DB::transaction(function () use ($user, $course, $price) {
            if ($price > 0) {
                $this->wallet->debit($user, $price, 'purchase', 'شراء كورس: '.$course->title, 'course:'.$course->id);
            }

            return $this->enroll($user, $course, $price > 0 ? 'wallet' : 'free', $price);
        });
    }

    /** تسجيل مباشر (قسيمة كورس، إدارة، مجاني) */
    public function enroll(User $user, Course $course, string $source, int $pricePaid = 0): Enrollment
    {
        $expires = $course->access_days ? now()->addDays($course->access_days) : null;

        return Enrollment::query()->updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['source' => $source, 'price_paid' => $pricePaid, 'expires_at' => $expires]
        );
    }
}
