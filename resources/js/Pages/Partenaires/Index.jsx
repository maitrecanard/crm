import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ partenaires, filters, total }) {
    const search = (q) =>
        router.get(route('partenaires.index'), { q: q || undefined }, { preserveState: true, replace: true });

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-800">Partenaires</h2>
                <Link href={route('partenaires.create')}
                    className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                    + Nouveau partenaire
                </Link>
            </div>
        }>
            <Head title="Partenaires" />
            <div className="mx-auto max-w-5xl space-y-4 p-4 sm:p-6 lg:p-8">
                <div className="flex items-center justify-between">
                    <input defaultValue={filters.q || ''} placeholder="Rechercher un partenaire…"
                        onKeyDown={(e) => e.key === 'Enter' && search(e.target.value)}
                        className="w-64 rounded-md border-gray-300 text-sm" />
                    <span className="text-sm text-gray-500">{total} partenaire{total > 1 ? 's' : ''}</span>
                </div>

                <div className="space-y-3">
                    {partenaires.data.map((p) => (
                        <div key={p.id} className="rounded-lg bg-white p-4 shadow">
                            <div className="flex items-center justify-between">
                                <Link href={route('partenaires.show', p.id)} className="font-medium text-indigo-600 hover:underline">
                                    {p.nom}
                                </Link>
                                <span className="flex items-center gap-2 text-xs">
                                    {p.user?.email_verified_at
                                        ? <span className="rounded-full bg-green-100 px-2 py-0.5 text-green-700">Compte actif</span>
                                        : <span className="rounded-full bg-amber-100 px-2 py-0.5 text-amber-700">Invitation en attente</span>}
                                    {!p.actif && <span className="rounded-full bg-gray-200 px-2 py-0.5 text-gray-600">Inactif</span>}
                                </span>
                            </div>
                            <div className="mt-1 text-xs text-gray-400">{p.email}</div>
                            <div className="mt-2 flex gap-4 text-xs text-gray-500">
                                <span>📁 {p.projects_count} projet{p.projects_count > 1 ? 's' : ''}</span>
                                <span>✅ {p.tasks_count} tâche{p.tasks_count > 1 ? 's' : ''} transmise{p.tasks_count > 1 ? 's' : ''}</span>
                            </div>
                        </div>
                    ))}
                    {!partenaires.data.length && (
                        <div className="rounded-lg bg-white p-10 text-center text-gray-400 shadow">
                            Aucun partenaire pour l’instant.
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
