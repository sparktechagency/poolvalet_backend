<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function addCategory(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'name' => $request->name,
            'icon' => $request->icon,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Category added successfully.',
            'category' => $category,
        ]);
    }

    public function getCategories($id = null)
    {

        $categories = Category::all();

        return response()->json([
            'status' => true,
            'message' => 'Get all categories',
            'data' => $categories
        ]);
    }

    public function viewCategory($id = null)
    {

        $category = Category::find($id);

        if(!$category){
            return response()->json([
                'status'=> false,
                'message'=> 'Category not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'View category',
            'data' => $category
        ]);
    }

    public function editCategory(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found.'
            ], 404);
        }

        // validation roles
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'icon' => 'sometimes|string|max:255',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $category->update($request->only(['name', 'icon']));

        return response()->json([
            'status' => true,
            'message' => 'Category updated successfully.',
            'category' => $category,
        ]);
    }

    public function deleteCategory($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found.'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Category deleted successfully.'
        ]);
    }

}
