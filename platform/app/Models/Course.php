<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'has_certificate' => 'boolean',
        'price' => 'integer',
        'sale_price' => 'integer',
    ];

    public const LEVELS = [
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort')->orderBy('id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** السعر الفعلي بعد الخصم */
    public function finalPrice(): int
    {
        return $this->sale_price !== null && $this->sale_price < $this->price ? $this->sale_price : $this->price;
    }

    public function isFree(): bool
    {
        return $this->finalPrice() === 0;
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? asset('storage/'.$this->cover) : null;
    }

    public function levelLabel(): string
    {
        return self::LEVELS[$this->level] ?? $this->level;
    }

    public function outcomesList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $this->outcomes))));
    }

    public function totalDuration(): int
    {
        return (int) $this->lessons()->sum('duration');
    }

    /** ترتيب الدروس حسب الفصول ثم ترتيب الدرس */
    public function orderedLessons()
    {
        return Lesson::query()
            ->join('sections', 'sections.id', '=', 'lessons.section_id')
            ->where('lessons.course_id', $this->id)
            ->orderBy('sections.sort')->orderBy('sections.id')
            ->orderBy('lessons.sort')->orderBy('lessons.id')
            ->select('lessons.*')
            ->get();
    }

    /** الكورسات التي يدرسها هذا الحساب */
    public function scopeOwnedBy($query, User $user)
    {
        return $query->whereIn('instructor_id', Instructor::where('user_id', $user->id)->select('id'));
    }
}
