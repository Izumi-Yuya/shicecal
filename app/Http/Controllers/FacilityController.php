<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    /**
     * Display a listing of facilities
     */
    public function index(Request $request)
    {
        $query = Facility::query();

        // Search functionality
        if ($request->filled('search_name')) {
            $query->searchByName($request->search_name);
        }

        if ($request->filled('search_office_code')) {
            $query->searchByOfficeCode($request->search_office_code);
        }

        if ($request->filled('search_address')) {
            $query->searchByAddress($request->search_address);
        }

        // Order by updated_at desc to show most recently updated first
        $facilities = $query->orderBy('updated_at', 'desc')
                           ->paginate(20);

        return view('facilities.index', compact('facilities'));
    }

    /**
     * Show the form for creating a new facility
     */
    public function create()
    {
        return view('facilities.create');
    }

    /**
     * Store a newly created facility in storage
     */
    public function store(Request $request)
    {
        // Implementation will be added in later tasks
        return redirect()->route('facilities.index');
    }

    /**
     * Display the specified facility
     */
    public function show(Facility $facility)
    {
        return view('facilities.show', compact('facility'));
    }

    /**
     * Show the form for editing the specified facility
     */
    public function edit(Facility $facility)
    {
        return view('facilities.edit', compact('facility'));
    }

    /**
     * Update the specified facility in storage
     */
    public function update(Request $request, Facility $facility)
    {
        // Implementation will be added in later tasks
        return redirect()->route('facilities.index');
    }

    /**
     * Remove the specified facility from storage
     */
    public function destroy(Facility $facility)
    {
        // Implementation will be added in later tasks
        return redirect()->route('facilities.index');
    }
}