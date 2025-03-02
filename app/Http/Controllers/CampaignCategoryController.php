<?php

namespace App\Http\Controllers;

use App\Models\CampaignCategory;
use Illuminate\Http\Request;

class CampaignCategoryController extends Controller
{
    public function index()
    {
        return CampaignCategory::all();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'campaign_category' => 'required|string|max:255',
        ]);

        $category = CampaignCategory::create($validatedData);
        return response()->json($category, 201);
    }

    public function show($id)
    {
        return CampaignCategory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = CampaignCategory::findOrFail($id);
        $category->update($request->all());
        return response()->json($category);
    }

    public function destroy($id)
    {
        CampaignCategory::destroy($id);
        return response()->json(null, 204);
    }
}
