import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ prospect, statuts, typeOptions }) {
    const form = useForm({
        statut: prospect.statut,
        prochaine_relance: prospect.prochaine_relance ? prospect.prochaine_relance.substring(0, 10) : '',
        notes: prospect.notes || '',
    });

    const inter = useForm({ type: 'appel', note: '', date: '' });

    const save = (e) => {
        e.preventDefault();
        form.patch(route('prospects.update', prospect.id), { preserveScroll: true });
    };

    const addInteraction = (e) => {
        e.preventDefault();
        inter.post(route('interactions.store', prospect.id), {
            preserveScroll: true,
            onSuccess: () => inter.reset('note'),
        });
    };

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('prospects.index')} className="text-gray-400 hover:text-gray-600">← Prospects</Link>
                <h2 className="text-xl font-semibold text-gray-800">{prospect.entreprise}</h2>
            </div>
        }>
            <Head title={prospect.entreprise} />

            <div className="mx-auto max-w-5xl grid gap-6 p-4 sm:p-6 lg:p-8 md:grid-cols-3">
                {/* Colonne infos */}
                <div className="md:col-span-2 space-y-6">
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 font-semibold text-gray-800">Informations</h3>
                        <dl className="grid grid-cols-2 gap-3 text-sm">
                            <Info label="Localité" value={prospect.localite} />
                            <Info label="Secteur" value={prospect.secteur} />
                            <Info label="Téléphone" value={prospect.telephone || '—'} />
                            <Info label="Email" value={prospect.email || '—'} />
                            <Info label="Catégorie" value={prospect.categorie} />
                            <Info label="Source" value={prospect.source_fichier} />
                        </dl>
                        {prospect.signal_alerte && (
                            <div className="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                                <span className="font-semibold">Signal : </span>{prospect.signal_alerte}
                            </div>
                        )}
                        {prospect.source_url && (
                            <a href={prospect.source_url} target="_blank" rel="noreferrer"
                                className="mt-3 inline-block text-sm text-indigo-600 underline">Voir la source ↗</a>
                        )}
                    </div>

                    {/* Appels d'offres liés */}
                    {prospect.tenders && prospect.tenders.length > 0 && (
                        <div className="rounded-lg bg-white p-6 shadow">
                            <h3 className="mb-3 font-semibold text-gray-800">
                                Appels d’offres ({prospect.tenders.length})
                            </h3>
                            <ul className="space-y-2">
                                {prospect.tenders.map((t) => (
                                    <li key={t.id}>
                                        <Link href={route('ao.show', t.id)}
                                            className="flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-gray-50">
                                            <span className="truncate text-gray-700">{t.objet?.substring(0, 60)}</span>
                                            <span className="ml-2 whitespace-nowrap text-gray-400">
                                                {t.date_limite ? new Date(t.date_limite).toLocaleDateString('fr-FR') : '—'}
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* Interactions */}
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 font-semibold text-gray-800">Historique</h3>
                        <form onSubmit={addInteraction} className="mb-4 flex flex-wrap items-end gap-2">
                            <select value={inter.data.type} onChange={(e) => inter.setData('type', e.target.value)}
                                className="rounded-md border-gray-300 text-sm">
                                {Object.entries(typeOptions).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                            <input value={inter.data.note} onChange={(e) => inter.setData('note', e.target.value)}
                                placeholder="Note (ex. laissé un message)…"
                                className="flex-1 min-w-[200px] rounded-md border-gray-300 text-sm" />
                            <button className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white">Ajouter</button>
                        </form>
                        <ul className="space-y-2">
                            {prospect.interactions.map((it) => (
                                <li key={it.id} className="border-l-2 border-indigo-200 pl-3 text-sm">
                                    <span className="font-medium">{typeOptions[it.type] || it.type}</span>
                                    <span className="ml-2 text-gray-400">{new Date(it.date).toLocaleString('fr-FR')}</span>
                                    {it.note && <div className="text-gray-600">{it.note}</div>}
                                </li>
                            ))}
                            {prospect.interactions.length === 0 && <li className="text-sm text-gray-400">Aucune interaction.</li>}
                        </ul>
                    </div>
                </div>

                {/* Colonne suivi */}
                <div className="space-y-4">
                    <form onSubmit={save} className="rounded-lg bg-white p-6 shadow space-y-4">
                        <h3 className="font-semibold text-gray-800">Suivi</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Statut</label>
                            <select value={form.data.statut} onChange={(e) => form.setData('statut', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                {Object.entries(statuts).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Prochaine relance</label>
                            <input type="date" value={form.data.prochaine_relance}
                                onChange={(e) => form.setData('prochaine_relance', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea rows="6" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                        </div>
                        <button disabled={form.processing}
                            className="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            Enregistrer
                        </button>
                        {form.recentlySuccessful && <p className="text-sm text-green-600">Enregistré ✓</p>}
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Info({ label, value }) {
    return (
        <div>
            <dt className="text-xs uppercase text-gray-400">{label}</dt>
            <dd className="text-gray-800">{value || '—'}</dd>
        </div>
    );
}
