<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Log;

use App\Models\Campaign;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');

        $campaigns = Campaign::with('category')
            ->when($search, function ($query, $search) {
                $query->where('campaign_name', 'like', '%' . $search . '%');
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('campaign_category_id', $categoryId);
            })
            ->orderBy('id', 'desc')
            ->paginate(12);

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        // dd($request);
        try {
            $validatedData = $request->validate([
                'campaign_category_id' => 'required|exists:campaign_categories,id',
                'campaign_name' => 'required|string|max:255',
                'campaign_code' => 'required|string|unique:campaigns,campaign_code',
                'campaign_thumbnail' => 'required|image|mimes:jpg,jpeg,png|max:2048',
                'campaign_image_1' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'campaign_image_2' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'campaign_image_3' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'description' => 'required',
                'location' => 'required|string|max:255',
                'target_amount' => 'required|numeric',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);
    
            if ($request->hasFile('campaign_thumbnail')) {
                $uploadedFile = Cloudinary::upload($request->file('campaign_thumbnail')->getRealPath(), ['folder' => 'cilegon_lazismu/campaign_images']);
                $validatedData['campaign_thumbnail'] = $uploadedFile->getSecurePath();
            }
    
            $validatedData['campaign_image_1'] = $request->hasFile('campaign_image_1')
                ? Cloudinary::upload($request->file('campaign_image_1')->getRealPath(), ['folder' => 'cilegon_lazismu/campaign_images'])->getSecurePath()
                : null;
    
            $validatedData['campaign_image_2'] = $request->hasFile('campaign_image_2')
                ? Cloudinary::upload($request->file('campaign_image_2')->getRealPath(), ['folder' => 'cilegon_lazismu/campaign_images'])->getSecurePath()
                : null;
    
            $validatedData['campaign_image_3'] = $request->hasFile('campaign_image_3')
                ? Cloudinary::upload($request->file('campaign_image_3')->getRealPath(), ['folder' => 'cilegon_lazismu/campaign_images'])->getSecurePath()
                : null;
 
            $validatedData['end_date'] = $validatedData['end_date'] ?? null;
            $validatedData['active'] = 1;
            $validatedData['approved'] = 1;
            $validatedData['priority'] = 0;
            $validatedData['recomendation'] = 0;
            $validatedData['distribution'] = 0;
            $validatedData['current_mount'] = 0;

            $campaign = Campaign::create($validatedData);
    
            return response()->json($campaign, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }    

    public function show($id)
    {
        return Campaign::with('category')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        try {
            $campaign = Campaign::findOrFail($id);
    
            $validatedData = $request->validate([
                'campaign_category_id' => 'sometimes|exists:campaign_categories,id',
                'campaign_name' => 'sometimes|string|max:255',
                'campaign_code' => 'sometimes|string|unique:campaigns,campaign_code,' . $id,
                'campaign_thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'campaign_image_1' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'campaign_image_2' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'campaign_image_3' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'description' => 'sometimes|string',
                'location' => 'sometimes|string|max:255',
                'target_amount' => 'sometimes|numeric',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after_or_equal:start_date',
            ]);
    
            if ($request->hasFile('campaign_thumbnail')) {
                $uploadedFile = Cloudinary::upload($request->file('campaign_thumbnail')->getRealPath(), ['folder' => 'cilegon_lazismu/campaign_images']);
                $validatedData['campaign_thumbnail'] = $uploadedFile->getSecurePath();
            } else {
                $validatedData['campaign_thumbnail'] = $campaign->campaign_thumbnail;
            }
    
            $validatedData['campaign_image_1'] = $request->hasFile('campaign_image_1')
                ? Cloudinary::upload($request->file('campaign_image_1')->getRealPath(), ['folder' => 'cilegon_lazismu/campaign_images'])->getSecurePath()
                : $campaign->campaign_image_1;
    
            $validatedData['campaign_image_2'] = $request->hasFile('campaign_image_2')
                ? Cloudinary::upload($request->file('campaign_image_2')->getRealPath(), ['folder' => 'cilegon_lazismu/campaign_images'])->getSecurePath()
                : $campaign->campaign_image_2;
    
            $validatedData['campaign_image_3'] = $request->hasFile('campaign_image_3')
                ? Cloudinary::upload($request->file('campaign_image_3')->getRealPath(), ['folder' => 'cilegon_lazismu/campaign_images'])->getSecurePath()
                : $campaign->campaign_image_3;
    
            $campaign->update($validatedData);
    
            return response()->json($campaign, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    
    
    public function destroy($id)
    {
        Campaign::destroy($id);
        return response()->json(null, 204);
    }

    public function setPriorityTrue($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->priority = true;
        $campaign->save();

        return response()->json([
            'message' => 'Campaign set priority successfully!',
            'campaign' => $campaign,
        ]);
    }

    public function setPriorityFalse($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->priority = false;
        $campaign->save();

        return response()->json([
            'message' => 'Campaign unset priority successfully!',
            'campaign' => $campaign,
        ]);
    }

    public function getPriorityCampaigns()
    {
        $campaigns = Campaign::with('category')
            ->where('priority', true)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($campaigns, 200);
    }

    public function setRecomendationTrue($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->recomendation = true;
        $campaign->recomendation_updated_at =  now();
        $campaign->save();
    
        return response()->json([
            'message' => 'Campaign set recomendation successfully!',
            'campaign' => $campaign,
        ]);
    }
    
    public function setRecomendationFalse($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->recomendation = false;
        $campaign->save();
    
        return response()->json([
            'message' => 'Campaign unset recomendation successfully!',
            'campaign' => $campaign,
        ]);
    }
    
    public function getRecomendationCampaigns()
    {
        $campaigns = Campaign::with('category')
            ->where('recomendation', true)
            ->orderBy('recomendation_updated_at', 'desc')
            ->get();
    
        return response()->json($campaigns, 200);
    }

    public function setActiveTrue($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->active = true;
        $campaign->save();
    
        return response()->json([
            'message' => 'Campaign set active successfully!',
            'campaign' => $campaign,
        ]);
    }
    
    public function setActiveFalse($id)
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->active = false;
        $campaign->save();
    
        return response()->json([
            'message' => 'Campaign unset active successfully!',
            'campaign' => $campaign,
        ]);
    }

    public function getActiveCampaigns(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');

        $campaigns = Campaign::with('category')
            ->where('active', true)
            ->when($search, function ($query, $search) {
                $query->where('campaign_name', 'like', '%' . $search . '%');
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('campaign_category_id', $categoryId);
            })
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json($campaigns, 200);
    }

    public function getNonActiveCampaigns(Request $request)
    {
        $search = $request->input('search');
        $categoryId = $request->input('category_id');

        $campaigns = Campaign::with('category')
            ->where('active', false)
            ->when($search, function ($query, $search) {
                $query->where('campaign_name', 'like', '%' . $search . '%');
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('campaign_category_id', $categoryId);
            })
            ->orderBy('id', 'desc')
            ->paginate(20);

        return response()->json($campaigns, 200);
    }


}
