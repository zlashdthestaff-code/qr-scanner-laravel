<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;

class CheckInController extends Controller
{
    public function index() {
        return view('welcome'); // Using the default blade
    }

    public function verify(Request $request)
    {
        $qrData = $request->input('qr_code');
        $parts = explode('|', $qrData);

        if (count($parts) !== 2) {
            return response()->json(['status' => 'error', 'message' => 'Format QR Tidak Dikenali']);
        }

        $member = Member::where('name', $parts[0])
                        ->where('security_code', $parts[1])
                        ->first();

        if ($member) {
            return response()->json([
                'status' => 'success',
                'message' => "Selamat Datang, {$member->name} ({$member->class})",
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'ID Palsu atau Tidak Terdaftar!']);
    }
}