<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingAddressResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'recipient_name' => $this->recipient_name,
            'address'        => $this->address,
            'phone_number'   => $this->phone_number,
            'is_default'     => $this->is_default,
            //'customer_id'    => $this->customer_id,
            //'tag_id'         => $this->tag_id,
            'tag_name'       => $this->tag?->name,
        ];
    }
}
