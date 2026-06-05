import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const TACHE_COLORS = {
    a_faire: 'bg-gray-100 text-gray-600',
    en_cours: 'bg-amber-100 text-amber-700',
    fait: 'bg-green-100 text-green-700',
};
const NEXT = { a_faire: 'en_cours', en_cours: 'fait', fait: 'a_faire' };

export default function Show({ project, statuts, statutsTache }) {
    const form = useForm({
        titre: project.titre,
        description: project.description || '',
        statut: project.statut,
        budget: project.budget || '',
        date_debut: project.date_debut ? project.date_debut.substring(0, 10) : '',
        date_fin_prevue: project.date_fin_prevue ? project.date_fin_prevue.substring(0, 10) : '',
        date_livraison: project.date_livraison ? project.date_livraison.substring(0, 10) : '',
        notes: project.notes || '',
    });
    const save = (e) => { e.preventDefault(); form.put(route('projects.update', project.id), { preserveScroll: true }); };

    const tasks = project.tasks || [];
    const faites = tasks.filter((t) => t.statut === 'fait').length;
    const avancement = tasks.length ? Math.round((faites / tasks.length) * 100) : 0;

    const newTask = useForm({ titre: '', echeance: '' });
    const addTask = (e) => {
        e.preventDefault();
        newTask.post(route('tasks.store', project.id), {
            preserveScroll: true, onSuccess: () => newTask.reset(),
        });
    };
    const cycle = (t) => router.put(route('tasks.update', t.id), { statut: NEXT[t.statut] },
        { preserveScroll: true, preserveState: true });
    const removeTask = (t) => {
        if (confirm('Supprimer cette tâche ?')) {
            router.delete(route('tasks.destroy', t.id), { preserveScroll: true, preserveState: true });
        }
    };

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('projects.index')} className="text-gray-400 hover:text-gray-600">← Projets</Link>
                <h2 className="truncate text-xl font-semibold text-gray-800">{project.titre}</h2>
            </div>
        }>
            <Head title={project.titre} />

            <div className="mx-auto grid max-w-5xl gap-6 p-4 sm:p-6 lg:p-8 md:grid-cols-3">
                {/* Colonne gauche : gestion des tâches */}
                <div className="space-y-6 md:col-span-2">
                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-2 flex items-center justify-between">
                            <h3 className="font-semibold text-gray-800">Plan de gestion</h3>
                            <span className="text-sm text-gray-500">{faites}/{tasks.length} — {avancement}%</span>
                        </div>
                        <div className="mb-4 h-2 overflow-hidden rounded-full bg-gray-100">
                            <div className="h-full bg-emerald-500" style={{ width: `${avancement}%` }} />
                        </div>

                        <ul className="divide-y divide-gray-100">
                            {tasks.map((t) => (
                                <li key={t.id} className="flex items-center gap-3 py-2">
                                    <button onClick={() => cycle(t)} title="Changer le statut"
                                        className={`rounded-full px-2 py-0.5 text-xs font-medium ${TACHE_COLORS[t.statut]}`}>
                                        {statutsTache[t.statut]}
                                    </button>
                                    <span className={`flex-1 text-sm ${t.statut === 'fait' ? 'text-gray-400 line-through' : 'text-gray-800'}`}>
                                        {t.titre}
                                    </span>
                                    {t.echeance && (
                                        <span className="text-xs text-gray-400">
                                            {new Date(t.echeance).toLocaleDateString('fr-FR')}
                                        </span>
                                    )}
                                    <button onClick={() => removeTask(t)} className="text-gray-300 hover:text-red-500" title="Supprimer">✕</button>
                                </li>
                            ))}
                            {!tasks.length && <li className="py-3 text-sm text-gray-400">Aucune tâche.</li>}
                        </ul>

                        <form onSubmit={addTask} className="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4">
                            <input value={newTask.data.titre} onChange={(e) => newTask.setData('titre', e.target.value)}
                                placeholder="Nouvelle tâche…" className="flex-1 rounded-md border-gray-300 text-sm" />
                            <input type="date" value={newTask.data.echeance} onChange={(e) => newTask.setData('echeance', e.target.value)}
                                className="rounded-md border-gray-300 text-sm" />
                            <button disabled={newTask.processing || !newTask.data.titre}
                                className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">
                                + Ajouter
                            </button>
                        </form>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 font-semibold text-gray-800">Description</h3>
                        <textarea rows="4" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)}
                            className="w-full rounded-md border-gray-300 text-sm" />
                        <h3 className="mb-2 mt-4 font-semibold text-gray-800">Notes internes</h3>
                        <textarea rows="5" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)}
                            className="w-full rounded-md border-gray-300 text-sm" />
                    </div>
                </div>

                {/* Colonne droite : pilotage */}
                <div>
                    <form onSubmit={save} className="space-y-4 rounded-lg bg-white p-6 shadow">
                        <h3 className="font-semibold text-gray-800">Pilotage</h3>

                        {project.prospect && (
                            <div className="text-sm">
                                <span className="text-xs uppercase text-gray-400">Client</span><br />
                                <Link href={route('prospects.show', project.prospect.id)} className="text-indigo-600 hover:underline">
                                    {project.prospect.entreprise} →
                                </Link>
                            </div>
                        )}
                        {project.tender && (
                            <div className="text-sm">
                                <span className="text-xs uppercase text-gray-400">Issu de l’AO</span><br />
                                <Link href={route('ao.show', project.tender.id)} className="text-indigo-600 hover:underline">
                                    {project.tender.objet} →
                                </Link>
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-gray-700">Intitulé</label>
                            <input value={form.data.titre} onChange={(e) => form.setData('titre', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
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
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Livraison</label>
                            <input type="date" value={form.data.date_livraison} onChange={(e) => form.setData('date_livraison', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <button disabled={form.processing}
                            className="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            {form.processing ? 'Enregistrement…' : 'Enregistrer'}
                        </button>
                        {form.recentlySuccessful && <p className="text-sm text-green-600">Enregistré ✓</p>}
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
