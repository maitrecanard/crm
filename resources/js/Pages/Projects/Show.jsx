import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const TACHE_COLORS = {
    a_faire: 'bg-gray-100 text-gray-600',
    en_cours: 'bg-amber-100 text-amber-700',
    fait: 'bg-green-100 text-green-700',
};
const NEXT = { a_faire: 'en_cours', en_cours: 'fait', fait: 'a_faire' };

const PROJET_STATUT_COLORS = {
    cadrage: 'bg-sky-100 text-sky-700',
    en_cours: 'bg-amber-100 text-amber-700',
    recette: 'bg-violet-100 text-violet-700',
    livre: 'bg-green-100 text-green-700',
    cloture: 'bg-gray-100 text-gray-500',
    suspendu: 'bg-red-100 text-red-700',
};

export default function Show({ project, statuts, statutsTache, statutsBug, gravites, typesBug, recurrences }) {
    const [tab, setTab] = useState('overview');

    const form = useForm({
        titre: project.titre,
        description: project.description || '',
        url_prod: project.url_prod || '',
        url_preprod: project.url_preprod || '',
        repo_git: project.repo_git || '',
        hebergeur: project.hebergeur || '',
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
    const tCount = { a_faire: 0, en_cours: 0, fait: 0 };
    tasks.forEach((t) => { tCount[t.statut] = (tCount[t.statut] || 0) + 1; });

    const bugs = project.bugs || [];
    const bugsOuverts = bugs.filter((b) => !['livre', 'ferme'].includes(b.statut));
    const byType = {};
    bugs.forEach((b) => { byType[b.type] = (byType[b.type] || 0) + 1; });

    const newTask = useForm({ titre: '', echeance: '' });
    const addTask = (e) => {
        e.preventDefault();
        newTask.post(route('tasks.store', project.id), { preserveScroll: true, onSuccess: () => newTask.reset() });
    };
    const cycle = (t) => router.put(route('tasks.update', t.id), { statut: NEXT[t.statut] },
        { preserveScroll: true, preserveState: true });
    const removeTask = (t) => {
        if (confirm('Supprimer cette tâche ?')) {
            router.delete(route('tasks.destroy', t.id), { preserveScroll: true, preserveState: true });
        }
    };

    const TABS = [
        { id: 'overview', label: "Vue d'ensemble" },
        { id: 'pilotage', label: 'Pilotage' },
        { id: 'taches', label: 'Tâches', badge: tasks.length },
        { id: 'env', label: 'Environnements' },
        { id: 'prod', label: 'Production', badge: bugsOuverts.length },
    ];

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('projects.index')} className="text-gray-400 hover:text-gray-600">← Projets</Link>
                <h2 className="truncate text-xl font-semibold text-gray-800">{project.titre}</h2>
                <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${PROJET_STATUT_COLORS[project.statut] || 'bg-gray-100'}`}>
                    {statuts[project.statut]}
                </span>
            </div>
        }>
            <Head title={project.titre} />

            <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                {/* Onglets */}
                <div className="flex gap-1 overflow-x-auto border-b border-gray-200">
                    {TABS.map((t) => (
                        <button key={t.id} onClick={() => setTab(t.id)}
                            className={`-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium ${
                                tab === t.id ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'}`}>
                            {t.label}
                            {t.badge != null && (
                                <span className="ml-1.5 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">{t.badge}</span>
                            )}
                        </button>
                    ))}
                </div>
            </div>

            <div className="mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">
                {/* ---------- VUE D'ENSEMBLE ---------- */}
                {tab === 'overview' && (
                    <div className="space-y-6">
                        <div className="grid gap-4 sm:grid-cols-4">
                            <KpiCard label="Avancement">
                                <Donut value={avancement} />
                            </KpiCard>
                            <KpiCard label="Budget">
                                <p className="text-2xl font-bold text-gray-800">
                                    {project.budget ? `${project.budget.toLocaleString('fr-FR')} €` : '—'}
                                </p>
                                <p className="text-xs text-gray-400">HT</p>
                            </KpiCard>
                            <KpiCard label="Tâches">
                                <p className="text-2xl font-bold text-gray-800">{faites}<span className="text-base text-gray-400">/{tasks.length}</span></p>
                                <p className="text-xs text-gray-400">terminées</p>
                            </KpiCard>
                            <KpiCard label="Tickets ouverts">
                                <p className={`text-2xl font-bold ${bugsOuverts.length ? 'text-rose-600' : 'text-gray-800'}`}>{bugsOuverts.length}</p>
                                <p className="text-xs text-gray-400">/ {bugs.length} au total</p>
                            </KpiCard>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-3 font-semibold text-gray-800">Répartition des tâches</h3>
                                <SegBar segments={[
                                    { n: tCount.fait, label: 'Fait', color: 'bg-green-500' },
                                    { n: tCount.en_cours, label: 'En cours', color: 'bg-amber-500' },
                                    { n: tCount.a_faire, label: 'À faire', color: 'bg-gray-300' },
                                ]} />
                            </div>
                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-3 font-semibold text-gray-800">Production</h3>
                                <SegBar segments={[
                                    { n: bugs.length - bugsOuverts.length, label: 'Résolus', color: 'bg-green-500' },
                                    { n: bugsOuverts.length, label: 'Ouverts', color: 'bg-rose-500' },
                                ]} />
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {Object.entries(byType).map(([k, n]) => (
                                        <span key={k} className={`rounded px-2 py-0.5 text-xs font-medium ${TYPE_COLORS[k] || 'bg-gray-100'}`}>
                                            {TYPE_ICONS[k]} {typesBug[k] || k} · {n}
                                        </span>
                                    ))}
                                    {!bugs.length && <span className="text-sm text-gray-400">Aucune intervention.</span>}
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-3 font-semibold text-gray-800">Jalons</h3>
                                <ul className="space-y-2 text-sm">
                                    <Jalon label="Début" date={project.date_debut} />
                                    <Jalon label="Fin prévue" date={project.date_fin_prevue} />
                                    <Jalon label="Livraison" date={project.date_livraison} />
                                </ul>
                            </div>
                            <div className="rounded-lg bg-white p-6 shadow">
                                <h3 className="mb-3 font-semibold text-gray-800">Rattachements</h3>
                                <div className="space-y-2 text-sm">
                                    {project.prospect ? (
                                        <div>
                                            <span className="text-xs uppercase text-gray-400">Client</span><br />
                                            <Link href={route('prospects.show', project.prospect.id)} className="text-indigo-600 hover:underline">
                                                {project.prospect.entreprise} →
                                            </Link>
                                        </div>
                                    ) : <p className="text-gray-400">Aucun client rattaché.</p>}
                                    {project.tender && (
                                        <div>
                                            <span className="text-xs uppercase text-gray-400">Issu de l'AO</span><br />
                                            <Link href={route('ao.show', project.tender.id)} className="text-indigo-600 hover:underline">
                                                {project.tender.objet} →
                                            </Link>
                                        </div>
                                    )}
                                    {project.url_prod && (
                                        <a href={project.url_prod} target="_blank" rel="noreferrer"
                                            className="inline-block rounded bg-gray-100 px-2 py-1 text-xs text-indigo-600 hover:bg-gray-200">
                                            🌐 Voir le site en ligne ↗
                                        </a>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* ---------- PILOTAGE ---------- */}
                {tab === 'pilotage' && (
                    <form onSubmit={save} className="mx-auto max-w-2xl space-y-4 rounded-lg bg-white p-6 shadow">
                        <h3 className="font-semibold text-gray-800">Pilotage</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Intitulé</label>
                            <input value={form.data.titre} onChange={(e) => form.setData('titre', e.target.value)}
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
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Livraison</label>
                                <input type="date" value={form.data.date_livraison} onChange={(e) => form.setData('date_livraison', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Description</label>
                            <textarea rows="4" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Notes internes</label>
                            <textarea rows="5" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <button disabled={form.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            {form.processing ? 'Enregistrement…' : 'Enregistrer'}
                        </button>
                        {form.recentlySuccessful && <span className="ml-3 text-sm text-green-600">Enregistré ✓</span>}
                    </form>
                )}

                {/* ---------- TÂCHES ---------- */}
                {tab === 'taches' && (
                    <div className="mx-auto max-w-2xl rounded-lg bg-white p-6 shadow">
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
                                    {t.echeance && <span className="text-xs text-gray-400">{new Date(t.echeance).toLocaleDateString('fr-FR')}</span>}
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
                                className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50">+ Ajouter</button>
                        </form>
                    </div>
                )}

                {/* ---------- ENVIRONNEMENTS ---------- */}
                {tab === 'env' && (
                    <div className="mx-auto max-w-2xl rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 font-semibold text-gray-800">🌐 Environnements & accès</h3>
                        <div className="space-y-3">
                            <EnvField label="Site en ligne (production)" value={form.data.url_prod}
                                onChange={(v) => form.setData('url_prod', v)} link />
                            <EnvField label="Préproduction / staging" value={form.data.url_preprod}
                                onChange={(v) => form.setData('url_preprod', v)} link />
                            <EnvField label="Dépôt Git" value={form.data.repo_git}
                                onChange={(v) => form.setData('repo_git', v)} link />
                            <EnvField label="Hébergeur" value={form.data.hebergeur}
                                onChange={(v) => form.setData('hebergeur', v)} />
                        </div>
                        <button onClick={save} disabled={form.processing}
                            className="mt-4 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            {form.processing ? 'Enregistrement…' : 'Enregistrer'}
                        </button>
                    </div>
                )}

                {/* ---------- PRODUCTION ---------- */}
                {tab === 'prod' && (
                    <BugsCard project={project} statutsBug={statutsBug} gravites={gravites}
                        typesBug={typesBug} recurrences={recurrences} />
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function KpiCard({ label, children }) {
    return (
        <div className="rounded-lg bg-white p-4 shadow">
            <div className="text-xs uppercase text-gray-400">{label}</div>
            <div className="mt-1">{children}</div>
        </div>
    );
}

function Donut({ value }) {
    const r = 32, c = 2 * Math.PI * r;
    const off = c * (1 - Math.min(value, 100) / 100);
    return (
        <svg width="80" height="80" viewBox="0 0 80 80">
            <circle cx="40" cy="40" r={r} fill="none" stroke="#e5e7eb" strokeWidth="9" />
            <circle cx="40" cy="40" r={r} fill="none" stroke="#10b981" strokeWidth="9"
                strokeDasharray={c} strokeDashoffset={off} strokeLinecap="round" transform="rotate(-90 40 40)" />
            <text x="40" y="45" textAnchor="middle" className="fill-gray-800 text-base font-bold">{value}%</text>
        </svg>
    );
}

function SegBar({ segments }) {
    const total = segments.reduce((s, x) => s + x.n, 0) || 1;
    return (
        <div>
            <div className="flex h-3 overflow-hidden rounded-full bg-gray-100">
                {segments.map((s, i) => s.n > 0 && (
                    <div key={i} className={s.color} style={{ width: `${(s.n / total) * 100}%` }} />
                ))}
            </div>
            <div className="mt-2 flex flex-wrap gap-3 text-xs text-gray-600">
                {segments.map((s, i) => (
                    <span key={i} className="flex items-center gap-1">
                        <span className={`inline-block h-2 w-2 rounded-full ${s.color}`} />{s.label} · <b>{s.n}</b>
                    </span>
                ))}
            </div>
        </div>
    );
}

function Jalon({ label, date }) {
    return (
        <li className="flex items-center justify-between">
            <span className="text-gray-500">{label}</span>
            <span className={date ? 'font-medium text-gray-800' : 'text-gray-300'}>
                {date ? new Date(date).toLocaleDateString('fr-FR') : '— non défini'}
            </span>
        </li>
    );
}

function EnvField({ label, value, onChange, link = false }) {
    const isUrl = link && value && /^https?:\/\//i.test(value);
    return (
        <div>
            <label className="block text-xs font-medium uppercase text-gray-400">{label}</label>
            <div className="mt-1 flex items-center gap-2">
                <input value={value} onChange={(e) => onChange(e.target.value)}
                    placeholder={link ? 'https://…' : ''} className="flex-1 rounded-md border-gray-300 text-sm" />
                {isUrl && (
                    <a href={value} target="_blank" rel="noreferrer"
                        className="whitespace-nowrap text-xs text-indigo-600 hover:underline">ouvrir ↗</a>
                )}
            </div>
        </div>
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
    // Les tickets résolus (livré) / clôturés sortent de la liste « en cours »
    // et sont repliés dans une section dédiée — toujours consultables.
    const enCours = bugs.filter((b) => !['livre', 'ferme'].includes(b.statut));
    const resolus = bugs.filter((b) => ['livre', 'ferme'].includes(b.statut));
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
                <span className="text-xs text-gray-400">
                    {enCours.length} en cours{resolus.length ? ` · ${resolus.length} résolu${resolus.length > 1 ? 's' : ''}` : ''}
                </span>
            </div>

            {!clientEmail && (
                <p className="mb-3 rounded bg-amber-50 p-2 text-xs text-amber-800">
                    ⚠️ Ce client n’a pas d’adresse e-mail : les notifications ne pourront pas partir. Ajoute-la sur sa fiche.
                </p>
            )}

            <ul className="space-y-3">
                {enCours.map((b) => <BugRow key={b.id} bug={b} statutsBug={statutsBug} gravites={gravites} typesBug={typesBug} />)}
                {!enCours.length && (
                    <li className="text-sm text-gray-400">
                        {bugs.length ? 'Aucun ticket en cours 🎉' : 'Aucune intervention.'}
                    </li>
                )}
            </ul>

            {resolus.length > 0 && (
                <details className="mt-3 border-t border-gray-100 pt-3">
                    <summary className="cursor-pointer text-sm text-gray-500 hover:text-gray-700">
                        Tickets résolus / clôturés ({resolus.length})
                    </summary>
                    <ul className="mt-3 space-y-3 opacity-75">
                        {resolus.map((b) => <BugRow key={b.id} bug={b} statutsBug={statutsBug} gravites={gravites} typesBug={typesBug} />)}
                    </ul>
                </details>
            )}

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
    const messages = bug.messages || [];
    const comment = useForm({ corps: '', interne: false });
    const addComment = (e) => {
        e.preventDefault();
        comment.post(route('bugs.messages.store', bug.id), {
            preserveScroll: true, preserveState: true, onSuccess: () => comment.reset(),
        });
    };
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
                {bug.source === 'client_site' && (
                    <span className="rounded bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-700" title="Déclaré par le client depuis son site">
                        🛟 Site client
                    </span>
                )}
                <span className="flex-1 text-sm font-medium text-gray-800">
                    {bug.reference && <span className="mr-1 font-mono text-xs text-gray-400">{bug.reference}</span>}
                    {bug.titre}
                </span>
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
            {bug.images?.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-2">
                    {bug.images.map((img) => (
                        <a key={img.id} href={route('bugs.images.show', img.id)} target="_blank" rel="noreferrer"
                            className="block">
                            <img src={route('bugs.images.show', img.id)} alt={img.nom || 'incident'}
                                className="h-16 w-16 rounded-md border border-gray-200 object-cover hover:opacity-80" />
                        </a>
                    ))}
                </div>
            )}
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

            {/* Fil de commentaires / résolution */}
            {messages.length > 0 && (
                <ul className="mt-3 space-y-2 border-t border-gray-100 pt-3">
                    {messages.map((m) => (
                        <li key={m.id} className={`rounded-md p-2 text-xs ${m.interne ? 'bg-yellow-50' : 'bg-indigo-50'}`}>
                            <div className="mb-0.5 flex items-center gap-2 text-[11px]">
                                {m.interne
                                    ? <span className="rounded bg-yellow-200 px-1.5 font-medium text-yellow-800">Note interne</span>
                                    : <span className="rounded bg-indigo-200 px-1.5 font-medium text-indigo-800">Transmis au client</span>}
                                <span className="text-gray-400">{new Date(m.created_at).toLocaleString('fr-FR')}</span>
                            </div>
                            <p className="whitespace-pre-line text-gray-700">{m.corps}</p>
                        </li>
                    ))}
                </ul>
            )}

            <form onSubmit={addComment} className="mt-2 space-y-2">
                <textarea rows="2" value={comment.data.corps} onChange={(e) => comment.setData('corps', e.target.value)}
                    placeholder="Commentaire, avancement ou résolution…" className="w-full rounded-md border-gray-200 text-xs" />
                <div className="flex items-center justify-between">
                    <label className="flex items-center gap-1.5 text-xs text-gray-600">
                        <input type="checkbox" checked={comment.data.interne}
                            onChange={(e) => comment.setData('interne', e.target.checked)}
                            className="rounded border-gray-300 text-indigo-600" />
                        Ne pas transmettre au client (note interne)
                    </label>
                    <button disabled={comment.processing || !comment.data.corps}
                        className={`rounded-md px-3 py-1.5 text-xs font-medium text-white disabled:opacity-50 ${
                            comment.data.interne ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-indigo-600 hover:bg-indigo-700'}`}>
                        {comment.data.interne ? 'Ajouter une note interne' : 'Enregistrer + transmettre'}
                    </button>
                </div>
            </form>
        </li>
    );
}
