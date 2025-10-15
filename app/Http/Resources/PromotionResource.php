<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'promotionType' => $this->promotion_type,
            'promotionValue' => $this->promotion_value !== null ? (float) $this->promotion_value : null,
            'isMaxPromotionValue' => (bool) $this->is_max_promotion_value,
            'maxPromotionValue' => $this->max_promotion_value !== null ? (float) $this->max_promotion_value : null,
            'orderMinValue' => $this->order_min_value !== null ? (float) $this->order_min_value : null,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'qtyLimit' => $this->qty_limit,
            'isOncePerCustomer' => (bool) $this->is_once_per_customer,
            'note' => $this->note,
        ];
    }
}


