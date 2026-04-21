<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| Web Routes - FINAL VERSION (Optimized for QR Scanner)
|--------------------------------------------------------------------------
*/

// 1. The Main Scanner Homepage
Route::get('/', function () {
    return view('welcome');
});

// 2. Verification API (Processes scans from the phone camera)
Route::post('/check-in', function (Request $request) {
    $qrData = $request->qr_code; // Expected: "Name|SecurityCode"
    
    if (str_contains($qrData, '|')) {
        $parts = explode('|', $qrData);
        $name = trim($parts[0]);
        $code = trim($parts[1]);

        // Find student by code
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

// 3. Optimized Excel Import (Prevents Timeouts and Ghost Rows)
Route::get('/update-database-secure-path', function () {
    // Increase resources for processing large Excel files
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 300);

    $path = storage_path('app/DATA_SECURE.xlsx');

    if (!file_exists($path)) {
        return "Error: DATA_SECURE.xlsx not found in storage/app/.";
    }

    try {
        // Load Excel to Array
        $rows = Excel::toArray([], $path)[0];
        $students = array_slice($rows, 1); // Remove header

        // Start Transaction for Speed (TKJ Optimization)
        DB::beginTransaction();
        
        $count = 0;
        foreach ($students as $row) {
            // Clean and Validate data
            $name  = isset($row[0]) ? trim((string)$row[0]) : '';
            $class = isset($row[1]) ? trim((string)$row[1]) : '';
            $code  = isset($row[2]) ? trim((string)$row[2]) : '';

            // Only import if Name and Code exist (Ignores empty rows)
            if (!empty($name) && !empty($code)) {
                Member::updateOrCreate(
                    ['security_code' => $code], // Search key
                    [
                        'name' => $name,
                        'class' => $class
                    ]
                );
                $count++;
            }
        }

        DB::commit();
        return "Database Updated Successfully! Cleaned and Imported $count students.";

    } catch (\Exception $e) {
        DB::rollBack();
        return "Critical Error during Import: " . $e->getMessage();
    }
});