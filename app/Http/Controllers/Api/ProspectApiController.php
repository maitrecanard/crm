<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use App\Services\ProspectUpsert;
use Illuminate\Http\Request;

class ProspectApiController extends Controller
{
    public function __construct(private ProspectUpsert $upsert) {}

    /** Crée/met à jour un prospect unique. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'entreprise'    => ['required', 'string', 'max:255'],
            'localite'      => ['nullable', 'string', 'max:255'],
            'telephone'     => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'string', 'max:255'],
            'categorie'     => ['nullable', 'string', 'max:255'],
            'secteur'       => ['nullable', 'string', 'max:255'],
            'signal_alerte' => ['nullable', 'string'],
            'source_url'    => ['nullable', 'string', 'max:1000'],
            'requete'       => ['nullable', 'string', 'max:255'],
            'source_fichier' => ['nullable', 'string', 'max:100'],
        ]);

        $res = $this->upsert->upsert($data);

        return response()->json([
            'id'      => $res['prospect']->id,
            'created' => $res['created'],
        ], $res['created'] ? 201 : 200);
    }

    /** Import en masse : { "prospects": [ {...}, ... ] }. */
    public function bulk(Request $request)
    {
        $request->validate([
            'prospects' => ['required', 'array', 'min:1', 'max:2000'],
            'source'    => ['nullable', 'string', 'max:100'],
        ]);
        // Les lignes invalides (entreprise vide) sont ignorées, pas rejetées.

        $created = $updated = $skipped = 0;
        foreach ($request->input('prospects') as $row) {
            $res = $this->upsert->upsert($row, $request->input('source'));
            if ($res === null) {
                $skipped++;
            } else {
                $res['created'] ? $created++ : $updated++;
            }
        }

        return response()->json([
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'total'   => Prospect::count(),
        ]);
    }
}
