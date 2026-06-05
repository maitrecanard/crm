import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';



const STATUT_COLORS = {
    cadrage: 'bg-sky-100 text-sky-700',
    en_cours: 'bg-amber-100 text-amber-700',
    recette: 'bg-violet-100 text-violet-700',
    livre: 'bg-green-100 text-green-700',
    cloture: 'bg-gray-100 text-gray-500',
    suspendu: 'bg-red-100 text-red-700',
};

export default function Index({ projects, filters, statuts, stats }) {
    const apply = (patch) => router.get(route('projects.index'), { ...filters, ...patch },
        { preserveState: true, replace: true });

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-800">Projets</h2>
                <Link href={route('projects.create')}
                    className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                    + Nouveau projet
                </Link>
            </div>
        }>
            <Head title="Projets" />
            <div className="mx-auto max-w-6xl space-y-4 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-wrap items-center gap-2">
                    <input defaultValue={filters.q || ''} placeholder="Rechercher un projet / client…"
                        onKeyDown={(e) => e.key === 'Enter' && apply({ q: e.target.value })}
                        className="w-64 rounded-md border-gray-300 text-sm" />
                    <select value={filters.statut || ''} onChange={(e) => apply({ statut: e.target.value || undefined })}
                        className="rounded-md border-gray-300 text-sm">
                        <option value="">Tous les statuts</option>
                        {Object.entries(statuts).map(([k, v]) =>
                            <option key={k} value={k}>{v} ({stats[k] || 0})</option>)}
                    </select>
                </div>

                <div className="overflow-hidden rounded-lg bg-white shadow">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th className="px-4 py-3 text-left">Projet</th>
                                <th className="px-4 py-3 text-left">Client</th>
                                <th className="px-4 py-3 text-left">Statut</th>
                                <th className="px-4 py-3 text-right">Budget</th>
                                <th className="px-4 py-3 text-left">Avancement</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {projects.data.map((p) => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <Link href={route('projects.show', p.id)} className="font-medium text-indigo-600 hover:underline">
                                            {p.titre}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-gray-700">{p.prospect?.entreprise || '—'}</td>
                                    <td className="px-4 py-3">
                                        <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${STATUT_COLORS[p.statut] || 'bg-gray-100'}`}>
                                            {statuts[p.statut] || p.statut}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right text-gray-700">
                                        {p.budget ? `${p.budget.toLocaleString('fr-FR')} €` : '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <div className="h-2 w-24 overflow-hidden rounded-full bg-gray-100">
                                                <div className="h-full bg-emerald-500" style={{ width: `${p.avancement}%` }} />
                                            </div>
                                            <span className="text-xs text-gray-500">{p.avancement}%</span>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {!projects.data.length && (
                                <tr><td colSpan="5" className="px-4 py-10 text-center text-gray-400">
                                    Aucun projet. Un projet est créé automatiquement quand un prospect passe en « Gagné ».
                                </td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
