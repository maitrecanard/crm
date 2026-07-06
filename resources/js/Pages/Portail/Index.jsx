import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const STATUT_COLORS = {
    proposee: 'bg-amber-100 text-amber-700',
    refusee: 'bg-red-100 text-red-700',
    a_faire: 'bg-gray-100 text-gray-700',
    en_cours: 'bg-blue-100 text-blue-700',
    fait: 'bg-green-100 text-green-700',
};

function fdate(d) {
    return d ? new Date(d).toLocaleDateString('fr-FR') : null;
}

/** Une tâche assignée au partenaire : accepter / refuser puis faire progresser. */
function TacheAssignee({ tache, statutsTache }) {
    const [refusOpen, setRefusOpen] = useState(false);
    const refus = useForm({ action: 'refuser', motif_refus: '' });

    const repondre = (action) => {
        router.post(route('portail.tasks.respond', tache.id), { action }, { preserveScroll: true });
    };
    const envoyerRefus = (e) => {
        e.preventDefault();
        refus.post(route('portail.tasks.respond', tache.id), {
            preserveScroll: true,
            onSuccess: () => { setRefusOpen(false); refus.reset(); },
        });
    };
    const progresser = (statut) => {
        router.post(route('portail.tasks.status', tache.id), { statut }, { preserveScroll: true });
    };

    return (
        <div className="rounded-md border border-gray-100 px-3 py-2">
            <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-gray-800">{tache.titre}</span>
                <span className={`rounded-full px-2 py-0.5 text-xs ${STATUT_COLORS[tache.statut]}`}>
                    {statutsTache[tache.statut]}
                </span>
            </div>
            {tache.description && <p className="mt-1 text-xs text-gray-500">{tache.description}</p>}
            <div className="mt-1 flex gap-3 text-xs text-gray-400">
                {tache.project && <span>📁 {tache.project.titre}</span>}
                {tache.echeance && <span>📅 {fdate(tache.echeance)}</span>}
            </div>

            {tache.statut === 'refusee' && tache.motif_refus && (
                <p className="mt-2 rounded bg-red-50 px-2 py-1 text-xs text-red-700">Refusée : {tache.motif_refus}</p>
            )}

            {/* À valider : accepter / refuser */}
            {tache.statut === 'proposee' && !refusOpen && (
                <div className="mt-2 flex gap-2">
                    <button onClick={() => repondre('accepter')}
                        className="rounded-md bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700">
                        Accepter
                    </button>
                    <button onClick={() => setRefusOpen(true)}
                        className="rounded-md border border-red-300 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-50">
                        Refuser
                    </button>
                </div>
            )}
            {tache.statut === 'proposee' && refusOpen && (
                <form onSubmit={envoyerRefus} className="mt-2 space-y-2">
                    <textarea rows="2" required autoFocus placeholder="Motif du refus (obligatoire)"
                        value={refus.data.motif_refus} onChange={(e) => refus.setData('motif_refus', e.target.value)}
                        className="w-full rounded-md border-gray-300 text-sm" />
                    {refus.errors.motif_refus && <p className="text-xs text-red-600">{refus.errors.motif_refus}</p>}
                    <div className="flex gap-2">
                        <button type="submit" disabled={refus.processing}
                            className="rounded-md bg-red-600 px-3 py-1 text-xs font-medium text-white disabled:opacity-50">
                            Confirmer le refus
                        </button>
                        <button type="button" onClick={() => setRefusOpen(false)}
                            className="rounded-md border border-gray-300 px-3 py-1 text-xs text-gray-600">
                            Annuler
                        </button>
                    </div>
                </form>
            )}

            {/* Acceptée : progression */}
            {tache.statut === 'a_faire' && (
                <button onClick={() => progresser('en_cours')}
                    className="mt-2 rounded-md bg-blue-600 px-3 py-1 text-xs font-medium text-white hover:bg-blue-700">
                    Commencer
                </button>
            )}
            {tache.statut === 'en_cours' && (
                <button onClick={() => progresser('fait')}
                    className="mt-2 rounded-md bg-green-600 px-3 py-1 text-xs font-medium text-white hover:bg-green-700">
                    Marquer terminée
                </button>
            )}
        </div>
    );
}

export default function Index({ partenaire, projects, taches, statutsTache }) {
    const form = useForm({ project_id: '', titre: '', description: '', echeance: '' });
    const submit = (e) => {
        e.preventDefault();
        form.post(route('portail.tasks.store'), { onSuccess: () => form.reset('titre', 'description', 'echeance') });
    };

    const assignees = taches.filter((t) => t.source === 'assignee');
    const transmises = taches.filter((t) => t.source !== 'assignee');
    const aValider = assignees.filter((t) => t.statut === 'proposee').length;

    return (
        <AuthenticatedLayout header={
            <h2 className="text-xl font-semibold text-gray-800">Espace partenaire — {partenaire.nom}</h2>
        }>
            <Head title="Mon espace partenaire" />
            <div className="mx-auto max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">

                {/* Tâches assignées (à valider en priorité) */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-3 flex items-center gap-2 font-semibold text-gray-800">
                        Tâches qui vous sont assignées
                        {aValider > 0 && (
                            <span className="rounded-full bg-amber-500 px-2 py-0.5 text-xs text-white">{aValider} à valider</span>
                        )}
                    </h3>
                    <div className="space-y-2">
                        {assignees.length ? assignees.map((t) => (
                            <TacheAssignee key={t.id} tache={t} statutsTache={statutsTache} />
                        )) : <p className="text-sm text-gray-400">Aucune tâche ne vous est assignée pour l’instant.</p>}
                    </div>
                </div>

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

                {/* Tâches transmises */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-3 font-semibold text-gray-800">Tâches transmises</h3>
                    <div className="space-y-2">
                        {transmises.length ? transmises.map((t) => (
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
                                    {t.echeance && <span>📅 {fdate(t.echeance)}</span>}
                                </div>
                            </div>
                        )) : <p className="text-sm text-gray-400">Vous n’avez transmis aucune tâche.</p>}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
