<?php

namespace App\Http\Controllers;

use App\Models\BugImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Sert les images d'incident depuis le disque privé (admin authentifié uniquement). */
class SupportImageController extends Controller
{
    public function show(BugImage $image): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($image->chemin), 404);

        return Storage::disk('local')->response(
            $image->chemin,
            $image->nom ?: basename($image->chemin),
            ['Content-Type' => $image->mime ?: 'application/octet-stream'],
        );
    }
}
