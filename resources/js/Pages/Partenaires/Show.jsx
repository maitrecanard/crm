import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

const STATUT_COLORS = {
    a_faire: 'bg-gray-100 text-gray-700',
    en_cours: 'bg-blue-100 text-blue-700',
    fait: 'bg-green-100 text-green-700',
};

export default function Show({ partenaire, compteActif, taches, statutsTache, projetsLibres }) {
    const [editing, setEditing] = useState(false);
    const form = useForm({
        nom: partenaire.nom, contact_nom: partenaire.contact_nom || '', email: partenaire.email,
        telephone: partenaire.telephone || '', notes: partenaire.notes || '', actif: partenaire.actif,
    });
    const [projetId, setProjetId] = useState('');

    const save = (e) => { e.preventDefault(); form.put(route('partenaires.update', partenaire.id), { onSuccess: () => setEditing(false) }); };
    const resend = () => router.post(route('partenaires.invite', partenaire.id));
    const attach = () => projetId && router.post(route('partenaires.projets.attach', partenaire.id), { project_id: projetId }, { onSuccess: () => setProjetId('') });
    const detach = (pid) => router.delete(route('partenaires.projets.detach', [partenaire.id, pid]));
    const setTaskStatut = (tid, statut) => router.put(route('tasks.update', tid), { statut }, { preserveScroll: true });
    const destroy = () => confirm('Supprimer ce partenaire et son compte ?') && router.delete(route('partenaires.destroy', partenaire.id));

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link href={route('partenaires.index')} className="text-gray-400 hover:text-gray-600">← Partenaires</Link>
                    <h2 className="text-xl font-semibold text-gray-800">{partenaire.nom}</h2>
                    {compteActif
                        ? <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">Compte actif</span>
                        : <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Invitation en attente</span>}
                </div>
                <button onClick={destroy} className="text-sm text-red-500 hover:text-red-700">Supprimer</button>
            </div>
        }>
            <Head title={partenaire.nom} />
            <div className="mx-auto max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">

                {/* Coordonnées + compte */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="mb-3 flex items-center justify-between">
                        <h3 className="font-semibold text-gray-800">Coordonnées</h3>
                        <button onClick={() => setEditing(!editing)} className="text-sm text-indigo-600 hover:underline">
                            {editing ? 'Annuler' : 'Modifier'}
                        </button>
                    </div>
                    {editing ? (
                        <form onSubmit={save} className="space-y-3">
                            <div className="grid grid-cols-2 gap-3">
                                {['nom', 'contact_nom', 'email', 'telephone'].map((n) => (
                                    <div key={n}>
                                        <label className="block text-xs font-medium text-gray-500">{n}</label>
                                        <input value={form.data[n]} onChange={(e) => form.setData(n, e.target.value)}
                                            className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                                        {form.errors[n] && <p className="text-xs text-red-600">{form.errors[n]}</p>}
                                    </div>
                                ))}
                            </div>
                            <textarea rows="3" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)}
                                placeholder="Notes" className="w-full rounded-md border-gray-300 text-sm" />
                            <label className="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" checked={form.data.actif} onChange={(e) => form.setData('actif', e.target.checked)} />
                                Partenaire actif
                            </label>
                            <button disabled={form.processing}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                Enregistrer
                            </button>
                        </form>
                    ) : (
                        <div className="space-y-1 text-sm text-gray-600">
                            {partenaire.contact_nom && <div>Contact : {partenaire.contact_nom}</div>}
                            <div>Email : {partenaire.email}</div>
                            {partenaire.telephone && <div>Tél : {partenaire.telephone}</div>}
                            {partenaire.notes && <div className="whitespace-pre-line text-gray-500">{partenaire.notes}</div>}
                            {!compteActif && (
                                <button onClick={resend} className="mt-2 rounded-md bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100">
                                    Renvoyer l’e-mail d’activation
                                </button>
                            )}
                        </div>
                    )}
                </div>

                {/* Projets rattachés */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-3 font-semibold text-gray-800">Projets rattachés</h3>
                    <div className="space-y-2">
                        {partenaire.projects?.length ? partenaire.projects.map((p) => (
                            <div key={p.id} className="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 text-sm">
                                <Link href={route('projects.show', p.id)} className="text-indigo-600 hover:underline">
                                    📁 {p.titre} <span className="text-xs text-gray-400">{p.prospect?.entreprise}</span>
                                </Link>
                                <button onClick={() => detach(p.id)} className="text-xs text-gray-400 hover:text-red-600">Détacher</button>
                            </div>
                        )) : <p className="text-sm text-gray-400">Aucun projet rattaché.</p>}
                    </div>
                    {projetsLibres?.length > 0 && (
                        <div className="mt-3 flex items-center gap-2">
                            <select value={projetId} onChange={(e) => setProjetId(e.target.value)}
                                className="flex-1 rounded-md border-gray-300 text-sm">
                                <option value="">Rattacher un projet existant…</option>
                                {projetsLibres.map((p) => (
                                    <option key={p.id} value={p.id}>{p.titre} — {p.prospect?.entreprise}</option>
                                ))}
                            </select>
                            <button onClick={attach} disabled={!projetId}
                                className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white disabled:opacity-50">
                                Rattacher
                            </button>
                        </div>
                    )}
                </div>

                {/* Tâches transmises */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-3 font-semibold text-gray-800">Tâches transmises par le partenaire</h3>
                    <div className="space-y-2">
                        {taches.length ? taches.map((t) => (
                            <div key={t.id} className="rounded-md border border-gray-100 px-3 py-2">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium text-gray-800">{t.titre}</span>
                                    <select value={t.statut} onChange={(e) => setTaskStatut(t.id, e.target.value)}
                                        className={`rounded-full border-0 py-0.5 pl-2 pr-7 text-xs ${STATUT_COLORS[t.statut]}`}>
                                        {Object.entries(statutsTache).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
                                    </select>
                                </div>
                                {t.description && <p className="mt-1 text-xs text-gray-500">{t.description}</p>}
                                <div className="mt-1 flex gap-3 text-xs text-gray-400">
                                    {t.project && <span>📁 {t.project.titre}</span>}
                                    {t.echeance && <span>📅 {new Date(t.echeance).toLocaleDateString('fr-FR')}</span>}
                                </div>
                            </div>
                        )) : <p className="text-sm text-gray-400">Aucune tâche transmise.</p>}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
