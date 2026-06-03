<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tender;
use App\Services\TenderUpsert;
use Illuminate\Http\Request;

class TenderApiController extends Controller
{
    public function __construct(private TenderUpsert $upsert) {}

    /** Import en masse d'appels d'offres : { "tenders": [ {idweb, objet, ...} ] }. */
    public function bulk(Request $request)
    {
        $request->validate([
            'tenders'           => ['required', 'array', 'min:1', 'max:2000'],
            'tenders.*.idweb'   => ['required', 'string'],
        ]);

        $created = $updated = $skipped = 0;
        foreach ($request->input('tenders') as $row) {
            $res = $this->upsert->upsert($row);
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
            'total'   => Tender::count(),
        ]);
    }
}
