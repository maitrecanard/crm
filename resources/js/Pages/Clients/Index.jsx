import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ clients, filters, total }) {
    const search = (q) => router.get(route('clients.index'), { q: q || undefined },
        { preserveState: true, replace: true });

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between">
                <h2 className="text-xl font-semibold text-gray-800">Clients</h2>
                <Link href={route('clients.create')}
                    className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                    + Nouveau client
                </Link>
            </div>
        }>
            <Head title="Clients" />
            <div className="mx-auto max-w-5xl space-y-4 p-4 sm:p-6 lg:p-8">
                <div className="flex items-center justify-between">
                    <input defaultValue={filters.q || ''} placeholder="Rechercher un client…"
                        onKeyDown={(e) => e.key === 'Enter' && search(e.target.value)}
                        className="w-64 rounded-md border-gray-300 text-sm" />
                    <span className="text-sm text-gray-500">{total} client{total > 1 ? 's' : ''}</span>
                </div>

                <div className="space-y-3">
                    {clients.data.map((c) => (
                        <div key={c.id} className="rounded-lg bg-white p-4 shadow">
                            <div className="flex items-center justify-between">
                                <Link href={route('prospects.show', c.id)} className="font-medium text-indigo-600 hover:underline">
                                    {c.entreprise}
                                </Link>
                                <span className="text-xs text-gray-400">
                                    {c.localite} {c.client_depuis && `· client depuis le ${new Date(c.client_depuis).toLocaleDateString('fr-FR')}`}
                                </span>
                            </div>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {c.projects?.length ? c.projects.map((p) => (
                                    <Link key={p.id} href={route('projects.show', p.id)}
                                        className="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200">
                                        📁 {p.titre}
                                    </Link>
                                )) : <span className="text-xs text-gray-400">Aucun projet</span>}
                            </div>
                        </div>
                    ))}
                    {!clients.data.length && (
                        <div className="rounded-lg bg-white p-10 text-center text-gray-400 shadow">
                            Aucun client pour l’instant. Passe un prospect en « Gagné » pour le convertir.
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
