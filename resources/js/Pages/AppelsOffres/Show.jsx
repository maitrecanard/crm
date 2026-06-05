import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Show({ tender, statuts }) {
    const form = useForm({ statut: tender.statut, notes: tender.notes || '' });

    const save = (e) => {
        e.preventDefault();
        form.put(route('ao.update', tender.id), { preserveScroll: true });
    };

    const j = tender.jours_restants;
    const deadlineColor = j === null ? 'text-gray-500'
        : j < 0 ? 'text-gray-400'
        : j <= 3 ? 'text-red-600' : j <= 7 ? 'text-amber-600' : 'text-gray-800';

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('ao.index')} className="text-gray-400 hover:text-gray-600">← Appels d’offres</Link>
                <h2 className="truncate text-xl font-semibold text-gray-800">{tender.acheteur}</h2>
            </div>
        }>
            <Head title={tender.acheteur || 'Appel d’offres'} />

            <div className="mx-auto grid max-w-5xl gap-6 p-4 sm:p-6 lg:p-8 md:grid-cols-3">
                <div className="space-y-6 md:col-span-2">
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 font-semibold text-gray-800">{tender.objet}</h3>
                        <dl className="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt className="text-xs uppercase text-gray-400">Acheteur</dt>
                                <dd className="text-gray-800">
                                    {tender.prospect ? (
                                        <Link href={route('prospects.show', tender.prospect.id)}
                                            className="text-indigo-600 hover:underline">
                                            {tender.acheteur} →
                                        </Link>
                                    ) : (tender.acheteur || '—')}
                                </dd>
                            </div>
                            <Info label="Département" value={tender.departement} />
                            <Info label="Procédure" value={tender.procedure} />
                            <Info label="Publié le" value={tender.date_parution ? new Date(tender.date_parution).toLocaleDateString('fr-FR') : '—'} />
                            <div>
                                <dt className="text-xs uppercase text-gray-400">Date limite</dt>
                                <dd className={`font-semibold ${deadlineColor}`}>
                                    {tender.date_limite ? new Date(tender.date_limite).toLocaleString('fr-FR') : '—'}
                                    {j !== null && j >= 0 && <span className="ml-2 text-xs">(J-{j})</span>}
                                    {j !== null && j < 0 && <span className="ml-2 text-xs">(dépassé)</span>}
                                </dd>
                            </div>
                        </dl>
                        {tender.url && (
                            <a href={tender.url} target="_blank" rel="noreferrer"
                                className="mt-4 inline-block rounded-md bg-gray-100 px-3 py-2 text-sm text-indigo-600 hover:bg-gray-200">
                                Voir l’avis BOAMP ↗ (DCE + contact)
                            </a>
                        )}
                    </div>

                    <div className="rounded-lg bg-indigo-50 p-4 text-sm text-indigo-900">
                        💡 Le dossier de réponse pré-rempli (mémoire + DPGF + AE) se génère côté moteur :
                        <code className="mx-1 rounded bg-white px-1">python3 generer_dossier.py {tender.idweb}</code>
                        → dossier dans <code className="rounded bg-white px-1">dossiers/</code>.
                    </div>
                </div>

                <div>
                    <form onSubmit={save} className="space-y-4 rounded-lg bg-white p-6 shadow">
                        <h3 className="font-semibold text-gray-800">Candidature</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Statut</label>
                            <select value={form.data.statut} onChange={(e) => form.setData('statut', e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 text-sm">
                                {Object.entries(statuts).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea rows="8" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)}
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
