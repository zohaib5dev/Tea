<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /** Save the colors the user picked. Send null (or nothing) to go back to the default theme. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $accent = isset($data['accent']) ? strtolower($data['accent']) : null;
        $background = isset($data['background']) ? strtolower($data['background']) : null;

        $request->user()->forceFill(['theme_accent' => $accent, 'theme_background' => $background])->save();

        return ['accent' => $accent, 'background' => $background];
    }
}
