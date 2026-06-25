<?php

namespace App\Services;

use App\Models\Bug;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Décodage, validation et stockage privé des images base64 jointes à un ticket
 * d'assistance déclaré depuis le site du client.
 */
class SupportTicketImages
{
    private const EXT = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * Stocke les images du ticket (disque privé `local`) et crée les BugImage.
     *
     * @param  array<int,array{data?:string,nom?:string}|string>  $images
     */
    public function store(Bug $bug, array $images): int
    {
        $max     = (int) config('crm.support_api.max_images', 4);
        $maxOcts = (int) config('crm.support_api.max_image_mo', 5) * 1024 * 1024;

        if (count($images) > $max) {
            throw ValidationException::withMessages([
                'images' => "Maximum {$max} images par demande.",
            ]);
        }

        $n = 0;
        foreach ($images as $i => $image) {
            $raw = is_array($image) ? ($image['data'] ?? '') : (string) $image;
            $nom = is_array($image) ? ($image['nom'] ?? null) : null;

            $binary = $this->decode($raw, $i);

            if (strlen($binary) > $maxOcts) {
                throw ValidationException::withMessages([
                    "images.{$i}" => 'Image trop volumineuse (max '.config('crm.support_api.max_image_mo').' Mo).',
                ]);
            }

            $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $binary) ?: '';
            if (! isset(self::EXT[$mime])) {
                throw ValidationException::withMessages([
                    "images.{$i}" => 'Format non supporté (JPEG, PNG, WEBP ou GIF attendu).',
                ]);
            }

            $chemin = "support/{$bug->id}/".Str::uuid().'.'.self::EXT[$mime];
            Storage::disk('local')->put($chemin, $binary);

            $bug->images()->create([
                'chemin' => $chemin,
                'nom'    => $nom ? Str::limit($nom, 200, '') : null,
                'mime'   => $mime,
                'taille' => strlen($binary),
            ]);
            $n++;
        }

        return $n;
    }

    /** Décode une image base64 (avec ou sans préfixe data-URI). */
    private function decode(string $raw, int $i): string
    {
        if ($raw === '') {
            throw ValidationException::withMessages(["images.{$i}" => 'Image vide.']);
        }

        // Retire l'éventuel préfixe « data:image/png;base64, ».
        if (str_contains($raw, ',') && str_starts_with($raw, 'data:')) {
            $raw = substr($raw, strpos($raw, ',') + 1);
        }

        $binary = base64_decode(str_replace(' ', '+', $raw), true);

        if ($binary === false || $binary === '') {
            throw ValidationException::withMessages(["images.{$i}" => 'Image base64 invalide.']);
        }

        return $binary;
    }
}
