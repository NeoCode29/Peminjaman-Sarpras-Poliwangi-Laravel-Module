<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
            'display_name' => $this->display_name,
            'description' => $this->description,
            'category' => $this->category,
            'is_active' => $this->is_active,
            'guard_name' => $this->guard_name,
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->permissions->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'category' => $permission->category,
                ])->toArray();
            }),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
