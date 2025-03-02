<?php

namespace App\Http\Controllers;

use App\Models\Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|mimes:pdf|max:2048',
        ]);

        $filePath = $request->file('file')->store('reports', 'public');

        $reports = Reports::create([
            'title' => $request->title,
            'file_path' => $filePath,
        ]);

        return response()->json([
            'message' => 'File uploaded successfully',
            'reports' => $reports,
        ], 201);
    }

    public function index(Request $request)
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $query = Reports::query();

        if ($month && $year) {
            $query->whereMonth('created_at', $month)
                  ->whereYear('created_at', $year);
        } elseif ($year) {
            $query->whereYear('created_at', $year);
        } elseif ($month) {
            $query->whereMonth('created_at', $month);
        }

        $reports = $query->get();

        return response()->json($reports);
    }
}
