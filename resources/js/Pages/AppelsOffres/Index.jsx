import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const STATUT_COLORS = {
    a_etudier: 'bg-gray-100 text-gray-700',
    go: 'bg-blue-100 text-blue-700',
    dossier: 'bg-amber-100 text-amber-700',
    depose: 'bg-violet-100 text-violet-700',
    gagne: 'bg-green-100 text-green-700',
    perdu: 'bg-red-100 text-red-700',
    abandonne: 'bg-gray-100 text-gray-400',
    expire: 'bg-gray-100 text-gray-400',
};

function urgence(j) {
    if (j === null) return 'text-gray-400';
    if (j < 0) return 'text-gray-400 line-through';
    if (j <= 3) return 'text-red-600 font-semibold';
    if (j <= 7) return 'text-amber-600 font-medium';
    return 'text-gray-700';
}

export default function Index({ tenders, filters, statuts, stats, total }) {
    const [q, setQ] = useState(filters.q || '');
    const refresh = useForm({});

    const apply = (patch) => {
        const next = { ...filters, ...patch };
        Object.keys(next).forEach((k) => !next[k] && delete next[k]);
        router.get(route('ao.index'), next, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Appels d’offres</h2>}>
            <Head title="Appels d’offres" />

            <div className="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                <div className="mb-4 flex flex-wrap items-center gap-2">
                    <button onClick={() => apply({ statut: null })}
                        className={`rounded-lg border px-3 py-2 text-sm ${!filters.statut ? 'border-indigo-500 bg-indigo-50' : 'bg-white'}`}>
                        Tous <span className="font-semibold">{total}</span>
                    </button>
                    {Object.entries(statuts).filter(([k]) => stats[k]).map(([key, label]) => (
                        <button key={key} onClick={() => apply({ statut: key })}
                            className={`rounded-lg border px-3 py-2 text-sm ${filters.statut === key ? 'border-indigo-500 bg-indigo-50' : 'bg-white'}`}>
                            {label} <span className="font-semibold">{stats[key]}</span>
                        </button>
                    ))}
                    <button onClick={() => refresh.post(route('ao.refresh'), { preserveScroll: true })}
                        disabled={refresh.processing}
                        className="ml-auto rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">
                        {refresh.processing ? 'Rafraîchissement…' : '↻ Rafraîchir (BOAMP)'}
                    </button>
                </div>

                <form onSubmit={(e) => { e.preventDefault(); apply({ q }); }} className="mb-4">
                    <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Rechercher (objet, acheteur, département)…"
                        className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </form>

                <div className="overflow-hidden rounded-lg bg-white shadow">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th className="px-4 py-3">Date limite</th>
                                <th className="px-4 py-3">Acheteur</th>
                                <th className="px-4 py-3">Objet</th>
                                <th className="px-4 py-3">Dépt</th>
                                <th className="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {tenders.map((t) => (
                                <tr key={t.id} className="hover:bg-gray-50 cursor-pointer"
                                    onClick={() => router.visit(route('ao.show', t.id))}>
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        <span className={urgence(t.jours_restants)}>
                                            {t.date_limite ? new Date(t.date_limite).toLocaleDateString('fr-FR') : '—'}
                                        </span>
                                        {t.jours_restants !== null && t.jours_restants >= 0 && (
                                            <span className="ml-1 text-xs text-gray-400">J-{t.jours_restants}</span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-gray-700">{t.acheteur}</td>
                                    <td className="px-4 py-3 text-gray-600">{t.objet?.substring(0, 70)}</td>
                                    <td className="px-4 py-3 text-gray-500">{t.departement}</td>
                                    <td className="px-4 py-3">
                                        <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${STATUT_COLORS[t.statut] || 'bg-gray-100'}`}>
                                            {statuts[t.statut] || t.statut}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                            {tenders.length === 0 && (
                                <tr><td colSpan="5" className="px-4 py-8 text-center text-gray-400">
                                    Aucun appel d’offres. Clique sur « Rafraîchir (BOAMP) ».
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
