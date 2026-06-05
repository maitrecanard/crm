import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const STATUT_COLORS = {
    a_contacter: 'bg-gray-100 text-gray-700',
    contacte: 'bg-blue-100 text-blue-700',
    relance: 'bg-amber-100 text-amber-700',
    rdv: 'bg-violet-100 text-violet-700',
    gagne: 'bg-green-100 text-green-700',
    perdu: 'bg-red-100 text-red-700',
    ignore: 'bg-gray-100 text-gray-400',
};

const SOURCE_LABELS = {
    clients_tech: 'Clients tech',
    grands_comptes: 'Grands comptes',
    besoins: 'Appels d’offres',
    pme: 'PME',
};

function Badge({ statut, statuts }) {
    return (
        <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${STATUT_COLORS[statut] || 'bg-gray-100'}`}>
            {statuts[statut] || statut}
        </span>
    );
}

export default function Index({ prospects, filters, statuts, stats, sources, secteurs, total }) {
    const [q, setQ] = useState(filters.q || '');

    const apply = (patch) => {
        const next = { ...filters, ...patch };
        Object.keys(next).forEach((k) => !next[k] && delete next[k]);
        router.get(route('prospects.index'), next, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Prospects</h2>}>
            <Head title="Prospects" />

            <div className="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
                {/* Pipeline */}
                <div className="mb-4 flex flex-wrap gap-2">
                    <button onClick={() => apply({ statut: null })}
                        className={`rounded-lg border px-3 py-2 text-sm ${!filters.statut ? 'border-indigo-500 bg-indigo-50' : 'bg-white'}`}>
                        Tous <span className="font-semibold">{total}</span>
                    </button>
                    {Object.entries(statuts).map(([key, label]) => (
                        <button key={key} onClick={() => apply({ statut: key })}
                            className={`rounded-lg border px-3 py-2 text-sm ${filters.statut === key ? 'border-indigo-500 bg-indigo-50' : 'bg-white'}`}>
                            {label} <span className="font-semibold">{stats[key] || 0}</span>
                        </button>
                    ))}
                </div>

                {/* Filtres */}
                <div className="mb-4 flex flex-wrap items-center gap-2">
                    <form onSubmit={(e) => { e.preventDefault(); apply({ q }); }} className="flex-1 min-w-[220px]">
                        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Rechercher (entreprise, ville, secteur, signal)…"
                            className="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </form>
                    <select value={filters.source_fichier || ''} onChange={(e) => apply({ source_fichier: e.target.value })}
                        className="rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">Toutes sources</option>
                        {sources.map((s) => <option key={s} value={s}>{SOURCE_LABELS[s] || s}</option>)}
                    </select>
                    <select value={filters.secteur || ''} onChange={(e) => apply({ secteur: e.target.value })}
                        className="max-w-[220px] rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">Tous secteurs</option>
                        {secteurs.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                    {(filters.q || filters.source_fichier || filters.secteur || filters.localite) && (
                        <button onClick={() => { setQ(''); router.get(route('prospects.index')); }}
                            className="text-sm text-gray-500 underline">Réinitialiser</button>
                    )}
                </div>

                {/* Table */}
                <div className="overflow-hidden rounded-lg bg-white shadow">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th className="px-4 py-3">Entreprise</th>
                                <th className="px-4 py-3">Localité</th>
                                <th className="px-4 py-3">Secteur</th>
                                <th className="px-4 py-3">Contact</th>
                                <th className="px-4 py-3">Source</th>
                                <th className="px-4 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {prospects.data.map((p) => (
                                <tr key={p.id} className="hover:bg-gray-50 cursor-pointer"
                                    onClick={() => router.visit(route('prospects.show', p.id))}>
                                    <td className="px-4 py-3 font-medium text-gray-900">{p.entreprise}</td>
                                    <td className="px-4 py-3 text-gray-600">{p.localite}</td>
                                    <td className="px-4 py-3 text-gray-600">{p.secteur}</td>
                                    <td className="px-4 py-3">
                                        {p.telephone && <span title={p.telephone} className="mr-1">📞</span>}
                                        {p.email && <span title={p.email}>✉️</span>}
                                    </td>
                                    <td className="px-4 py-3 text-gray-500">{SOURCE_LABELS[p.source_fichier] || p.source_fichier}</td>
                                    <td className="px-4 py-3"><Badge statut={p.statut} statuts={statuts} /></td>
                                </tr>
                            ))}
                            {prospects.data.length === 0 && (
                                <tr><td colSpan="6" className="px-4 py-8 text-center text-gray-400">Aucun prospect</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="mt-4 flex flex-wrap gap-1">
                    {prospects.links.map((link, i) => (
                        <button key={i} disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true })}
                            className={`rounded px-3 py-1 text-sm ${link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600'} ${!link.url && 'opacity-40'}`}
                            dangerouslySetInnerHTML={{ __html: link.label }} />
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
