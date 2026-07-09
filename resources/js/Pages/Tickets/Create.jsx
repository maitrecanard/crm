import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

export default function Create({ clients, internalProjects = [], types, gravites, recurrences }) {
    const form = useForm({
        interne: false, client_id: '', project_id: '', type: 'bug', titre: '', description: '',
        gravite: 'majeur', recurrence: '', prochaine_echeance: '', issue_git: '',
    });

    const projets = useMemo(() => {
        const c = clients.find((c) => String(c.id) === String(form.data.client_id));
        return c ? c.projects : [];
    }, [clients, form.data.client_id]);

    const submit = (e) => {
        e.preventDefault();
        form.post(route('tickets.store'));
    };

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('tickets.index')} className="text-sm text-indigo-600 hover:underline">← Tickets</Link>
                <h2 className="text-xl font-semibold text-gray-800">Nouveau ticket</h2>
            </div>
        }>
            <Head title="Nouveau ticket" />
            <div className="mx-auto max-w-2xl p-4 sm:p-6 lg:p-8">
                <form onSubmit={submit} className="space-y-4 rounded-lg bg-white p-6 shadow">
                    {/* Ticket interne : aucune notification client, rattachement à un projet interne */}
                    <label className="flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        <input type="checkbox" checked={form.data.interne}
                            onChange={(e) => { form.setData('interne', e.target.checked); form.setData('client_id', ''); form.setData('project_id', ''); }}
                            className="rounded border-gray-300" />
                        🔒 Ticket interne (aucun e-mail envoyé)
                    </label>

                    {form.data.interne ? (
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Projet interne (optionnel)</label>
                            <select value={form.data.project_id} onChange={(e) => form.setData('project_id', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Aucun projet</option>
                                {internalProjects.map((p) => <option key={p.id} value={p.id}>{p.titre}</option>)}
                            </select>
                            {internalProjects.length === 0 && (
                                <p className="mt-1 text-xs text-gray-400">Aucun projet interne pour l’instant — créez-en un depuis l’onglet Projets.</p>
                            )}
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Client *</label>
                                <select value={form.data.client_id}
                                    onChange={(e) => { form.setData('client_id', e.target.value); form.setData('project_id', ''); }}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Choisir un client…</option>
                                    {clients.map((c) => <option key={c.id} value={c.id}>{c.entreprise}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Projet *</label>
                                <select value={form.data.project_id} onChange={(e) => form.setData('project_id', e.target.value)}
                                    disabled={!form.data.client_id}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm disabled:bg-gray-50">
                                    <option value="">{form.data.client_id ? 'Choisir un projet…' : 'Sélectionnez d’abord un client'}</option>
                                    {projets.map((p) => <option key={p.id} value={p.id}>{p.titre}</option>)}
                                </select>
                                {form.errors.project_id && <p className="text-xs text-red-600">{form.errors.project_id}</p>}
                            </div>
                        </div>
                    )}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Type *</label>
                            <select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                {Object.entries(types).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Gravité *</label>
                            <select value={form.data.gravite} onChange={(e) => form.setData('gravite', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                {Object.entries(gravites).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                            </select>
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Titre *</label>
                        <input value={form.data.titre} onChange={(e) => form.setData('titre', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        {form.errors.titre && <p className="text-xs text-red-600">{form.errors.titre}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Description</label>
                        <textarea rows="4" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                    </div>

                    {form.data.type === 'maintenance' && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Récurrence</label>
                                <select value={form.data.recurrence} onChange={(e) => form.setData('recurrence', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">—</option>
                                    {Object.entries(recurrences).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Prochaine échéance</label>
                                <input type="date" value={form.data.prochaine_echeance}
                                    onChange={(e) => form.setData('prochaine_echeance', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                            </div>
                        </div>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Issue Git (optionnel)</label>
                        <input value={form.data.issue_git} onChange={(e) => form.setData('issue_git', e.target.value)}
                            placeholder="owner/repo#123"
                            className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                    </div>

                    <div className="flex justify-end gap-2">
                        <Link href={route('tickets.index')} className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600">Annuler</Link>
                        <button disabled={form.processing || (!form.data.interne && !form.data.project_id) || !form.data.titre}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            Créer le ticket
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
