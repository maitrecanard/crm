import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

const STATUT_COLORS = {
    a_faire: 'bg-gray-100 text-gray-700',
    en_cours: 'bg-blue-100 text-blue-700',
    fait: 'bg-green-100 text-green-700',
};

export default function Index({ partenaire, projects, taches, statutsTache }) {
    const form = useForm({ project_id: '', titre: '', description: '', echeance: '' });
    const submit = (e) => {
        e.preventDefault();
        form.post(route('portail.tasks.store'), { onSuccess: () => form.reset('titre', 'description', 'echeance') });
    };

    return (
        <AuthenticatedLayout header={
            <h2 className="text-xl font-semibold text-gray-800">Espace partenaire — {partenaire.nom}</h2>
        }>
            <Head title="Mon espace partenaire" />
            <div className="mx-auto max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">

                {/* Transmettre une tâche */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-3 font-semibold text-gray-800">Transmettre une tâche</h3>
                    {projects.length ? (
                        <form onSubmit={submit} className="space-y-3">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Projet *</label>
                                <select value={form.data.project_id} onChange={(e) => form.setData('project_id', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Choisir un projet…</option>
                                    {projects.map((p) => <option key={p.id} value={p.id}>{p.titre}</option>)}
                                </select>
                                {form.errors.project_id && <p className="text-xs text-red-600">{form.errors.project_id}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Tâche à réaliser *</label>
                                <input value={form.data.titre} onChange={(e) => form.setData('titre', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                                {form.errors.titre && <p className="text-xs text-red-600">{form.errors.titre}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Détails</label>
                                <textarea rows="3" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Échéance souhaitée</label>
                                <input type="date" value={form.data.echeance} onChange={(e) => form.setData('echeance', e.target.value)}
                                    className="mt-1 rounded-md border-gray-300 text-sm" />
                            </div>
                            <button disabled={form.processing || !form.data.project_id || !form.data.titre}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                Transmettre
                            </button>
                        </form>
                    ) : (
                        <p className="text-sm text-gray-400">Aucun projet ne vous est rattaché pour l’instant.</p>
                    )}
                </div>

                {/* Tâches déjà transmises */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-3 font-semibold text-gray-800">Tâches transmises</h3>
                    <div className="space-y-2">
                        {taches.length ? taches.map((t) => (
                            <div key={t.id} className="rounded-md border border-gray-100 px-3 py-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium text-gray-800">{t.titre}</span>
                                    <span className={`rounded-full px-2 py-0.5 text-xs ${STATUT_COLORS[t.statut]}`}>
                                        {statutsTache[t.statut]}
                                    </span>
                                </div>
                                {t.description && <p className="mt-1 text-xs text-gray-500">{t.description}</p>}
                                <div className="mt-1 flex gap-3 text-xs text-gray-400">
                                    {t.project && <span>📁 {t.project.titre}</span>}
                                    {t.echeance && <span>📅 {new Date(t.echeance).toLocaleDateString('fr-FR')}</span>}
                                </div>
                            </div>
                        )) : <p className="text-sm text-gray-400">Vous n’avez transmis aucune tâche.</p>}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
