<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckInController;
use App\Models\Member;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. The Main Scanner Page (Mobile Friendly)
Route::get('/', [CheckInController::class, 'index'])->name('scanner');

// 2. The Verification Logic (Called via AJAX from the scanner)
Route::post('/verify', [CheckInController::class, 'verify'])->name('verify');

/**
 * 3. THE DATA IMPORT ROUTE
 * * SECURITY: Change 'update-database-secure-path' to something secret.
 * INSTRUCTIONS: 
 * 1. Upload your 'DATA_SECURE.xlsx' to the /storage/app/ folder on your server.
 * 2. Visit: yourdomain.com/update-database-secure-path
 */
Route::get('/update-database-secure-path', function() {
    
    // Path to the file inside storage/app/
    $path = storage_path('app/DATA_SECURE.xlsx');
    
    if (!file_exists($path)) {
        return response("Error: DATA_SECURE.xlsx not found in /storage/app/. Please upload the file via FTP.", 404);
    }

    try {
        // Load the data from the first sheet
        $data = Excel::toArray([], $path)[0];
        $count = 0;

        // Skip the header row
        foreach (array_slice($data, 1) as $row) {
            // Check if name and security code exist in the row
            if (isset($row[0], $row[2])) {
                Member::updateOrCreate(
                    ['security_code' => (string)$row[2]], // Unique key to find existing student
                    [
                        'name'  => (string)$row[0],
                        'class' => (string)$row[1],
                    ]
                );
                $count++;
            }
        }

        return "Database Updated Successfully! Total Students: " . $count;

    } catch (\Exception $e) {
        return "Error during import: " . $e->getMessage();
    }
});