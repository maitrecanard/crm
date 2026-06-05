import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const TACHE_COLORS = {
    a_faire: 'bg-gray-100 text-gray-600',
    en_cours: 'bg-amber-100 text-amber-700',
    fait: 'bg-green-100 text-green-700',
};
const NEXT = { a_faire: 'en_cours', en_cours: 'fait', fait: 'a_faire' };

export default function Show({ project, statuts, statutsTache, statutsBug, gravites, typesBug, recurrences }) {
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

                    <BugsCard project={project} statutsBug={statutsBug} gravites={gravites}
                        typesBug={typesBug} recurrences={recurrences} />
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

const GRAVITE_COLORS = {
    mineur: 'bg-gray-100 text-gray-600',
    majeur: 'bg-amber-100 text-amber-700',
    bloquant: 'bg-red-100 text-red-700',
};
const TYPE_COLORS = {
    bug: 'bg-rose-100 text-rose-700',
    maintenance: 'bg-sky-100 text-sky-700',
    evolution: 'bg-violet-100 text-violet-700',
};
const TYPE_ICONS = { bug: '🐛', maintenance: '🔧', evolution: '✨' };

// Suivi de production : interventions (bug / maintenance / évolution) + notif e-mail à chaque étape.
function BugsCard({ project, statutsBug, gravites, typesBug, recurrences }) {
    const bugs = project.bugs || [];
    const clientEmail = project.prospect?.email;
    const add = useForm({
        type: 'bug', titre: '', description: '', gravite: 'majeur',
        recurrence: 'mensuelle', prochaine_echeance: '', issue_git: '',
    });
    const submit = (e) => {
        e.preventDefault();
        add.post(route('bugs.store', project.id), { preserveScroll: true, onSuccess: () => add.reset() });
    };
    const isMaint = add.data.type === 'maintenance';

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold text-gray-800">🔧 Suivi de production</h3>
                <span className="text-xs text-gray-400">{bugs.length} ticket{bugs.length > 1 ? 's' : ''}</span>
            </div>

            {!clientEmail && (
                <p className="mb-3 rounded bg-amber-50 p-2 text-xs text-amber-800">
                    ⚠️ Ce client n’a pas d’adresse e-mail : les notifications ne pourront pas partir. Ajoute-la sur sa fiche.
                </p>
            )}

            <ul className="space-y-3">
                {bugs.map((b) => <BugRow key={b.id} bug={b} statutsBug={statutsBug} gravites={gravites} typesBug={typesBug} />)}
                {!bugs.length && <li className="text-sm text-gray-400">Aucune intervention.</li>}
            </ul>

            <form onSubmit={submit} className="mt-4 space-y-2 border-t border-gray-100 pt-4">
                <div className="flex gap-2">
                    <select value={add.data.type} onChange={(e) => add.setData('type', e.target.value)}
                        className="rounded-md border-gray-300 text-sm">
                        {Object.entries(typesBug).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                    </select>
                    <input value={add.data.titre} onChange={(e) => add.setData('titre', e.target.value)}
                        placeholder={isMaint ? 'Maintenance (ex. mises à jour de sécurité)…' : 'Sujet…'}
                        className="flex-1 rounded-md border-gray-300 text-sm" />
                    {!isMaint && (
                        <select value={add.data.gravite} onChange={(e) => add.setData('gravite', e.target.value)}
                            className="rounded-md border-gray-300 text-sm">
                            {Object.entries(gravites).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                        </select>
                    )}
                </div>
                {isMaint && (
                    <div className="flex gap-2">
                        <select value={add.data.recurrence} onChange={(e) => add.setData('recurrence', e.target.value)}
                            className="flex-1 rounded-md border-gray-300 text-sm">
                            {Object.entries(recurrences).map(([k, v]) => <option key={k} value={k}>Périodicité : {v}</option>)}
                        </select>
                        <input type="date" value={add.data.prochaine_echeance} title="Prochaine échéance"
                            onChange={(e) => add.setData('prochaine_echeance', e.target.value)}
                            className="rounded-md border-gray-300 text-sm" />
                    </div>
                )}
                <textarea rows="2" value={add.data.description} onChange={(e) => add.setData('description', e.target.value)}
                    placeholder="Description (reprise dans l’e-mail envoyé au client)…" className="w-full rounded-md border-gray-300 text-sm" />
                <input value={add.data.issue_git} onChange={(e) => add.setData('issue_git', e.target.value)}
                    placeholder="Issue Git (URL ou réf. — interne)…" className="w-full rounded-md border-gray-300 text-sm" />
                <div className="flex justify-end">
                    <button disabled={add.processing || !add.data.titre}
                        className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">
                        Enregistrer + notifier le client
                    </button>
                </div>
            </form>
        </div>
    );
}

function BugRow({ bug, statutsBug, gravites, typesBug }) {
    const [issue, setIssue] = useState(bug.issue_git || '');
    const setStatut = (statut) => router.put(route('bugs.update', bug.id), { statut },
        { preserveScroll: true, preserveState: true });
    const saveIssue = () => {
        if (issue !== (bug.issue_git || '')) {
            router.put(route('bugs.update', bug.id), { issue_git: issue }, { preserveScroll: true, preserveState: true });
        }
    };
    const remove = () => {
        if (confirm('Supprimer ce bug ?')) {
            router.delete(route('bugs.destroy', bug.id), { preserveScroll: true, preserveState: true });
        }
    };

    return (
        <li className="rounded-md border border-gray-200 p-3">
            <div className="flex items-center gap-2">
                <span className={`rounded px-2 py-0.5 text-xs font-medium ${TYPE_COLORS[bug.type] || 'bg-gray-100'}`}>
                    {TYPE_ICONS[bug.type]} {typesBug[bug.type] || bug.type}
                </span>
                {bug.type !== 'maintenance' && (
                    <span className={`rounded px-2 py-0.5 text-xs font-medium ${GRAVITE_COLORS[bug.gravite]}`}>{gravites[bug.gravite]}</span>
                )}
                <span className="flex-1 text-sm font-medium text-gray-800">{bug.titre}</span>
                <select value={bug.statut} onChange={(e) => setStatut(e.target.value)}
                    className="rounded-md border-gray-300 text-xs" title="Changer l’étape (notifie le client)">
                    {Object.entries(statutsBug).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                </select>
                <button onClick={remove} className="text-gray-300 hover:text-red-500" title="Supprimer">✕</button>
            </div>
            {bug.type === 'maintenance' && (bug.recurrence || bug.prochaine_echeance) && (
                <p className="mt-1 text-xs text-sky-700">
                    {bug.recurrence && `Périodicité : ${bug.recurrence}`}
                    {bug.prochaine_echeance && ` · prochaine échéance le ${new Date(bug.prochaine_echeance).toLocaleDateString('fr-FR')}`}
                </p>
            )}
            {bug.description && <p className="mt-2 whitespace-pre-line text-xs text-gray-600">{bug.description}</p>}
            <div className="mt-2 flex items-center gap-2">
                <span className="text-xs text-gray-400">Issue Git</span>
                <input value={issue} onChange={(e) => setIssue(e.target.value)} onBlur={saveIssue}
                    placeholder="URL ou réf." className="flex-1 rounded-md border-gray-200 text-xs" />
                {bug.issue_git && /^https?:\/\//.test(bug.issue_git) && (
                    <a href={bug.issue_git} target="_blank" rel="noreferrer" className="text-xs text-indigo-600 hover:underline">ouvrir ↗</a>
                )}
            </div>
            <p className="mt-1 text-[11px] text-gray-400">
                {bug.notifie_le ? `Client notifié le ${new Date(bug.notifie_le).toLocaleString('fr-FR')}` : 'Pas encore notifié'}
            </p>
        </li>
    );
}
