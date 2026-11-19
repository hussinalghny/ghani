<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['phone' => normalize_phone(env('ADMIN_PHONE', '07766637444'))],
            ['name' => 'إدارة غني أكاديمي', 'password' => env('ADMIN_PASSWORD', 'ChangeMe@2026')]
        );
        $admin->forceFill(['role' => 'admin'])->save();

        Setting::put(config('academy.defaults'));

        $cats = [
            ['name' => 'الأمن السيبراني', 'slug' => 'cyber-security', 'icon' => 'shield', 'description' => 'احم الأنظمة والشبكات بأدوات وأساليب عملية.'],
            ['name' => 'برمجة المواقع', 'slug' => 'web', 'icon' => 'globe', 'description' => 'من أول سطر HTML إلى موقع منشور على الإنترنت.'],
            ['name' => 'برمجة التطبيقات', 'slug' => 'mobile', 'icon' => 'phone', 'description' => 'تطبيقات Android و iOS بكود واحد.'],
            ['name' => 'الذكاء الاصطناعي', 'slug' => 'ai', 'icon' => 'cpu', 'description' => 'تحليل البيانات وبناء حلول ذكية.'],
        ];
        foreach ($cats as $i => $c) {
            Category::query()->updateOrCreate(['slug' => $c['slug']], $c + ['sort' => $i]);
        }

        $instructor = Instructor::query()->firstOrCreate(['name' => 'مدرب غني أكاديمي'], ['title' => 'مدرب ممارس في المجال']);

        $courses = [
            ['cyber-security', 'الدفاع الذكي', 'smart-defense', 'الأمن السيبراني بالذكاء الاصطناعي خلال 4 أسابيع.', 200000, true],
            ['web', 'موقعك الأول بيدك', 'web-first-site', 'HTML و CSS و JavaScript و PHP و MySQL بمشروع حقيقي.', 150000, true],
            ['mobile', 'تطبيق خلال شهر', 'flutter-app', 'تطبيق Flutter يشتغل على Android و iOS.', 175000, true],
        ];
        foreach ($courses as $i => [$cat, $title, $slug, $sub, $price, $featured]) {
            $course = Course::query()->firstOrCreate(['slug' => $slug], [
                'category_id' => Category::where('slug', $cat)->value('id'),
                'instructor_id' => $instructor->id,
                'title' => $title,
                'subtitle' => $sub,
                'description' => "كورس عملي مدته 4 أسابيع، 12 جلسة بواقع ساعتين لكل جلسة.\nكل جلسة تنتهي بشيء تعمله بيدك، وتطلع بمشروع حقيقي وشهادة إتمام.",
                'outcomes' => "تبني مشروعا حقيقيا من الصفر\nتفهم الأدوات التي يستخدمها أصحاب العمل\nتحصل على شهادة إتمام قابلة للتحقق",
                'price' => $price,
                'level' => 'beginner',
                'duration_label' => '4 أسابيع · 12 جلسة',
                'is_published' => true,
                'is_featured' => $featured,
                'sort' => $i,
            ]);

            if (! $course->sections()->exists()) {
                foreach (['الأسبوع الأول: الأساسيات', 'الأسبوع الثاني: التطبيق'] as $si => $st) {
                    $section = $course->sections()->create(['title' => $st, 'sort' => $si]);
                    foreach ([1, 2, 3] as $li) {
                        $course->lessons()->create([
                            'section_id' => $section->id,
                            'title' => 'الجلسة '.($si * 3 + $li),
                            'type' => 'video',
                            'duration' => 600 + $li * 180,
                            'is_free_preview' => $si === 0 && $li === 1,
                            'sort' => $li,
                        ]);
                    }
                }
            }
        }
    }
}
