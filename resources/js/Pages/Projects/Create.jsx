import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ clients, statuts, preselect }) {
    const form = useForm({
        prospect_id: preselect || (clients[0]?.id ?? ''),
        titre: '', description: '', statut: 'cadrage', budget: '',
        date_debut: '', date_fin_prevue: '',
    });
    const submit = (e) => { e.preventDefault(); form.post(route('projects.store')); };

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('projects.index')} className="text-gray-400 hover:text-gray-600">← Projets</Link>
                <h2 className="text-xl font-semibold text-gray-800">Nouveau projet</h2>
            </div>
        }>
            <Head title="Nouveau projet" />
            <div className="mx-auto max-w-2xl p-4 sm:p-6 lg:p-8">
                {!clients.length ? (
                    <div className="rounded-lg bg-white p-6 text-sm text-gray-600 shadow">
                        Aucun client pour l’instant.{' '}
                        <Link href={route('clients.create')} className="text-indigo-600 hover:underline">Créer un client</Link> d’abord.
                    </div>
                ) : (
                    <form onSubmit={submit} className="space-y-4 rounded-lg bg-white p-6 shadow">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Client *</label>
                            <select value={form.data.prospect_id} onChange={(e) => form.setData('prospect_id', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                {clients.map((c) => <option key={c.id} value={c.id}>{c.entreprise}</option>)}
                            </select>
                            {form.errors.prospect_id && <p className="mt-1 text-xs text-red-600">{form.errors.prospect_id}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Intitulé du projet *</label>
                            <input value={form.data.titre} onChange={(e) => form.setData('titre', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            {form.errors.titre && <p className="mt-1 text-xs text-red-600">{form.errors.titre}</p>}
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Description</label>
                            <textarea rows="3" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Statut</label>
                                <select value={form.data.statut} onChange={(e) => form.setData('statut', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    {Object.entries(statuts).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Budget (€ HT)</label>
                                <input type="number" value={form.data.budget} onChange={(e) => form.setData('budget', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Début</label>
                                <input type="date" value={form.data.date_debut} onChange={(e) => form.setData('date_debut', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Fin prévue</label>
                                <input type="date" value={form.data.date_fin_prevue} onChange={(e) => form.setData('date_fin_prevue', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            </div>
                        </div>
                        <p className="text-xs text-gray-400">Un plan de tâches standard sera pré-rempli (modifiable ensuite).</p>
                        <div className="flex items-center justify-end gap-3">
                            <Link href={route('projects.index')} className="text-sm text-gray-500 hover:text-gray-700">Annuler</Link>
                            <button disabled={form.processing || !form.data.titre}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                Créer le projet
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
