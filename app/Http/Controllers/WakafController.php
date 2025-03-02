<?php

namespace App\Http\Controllers;

use App\Models\Wakaf;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class WakafController extends Controller
{
    public function index()
    {
        return Wakaf::all();
    }

    public function store(Request $request)
    {
        $request->merge([
            'amount' => $request->input('amount', 0),
            'distribution' => $request->input('distribution', 0),
        ]);
        
        $validatedData = $request->validate([
            'category_name' => 'required|string|max:255',
            'thumbnail' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'amount' => 'required|numeric',
            'distribution' => 'required|numeric',
        ]);

            
        if ($request->hasFile('thumbnail')) {
            $uploadedFile = Cloudinary::upload($request->file('thumbnail')->getRealPath(), ['folder' => 'campaign_images']);
            $validatedData['thumbnail'] = $uploadedFile->getSecurePath();
        }

        $wakaf = Wakaf::create($validatedData);
        return response()->json($wakaf, 201);
    }

    public function show($id)
    {
        return Wakaf::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $wakaf = Wakaf::findOrFail($id);
        $wakaf->update($request->all());
        return response()->json($wakaf);
    }

    public function destroy($id)
    {
        Wakaf::destroy($id);
        return response()->json(null, 204);
    }
}
