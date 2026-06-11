import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';

// Surveillance de facturation mensuelle : config + suivi mois par mois.
// On ne GÉNÈRE pas de facture : on saisit la référence (= preuve d'envoi).
// Référence manquante passé l'échéance → mois « en retard » + alerte (dashboard/e-mail).
const FACT_BADGE = {
    envoyee:   'bg-green-100 text-green-700',
    a_venir:   'bg-gray-100 text-gray-500',
    en_retard: 'bg-rose-100 text-rose-700',
};
const FACT_LABEL = { envoyee: 'Envoyée', a_venir: 'À venir', en_retard: 'En retard' };

export default function FacturationMensuelle({ prospect, facturation }) {
    const cfg = useForm({
        facturation_active: !!facturation.active,
        facturation_jour: facturation.jour || 5,
        facturation_debut: facturation.debut || '',
        facturation_montant_ht: facturation.montant_ht || '',
        facturation_libelle: facturation.libelle || '',
    });
    const saveCfg = (e) => {
        e.preventDefault();
        cfg.put(route('prospects.facturation', prospect.id), { preserveScroll: true });
    };

    const periodes = facturation.periodes || [];
    const [refs, setRefs] = useState(() =>
        Object.fromEntries(periodes.map((m) => [m.periode, m.reference || ''])));
    const saveRef = (m) => router.put(route('prospects.factures.upsert', prospect.id),
        { periode: `${m.periode}-01`, reference: refs[m.periode] || '' },
        { preserveScroll: true });

    const nbRetard = periodes.filter((m) => m.statut === 'en_retard').length;

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold text-gray-800">🧾 Facturation mensuelle</h3>
                {cfg.recentlySuccessful && <span className="text-xs text-green-600">Enregistré ✓</span>}
            </div>

            <div className="grid grid-cols-2 gap-3 text-sm">
                <label className="col-span-2 flex items-center gap-2">
                    <input type="checkbox" checked={cfg.data.facturation_active}
                        onChange={(e) => cfg.setData('facturation_active', e.target.checked)} />
                    <span>Surveiller la facturation mensuelle de ce client</span>
                </label>
                <div>
                    <label className="block text-xs text-gray-500">Jour d’échéance (du mois suivant)</label>
                    <input type="number" min="1" max="28" value={cfg.data.facturation_jour}
                        onChange={(e) => cfg.setData('facturation_jour', e.target.value)}
                        className="w-full rounded-md border-gray-300 text-sm" />
                </div>
                <div>
                    <label className="block text-xs text-gray-500">Premier mois facturé</label>
                    <input type="month" value={(cfg.data.facturation_debut || '').substring(0, 7)}
                        onChange={(e) => cfg.setData('facturation_debut', e.target.value ? e.target.value + '-01' : '')}
                        className="w-full rounded-md border-gray-300 text-sm" />
                </div>
                <div>
                    <label className="block text-xs text-gray-500">Montant HT mensuel (€)</label>
                    <input type="number" step="0.01" min="0" value={cfg.data.facturation_montant_ht || ''}
                        onChange={(e) => cfg.setData('facturation_montant_ht', e.target.value)}
                        className="w-full rounded-md border-gray-300 text-sm" />
                </div>
                <div>
                    <label className="block text-xs text-gray-500">Libellé</label>
                    <input type="text" value={cfg.data.facturation_libelle || ''}
                        onChange={(e) => cfg.setData('facturation_libelle', e.target.value)}
                        placeholder="ex. Maintenance mensuelle"
                        className="w-full rounded-md border-gray-300 text-sm" />
                </div>
            </div>
            <button onClick={saveCfg} disabled={cfg.processing}
                className="mt-3 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                {cfg.processing ? 'Enregistrement…' : 'Enregistrer la surveillance'}
            </button>

            {facturation.active && periodes.length > 0 && (
                <div className="mt-5 border-t border-gray-100 pt-4">
                    <p className="mb-2 text-xs text-gray-500">
                        Saisis la référence de facture de chaque mois (vide = non envoyée → alerte passé l’échéance).
                        {nbRetard > 0 && <span className="ml-1 font-medium text-rose-600">{nbRetard} en retard.</span>}
                    </p>
                    <ul className="space-y-2">
                        {periodes.map((m) => (
                            <li key={m.periode} className="flex items-center gap-2 text-sm">
                                <span className="w-32 capitalize text-gray-700">{m.mois_label}</span>
                                <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${FACT_BADGE[m.statut]}`}>
                                    {FACT_LABEL[m.statut]}
                                </span>
                                <input value={refs[m.periode] ?? ''}
                                    onChange={(e) => setRefs({ ...refs, [m.periode]: e.target.value })}
                                    placeholder="N° de facture…"
                                    className="flex-1 rounded-md border-gray-300 text-sm" />
                                <button onClick={() => saveRef(m)}
                                    className="rounded-md bg-gray-100 px-3 py-1 text-xs hover:bg-gray-200">OK</button>
                                {m.statut === 'en_retard' && (
                                    <span className="text-rose-500" title={`Échéance dépassée le ${m.echeance}`}>⚠</span>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
            {facturation.active && periodes.length === 0 && (
                <p className="mt-4 text-xs text-gray-400">
                    Renseigne un « premier mois facturé » puis enregistre pour générer le suivi.
                </p>
            )}
        </div>
    );
}
