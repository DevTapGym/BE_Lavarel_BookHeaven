<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BookListResource extends ResourceCollection
{
    public function toArray($request)
    {
        $meta = [
            'page' => $this->currentPage(),
            'pageSize' => $this->perPage(),
            'pages' => $this->lastPage(),
            'total' => $this->total(),
        ];
        return [
            'meta' => $meta,
            'result' => BookItemResource::collection($this->collection),
        ];
    }
}
