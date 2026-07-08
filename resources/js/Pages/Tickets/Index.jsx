import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const STATUT_COLORS = {
    nouveau: 'bg-gray-100 text-gray-700',
    en_cours: 'bg-blue-100 text-blue-700',
    attente_fournisseur: 'bg-orange-100 text-orange-700',
    attente_client: 'bg-yellow-100 text-yellow-800',
    en_test: 'bg-violet-100 text-violet-700',
    livre: 'bg-green-100 text-green-700',
    ferme: 'bg-gray-200 text-gray-500',
};
const GRAVITE_STYLE = {
    bloquant: 'bg-red-100 text-red-700',
    majeur: 'bg-amber-100 text-amber-700',
    mineur: 'bg-gray-100 text-gray-600',
};

export default function Index({ tickets, filters = {}, statuts, types, stats = {} }) {
    const [q, setQ] = useState(filters.q || '');

    const applique = (params) => {
        router.get(route('tickets.index'), { ...filters, ...params }, { preserveState: true, replace: true });
    };
    const chercher = (e) => { e.preventDefault(); applique({ q }); };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Tickets</h2>}>
            <Head title="Tickets" />
            <div className="mx-auto max-w-6xl space-y-4 p-4 sm:p-6 lg:p-8">

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <form onSubmit={chercher} className="flex gap-2">
                        <input value={q} onChange={(e) => setQ(e.target.value)} placeholder="Référence, titre, client…"
                            className="w-64 rounded-md border-gray-300 text-sm" />
                        <button className="rounded-md bg-gray-800 px-3 py-2 text-sm font-medium text-white">Rechercher</button>
                    </form>
                    <Link href={route('tickets.create')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        + Nouveau ticket
                    </Link>
                </div>

                {/* Filtres par statut */}
                <div className="flex flex-wrap gap-2">
                    <button onClick={() => applique({ statut: undefined })}
                        className={`rounded-full px-3 py-1 text-xs ${!filters.statut ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 shadow'}`}>
                        Tous
                    </button>
                    {Object.entries(statuts).map(([k, label]) => (
                        <button key={k} onClick={() => applique({ statut: k })}
                            className={`rounded-full px-3 py-1 text-xs ${filters.statut === k ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 shadow'}`}>
                            {label} <span className="text-gray-400">{stats[k] || 0}</span>
                        </button>
                    ))}
                </div>

                <div className="overflow-hidden rounded-lg bg-white shadow">
                    {tickets.length === 0 ? (
                        <p className="p-6 text-sm text-gray-400">Aucun ticket.</p>
                    ) : (
                        <table className="min-w-full divide-y divide-gray-100 text-sm">
                            <thead className="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th className="px-4 py-2">Réf.</th>
                                    <th className="px-4 py-2">Titre</th>
                                    <th className="px-4 py-2">Client</th>
                                    <th className="px-4 py-2">Type</th>
                                    <th className="px-4 py-2">Gravité</th>
                                    <th className="px-4 py-2">Statut</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {tickets.map((t) => (
                                    <tr key={t.id} className="cursor-pointer hover:bg-gray-50"
                                        onClick={() => router.visit(t.url)}>
                                        <td className="whitespace-nowrap px-4 py-2 font-mono text-xs text-gray-500">{t.reference}</td>
                                        <td className="px-4 py-2 font-medium text-gray-800">{t.titre}</td>
                                        <td className="px-4 py-2 text-gray-600">{t.client || '—'}</td>
                                        <td className="px-4 py-2 text-gray-500">{types[t.type] || t.type}</td>
                                        <td className="px-4 py-2">
                                            <span className={`rounded px-1.5 py-0.5 text-xs font-medium ${GRAVITE_STYLE[t.gravite] || ''}`}>{t.gravite}</span>
                                        </td>
                                        <td className="px-4 py-2">
                                            <span className={`rounded-full px-2 py-0.5 text-xs ${STATUT_COLORS[t.statut] || ''}`}>{statuts[t.statut]}</span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
