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

    /** شراء كورس من رصيد المحفظة، ويا كود خصم إذا موجود (يتحقق منه اللي يستدعي) */
    public function purchase(User $user, Course $course, ?\App\Models\DiscountCode $code = null): Enrollment
    {
        if (! $course->is_published) {
            throw new AcademyException('هذا الكورس غير متاح حاليا.', 'unavailable');
        }
        if ($user->hasAccessTo($course) && ! $user->isAdmin()) {
            throw new AcademyException('أنت مشترك في هذا الكورس.', 'already_enrolled');
        }

        $price = $code ? $code->apply($course->finalPrice()) : $course->finalPrice();

        return DB::transaction(function () use ($user, $course, $price, $code) {
            if ($price > 0) {
                $this->wallet->debit($user, $price, 'purchase', 'شراء كورس: '.$course->title.($code ? ' · كود '.$code->code : ''), 'course:'.$course->id);
            }
            $e = $this->enroll($user, $course, $price > 0 ? 'wallet' : ($code ? 'code' : 'free'), $price);
            if ($code) {
                $e->update(['discount_code_id' => $code->id]);
            }

            return $e;
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
