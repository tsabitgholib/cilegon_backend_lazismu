<?php

namespace App\Http\Controllers;

use App\Models\Infak;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;

class InfakController extends Controller
{
    public function index()
    {
        return Infak::all();
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

        $infak = Infak::create($validatedData);
        return response()->json($infak, 201);
    }

    public function show($id)
    {
        return Infak::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        try {
            $infak = Infak::findOrFail($id);

            $validatedData = $request->validate([
                'category_name' => 'sometimes|string|max:255',
                'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            if ($request->hasFile('thumbnail')) {
                $uploadedFile = Cloudinary::upload($request->file('thumbnail')->getRealPath(), ['folder' => 'campaign_images']);
                $validatedData['thumbnail'] = $uploadedFile->getSecurePath();
            } else {
                $validatedData['thumbnail'] = $infak->thumbnail;
            }

            $validatedData['category_name'] = $request->input('category_name', $infak->category_name);

            $infak->update($validatedData);

            return response()->json($infak, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function destroy($id)
    {
        Infak::destroy($id);
        return response()->json(null, 204);
    }
}

