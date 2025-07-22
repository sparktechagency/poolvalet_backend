<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PageController extends Controller
{
    public function createPage(Request $request)
    {
        return $this->storeOrUpdatePage($request, $request->page_type, $request->content);
    }

    private function storeOrUpdatePage(Request $request, $page_type, $content)
    {
        // Validation (only content)
        $request->validate([
            'content' => 'required|string',
        ]);

        // Update or Create based on 'type'
        $page = Page::updateOrCreate(
            ['page_type' => $page_type], // condition
            ['content' => json_encode($request->content)]
        );

        $page->content = json_decode($page->content);

        return response()->json([
            'status' => true,
            'message' => $page_type . ' page saved successfully.',
            'data' => [
                'page_type' => $page_type,
                'content' => $page->content,
            ],
        ]);
    }

    public function getPage(Request $request)
    {

        // ✅ Validation Rules
        $validator = Validator::make($request->all(), [
            'page_type' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $page = Page::where('page_type', $request->page_type)->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => $request->page_type . ' page not found',
            ], 404);
        }

        $page->content = json_decode($page->content);

        return response()->json([
            'status' => true,
            'message' => 'Get ' . $request->page_type . ' page',
            'data' => $page
        ], 200);
    }
}
