<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    // public function addCategory(Request $request)
    // {
    //     // validation roles
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'icon' => 'required|string|max:255',
    //     ]);

    //     // check validation
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     $category = Category::create([
    //         'name' => $request->name,
    //         'icon' => $request->icon,
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Category added successfully.',
    //         'category' => $category,
    //     ]);
    // }


    public function addCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'expected_budget' => 'nullable|string|max:255',
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('icon', $filename, 'public');
            $avatarPath = '/storage/' . $filepath;
        }

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'icon' => $avatarPath,
            'expected_budget' => $request->expected_budget,
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

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'View category',
            'data' => $category
        ]);
    }

    // public function editCategory(Request $request, $id)
    // {
    //     $category = Category::find($id);

    //     if (!$category) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Category not found.'
    //         ], 404);
    //     }

    //     // validation roles
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'sometimes|string|max:255',
    //         'icon' => 'sometimes|string|max:255',
    //     ]);

    //     // check validation
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     $category->update($request->only(['name', 'icon']));

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Category updated successfully.',
    //         'category' => $category,
    //     ]);
    // }


    public function editCategory(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'icon' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // image file validation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $oldPath = $category->icon;

        if ($oldPath && Storage::disk('public')->exists(str_replace('/storage/', '', $oldPath))) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $oldPath));
        }

        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('icon', $filename, 'public'); // stored in /storage/app/public/categories
            $category->icon = '/storage/' . $filepath;
        }

        if ($request->has('name')) {
            $category->name = $request->name;
        }

        $category->save();

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
