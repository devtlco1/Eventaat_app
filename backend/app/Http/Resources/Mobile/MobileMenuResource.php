<?php

namespace App\Http\Resources\Mobile;

use App\Models\RestaurantMenu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin RestaurantMenu
 */
class MobileMenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isPdf = $this->menu_mode === RestaurantMenu::MODE_PDF_UPLOAD;
        $isExternal = $this->menu_mode === RestaurantMenu::MODE_EXTERNAL_LINK;
        $isStructured = $this->menu_mode === RestaurantMenu::MODE_STRUCTURED;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'mode' => $this->menu_mode,
            'status' => $this->status,
            'display_order' => (int) ($this->display_order ?? 0),
            'pdf_url' => $isPdf && filled($this->menu_file_path)
                ? Storage::disk('public')->url($this->menu_file_path)
                : null,
            'external_url' => $isExternal
                ? $this->menu_url
                : null,
            'categories' => $isStructured
                ? MobileMenuCategoryResource::collection($this->whenLoaded('categories'))
                : [],
        ];
    }
}
