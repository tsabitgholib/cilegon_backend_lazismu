<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Infak;
use App\Models\latestNews;
use App\Models\Wakaf;
use App\Models\Zakat;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class LatestNewsController extends Controller
{

    public function index(Request $request, $category)
    {
        $search = $request->query('search');
    
        $news = latestNews::where('category', $category)
            ->whereHas('campaign', function ($query) use ($search) {
                if ($search) {
                    $query->where('campaign_name', 'LIKE', "%$search%");
                }
            })
            ->get();
    
        return response()->json($news);
    }
    

    public function store(Request $request, $category, $id)
    {
        $request->validate([
            'latest_news_date' => 'required|date',
            'image' => 'required|image|mimes:jpg,jpeg,png',
            'description' => 'required|string',
        ]);
    
        $imagePath = Cloudinary::upload($request->file('image')->getRealPath(), [
            'folder' => 'cilegon_lazismu/latest_news',
        ])->getSecurePath();

        $model = null;
        $foreignKey = null;
    
        switch ($category) {
            case 'zakat':
                $model = Zakat::find($id);
                $foreignKey = 'zakat_id';
                break;
    
            case 'infak':
                $model = Infak::find($id);
                $foreignKey = 'infak_id';
                break;
    
            case 'campaign':
                $model = Campaign::find($id);
                $foreignKey = 'campaign_id';
                break;
    
            case 'wakaf':
                $model = Wakaf::find($id);
                $foreignKey = 'wakaf_id';
                break;
            
            default:
                return response()->json(['error' => 'Invalid category'], 400);
        }
    
        if (!$model) {
            return response()->json(['error' => 'Resource not found'], 404);
        }
    
        $news = latestNews::create([
            'latest_news_date' => $request->latest_news_date,
            'image' => $imagePath,
            'description' => $request->description,
            'category' => $category,
            $foreignKey => $model->id,
        ]);
    
        return response()->json($news, 201);
    }    

    public function update(Request $request, $category, $id)
    {
        $news = latestNews::where('category', $category)->findOrFail($id);

        $request->validate([
            'latest_news_date' => 'sometimes|date',
            'image' => 'sometimes|image|mimes:jpg,jpeg,png',
            'description' => 'sometimes|string',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = Cloudinary::upload($request->file('image')->getRealPath(), [
                'folder' => 'cilegon_lazismu/latest_news',
            ])->getSecurePath();
            $news->image = $imagePath;
        }

        $news->update($request->except('image'));

        return response()->json($news);
    }

    public function destroy($id)
    {
        $news = latestNews::findOrFail($id);
        $news->delete();

        return response()->json(['message' => 'Latest news deleted successfully.']);
    }


    public function getByCategoryAndEntityId($category, $id)
    {
        $validCategories = ['campaign', 'zakat', 'infak', 'wakaf'];
        if (!in_array($category, $validCategories)) {
            return response()->json(['error' => 'Invalid category'], 400);
        }

        $column = $category . '_id';
        $latestNews = latestNews::where($column, $id)->get();

        if ($latestNews->isEmpty()) {
            return response()->json(['message' => 'No latest news found for this category and ID'], 404);
        }

        return response()->json($latestNews);
    }
}
