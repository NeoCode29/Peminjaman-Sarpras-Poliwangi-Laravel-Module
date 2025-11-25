<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'bio' => $this->bio,
            'user_type' => $this->user_type,
            'status' => $this->status,
            'role' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
            ])->toArray()),
            'profile_completed' => $this->profile_completed,
            'profile_completed_at' => $this->profile_completed_at,
            'blocked_until' => $this->blocked_until,
            'blocked_reason' => $this->blocked_reason,
            'student' => $this->whenLoaded('student'),
            'staff_employee' => $this->whenLoaded('staffEmployee'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
