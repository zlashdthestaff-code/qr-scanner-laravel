<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. The Main Scanner Interface
Route::get('/', function () {
    return view('welcome');
});

// 2. The Verification Logic (Called by the Scanner)
Route::post('/check-in', function (Request $request) {
    $qrData = $request->qr_code; // Format expected: "Name|SecurityCode"
    
    if (str_contains($qrData, '|')) {
        $parts = explode('|', $qrData);
        $name = $parts[0];
        $code = $parts[1];

        // Search database for the security code
        $member = Member::where('security_code', $code)->first();

        if ($member) {
            return response()->json([
                'status' => 'success',
                'message' => "VERIFIED: {$member->name} ({$member->class})",
                'student' => $member
            ]);
        }
    }

    return response()->json([
        'status' => 'error',
        'message' => 'Identity Not Found or Invalid QR'
    ], 404);
});

// 3. The Secure Import Route (Optimized for Large Data)
Route::get('/update-database-secure-path', function () {
    // Increase memory and time limits just for this route
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 300);

    $path = storage_path('app/DATA_SECURE.xlsx');

    if (!file_exists($path)) {
        return "Error: DATA_SECURE.xlsx not found in storage/app/. Did you push it to GitHub?";
    }

    try {
        // Load the Excel data into an array
        $rows = Excel::toArray([], $path)[0];
        
        // Remove the header row
        $students = array_slice($rows, 1);

        // Use a Database Transaction to prevent 504 Timeouts
        DB::beginTransaction();

        foreach ($students as $row) {
            if (isset($row[0], $row[2])) {
                Member::updateOrCreate(
                    ['security_code' => (string)$row[2]], // Unique identifier
                    [
                        'name' => (string)$row[0],
                        'class' => (string)$row[1]
                    ]
                );
            }
        }

        DB::commit();
        return "Database Updated Successfully! Total Students Processed: " . count($students);

    } catch (\Exception $e) {
        DB::rollBack();
        return "Critical Error: " . $e->getMessage();
    }
});