<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'officina_nome' => 'required|string|max:255',
            'officina_indirizzo' => 'nullable|string|max:255',
            'officina_piva' => 'nullable|string|max:20',
            'officina_telefono' => 'nullable|string|max:30',
            'officina_email' => 'nullable|email|max:255',
            'costo_orario_default' => 'nullable|numeric|min:0',
            'iva_default' => 'nullable|numeric|min:0|max:100',
            'clausola_preventivo' => 'nullable|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Impostazioni salvate con successo.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => [
                'required', 'file',
                'mimes:png,jpg,jpeg',
                'mimetypes:image/png,image/jpeg',
                'max:2048',
            ],
        ]);

        $path = $request->file('logo')->storeAs('public/images', 'logo.png');
        Storage::copy($path, 'public/logo.png');

        // Copia in public/images per accesso diretto
        $request->file('logo')->move(public_path('images'), 'logo.png');

        return back()->with('success', 'Logo aggiornato con successo.');
    }
}
