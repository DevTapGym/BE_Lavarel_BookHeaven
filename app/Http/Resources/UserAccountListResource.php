<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAccountListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Lấy phone từ customer hoặc employee
        $phone = null;
        if ($this->customer) {
            $phone = $this->customer->phone;
        } elseif ($this->employee) {
            $phone = $this->employee->phone;
        }

        // Lấy role đầu tiên (nếu có)
        $role = null;
        if ($this->roles && $this->roles->isNotEmpty()) {
            $firstRole = $this->roles->first();
            $role = [
                'id' => $firstRole->id,
                'name' => $firstRole->name,
            ];
        }

        return [
            'id' => $this->id,
            'username' => $this->name,
            'email' => $this->email,
            'phone' => $phone,
            'updatedAt' => $this->updated_at ? $this->updated_at->toISOString() : null,
            'createdAt' => $this->created_at ? $this->created_at->toISOString() : null,
            'role' => $role,
        ];
    }
}
