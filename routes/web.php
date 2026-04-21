<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\Member;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Homepage
Route::get('/', function () {
    return view('welcome');
});

// 2. Scan Verification API
Route::post('/check-in', function (Request $request) {
    $qrData = $request->qr_code;
    
    if (str_contains($qrData, '|')) {
        $parts = explode('|', $qrData);
        $name = trim($parts[0]);
        $code = trim($parts[1]);

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
        'message' => 'Identity Not Found'
    ], 404);
});

// 3. Optimized CSV Import (Uses low memory)
Route::get('/update-database-secure-path', function () {
    $path = storage_path('app/DATA_SECURE.csv');

    if (!file_exists($path)) {
        return "Error: DATA_SECURE.csv not found in storage/app/.";
    }

    try {
        $file = fopen($path, 'r');
        fgetcsv($file); // Skip header row

        DB::beginTransaction();
        
        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            // CSV columns: 0=Name, 1=Class, 2=Code
            $name  = isset($row[0]) ? trim($row[0]) : '';
            $class = isset($row[1]) ? trim($row[1]) : '';
            $code  = isset($row[2]) ? trim($row[2]) : '';

            if (!empty($name) && !empty($code)) {
                Member::updateOrCreate(
                    ['security_code' => $code],
                    ['name' => $name, 'class' => $class]
                );
                $count++;
            }
        }
        
        fclose($file);
        DB::commit();
        
        return "Database Updated! Successfully processed $count students from CSV.";

    } catch (\Exception $e) {
        if(isset($file)) fclose($file);
        DB::rollBack();
        return "Critical Error: " . $e->getMessage();
    }
});