<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'cover' => $this->coverUrl(),
            'thumb' => $this->thumbUrl(),
            'price' => $this->price,
            'final_price' => $this->finalPrice(),
            'price_label' => $this->isFree() ? 'مجاني' : iqd($this->finalPrice()),
            'level' => $this->level,
            'level_label' => $this->levelLabel(),
            'duration_label' => $this->duration_label,
            'lessons_count' => $this->whenCounted('lessons'),
            'has_certificate' => $this->has_certificate,
            'is_featured' => $this->is_featured,
            'rating' => round((float) $this->rating_avg, 1),
            'rating_count' => (int) $this->rating_count,
            'students_count' => $this->whenCounted('enrollments'),
            'category' => $this->whenLoaded('category', fn () => ['id' => $this->category->id, 'name' => $this->category->name, 'slug' => $this->category->slug]),
            'instructor' => $this->whenLoaded('instructor', fn () => $this->instructor ? [
                'name' => $this->instructor->name,
                'title' => $this->instructor->title,
                'photo' => $this->instructor->photoUrl(),
                'bio' => $this->instructor->bio,
                'can_chat' => (bool) $this->instructor->user_id,
            ] : null),
        ];
    }
}
