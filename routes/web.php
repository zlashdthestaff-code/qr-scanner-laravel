<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use Illuminate\Http\Request;

// 1. Homepage
Route::get('/', function () { return view('welcome'); });

// 2. Scan Verification (Changed to /verify to match your logs)
Route::post('/verify', function (Request $request) {
    $qrData = $request->qr_code;
    
    if (str_contains($qrData, '|')) {
        $parts = explode('|', $qrData);
        $name = trim($parts[0]);
        $code = trim($parts[1]);

        $member = Member::where('security_code', $code)->first();

        if ($member) {
            // Block if already scanned
            if ($member->is_checked_in) {
                return response()->json([
                    'status' => 'error',
                    'message' => "ALREADY SCANNED: {$member->name} has already checked in."
                ], 403);
            }

            // Mark as scanned
            $member->update(['is_checked_in' => true]);

            return response()->json([
                'status' => 'success',
                'message' => "VERIFIED: {$member->name} ({$member->class})",
                'student' => $member
            ]);
        }
    }
    return response()->json(['status' => 'error', 'message' => 'Identity Not Found'], 404);
});

// 3. Super-Fast CSV Import
Route::get('/update-database-secure-path', function () {
    $path = storage_path('app/DATA_SECURE.csv');
    if (!file_exists($path)) return "Error: DATA_SECURE.csv not found.";

    try {
        $file = fopen($path, 'r');
        fgetcsv($file); // Skip header row

        DB::beginTransaction();
        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            if (!empty($row[0]) && !empty($row[2])) {
                DB::table('members')->updateOrInsert(
                    ['security_code' => trim($row[2])],
                    [
                        'name' => trim($row[0]),
                        'class' => trim($row[1]),
                        'is_checked_in' => false,
                        'updated_at' => now(),
                        'created_at' => now()
                    ]
                );
                $count++;
            }
        }
        fclose($file);
        DB::commit();
        return "Import Success! Processed $count students. Go to / to start scanning.";
    } catch (\Exception $e) {
        if(isset($file)) fclose($file);
        DB::rollBack();
        return "Error: " . $e->getMessage();
    }
});

// 4. Secret Reset Link
Route::get('/reset-all-attendance', function() {
    Member::where('is_checked_in', true)->update(['is_checked_in' => false]);
    return "All student statuses have been reset to 'Not Checked In'.";
});