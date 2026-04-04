<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessConnection;
use App\Models\BusinessSetting;

class BusinessSettingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get business connections that this user has access to
        $query = BusinessConnection::with('settings');

        // If user is super admin, show all business connections
        if ($user->business_id === 1) {
            $businessConnections = $query->paginate(15);
        } else {
            // Regular users only see their own business connections
            $businessConnections = $query->where('insurance_company_id', $user->business_id)->paginate(15);
        }

        // Get first business connection for default display
        $firstConnection = $businessConnections->first();

        return view('settings.index', compact('businessConnections', 'firstConnection'));
    }

    public function update(Request $request, BusinessConnection $businessConnection = null)
    {
        $user = Auth::user();

        // Handle business connection selection
        if ($request->has('business_connection_id')) {
            $businessConnection = BusinessConnection::findOrFail($request->business_connection_id);
        }

        // Additional check: users can only edit their own business connections unless super admin
        if ($user->business_id !== 1 && $businessConnection->insurance_company_id !== $user->business_id) {
            return redirect()->route('settings.index')->with('error', 'You can only edit your own business settings.');
        }

        $validated = $request->validate([
            'visit_authorization_duration' => 'required|integer|min:1|max:365',
        ]);

        // Update or create setting
        BusinessSetting::setValue(
            $businessConnection->id,
            'visit_authorization_duration',
            $validated['visit_authorization_duration']
        );

        return redirect()
            ->route('settings.index')
            ->with('success', "Visit authorization duration updated for {$businessConnection->connected_business_name}.");
    }
}
