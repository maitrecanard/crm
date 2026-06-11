import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

const PIPE_COLORS = {
    a_contacter: 'bg-gray-400', contacte: 'bg-blue-500', relance: 'bg-amber-500',
    rdv: 'bg-violet-500', gagne: 'bg-green-500', perdu: 'bg-red-400',
};

function Kpi({ label, value, accent = 'text-gray-900', to }) {
    const inner = (
        <div className="rounded-lg bg-white p-5 shadow transition hover:shadow-md">
            <div className={`text-3xl font-bold ${accent}`}>{value}</div>
            <div className="mt-1 text-sm text-gray-500">{label}</div>
        </div>
    );
    return to ? <Link href={to}>{inner}</Link> : inner;
}

function jdiff(dateStr) {
    if (!dateStr) return null;
    const d = new Date(dateStr); d.setHours(0, 0, 0, 0);
    const t = new Date(); t.setHours(0, 0, 0, 0);
    return Math.round((d - t) / 86400000);
}

export default function Dashboard({ statutsProspect, prospectStats, kpis, relances, aoUrgents, aContacterEmail = [], avecEmailTotal = 0, facturesEnRetard = [] }) {
    const pipeOrder = ['a_contacter', 'contacte', 'relance', 'rdv', 'gagne', 'perdu'];
    const maxPipe = Math.max(1, ...pipeOrder.map((k) => prospectStats[k] || 0));

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Tableau de bord</h2>}>
            <Head title="Tableau de bord" />

            <div className="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
                {/* KPIs */}
                <div className="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                    <Kpi label="Prospects" value={kpis.total} to={route('prospects.index')} />
                    <Kpi label="À contacter" value={kpis.a_contacter} accent="text-gray-600"
                        to={route('prospects.index', { statut: 'a_contacter' })} />
                    <Kpi label="En cours" value={kpis.en_cours} accent="text-blue-600" />
                    <Kpi label="RDV" value={kpis.rdv} accent="text-violet-600"
                        to={route('prospects.index', { statut: 'rdv' })} />
                    <Kpi label="Gagnés" value={kpis.gagne} accent="text-green-600"
                        to={route('prospects.index', { statut: 'gagne' })} />
                    <Kpi label="AO ouverts" value={kpis.ao_ouverts} accent="text-amber-600"
                        to={route('ao.index')} />
                </div>

                {/* Alerte : factures mensuelles non envoyées (référence manquante) */}
                {facturesEnRetard.length > 0 && (
                    <div className="rounded-lg border border-rose-200 bg-rose-50 p-4">
                        <h3 className="mb-2 flex items-center gap-2 font-semibold text-rose-800">
                            🧾 Factures mensuelles non envoyées
                            <span className="rounded-full bg-rose-600 px-2 py-0.5 text-xs text-white">{facturesEnRetard.length}</span>
                        </h3>
                        <ul className="space-y-1">
                            {facturesEnRetard.map((f, i) => (
                                <li key={i} className="flex flex-wrap items-center gap-x-2 text-sm text-rose-900">
                                    <Link href={route('prospects.show', f.prospect_id)}
                                        className="font-medium underline hover:text-rose-700">{f.entreprise}</Link>
                                    {f.libelle && <span className="text-rose-500">({f.libelle})</span>}
                                    <span className="capitalize">— facture {f.mois_label}</span>
                                    <span className="text-rose-500">échéance dépassée le {f.echeance}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Pipeline */}
                    <div className="rounded-lg bg-white p-6 shadow lg:col-span-1">
                        <h3 className="mb-4 font-semibold text-gray-800">Pipeline prospects</h3>
                        <div className="space-y-3">
                            {pipeOrder.map((k) => (
                                <button key={k} onClick={() => router.get(route('prospects.index', { statut: k }))}
                                    className="block w-full text-left">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">{statutsProspect[k]}</span>
                                        <span className="font-medium">{prospectStats[k] || 0}</span>
                                    </div>
                                    <div className="mt-1 h-2 rounded bg-gray-100">
                                        <div className={`h-2 rounded ${PIPE_COLORS[k]}`}
                                            style={{ width: `${((prospectStats[k] || 0) / maxPipe) * 100}%` }} />
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Relances */}
                    <div className="rounded-lg bg-white p-6 shadow lg:col-span-1">
                        <h3 className="mb-4 font-semibold text-gray-800">Relances à venir (7 j)</h3>
                        {relances.length === 0 && <p className="text-sm text-gray-400">Aucune relance programmée.</p>}
                        <ul className="space-y-2">
                            {relances.map((p) => {
                                const j = jdiff(p.prochaine_relance);
                                return (
                                    <li key={p.id}>
                                        <Link href={route('prospects.show', p.id)}
                                            className="flex items-center justify-between rounded px-2 py-1 text-sm hover:bg-gray-50">
                                            <span className="truncate text-gray-700">{p.entreprise}</span>
                                            <span className={j < 0 ? 'text-red-600' : j <= 1 ? 'text-amber-600' : 'text-gray-400'}>
                                                {j < 0 ? `retard ${-j}j` : j === 0 ? "aujourd'hui" : `J-${j}`}
                                            </span>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>

                    {/* AO urgents */}
                    <div className="rounded-lg bg-white p-6 shadow lg:col-span-1">
                        <h3 className="mb-4 font-semibold text-gray-800">Appels d’offres urgents</h3>
                        {aoUrgents.length === 0 && <p className="text-sm text-gray-400">Aucun AO ouvert.</p>}
                        <ul className="space-y-2">
                            {aoUrgents.map((t) => {
                                const j = jdiff(t.date_limite);
                                return (
                                    <li key={t.id}>
                                        <Link href={route('ao.show', t.id)}
                                            className="block rounded px-2 py-1 text-sm hover:bg-gray-50">
                                            <div className="flex items-center justify-between">
                                                <span className="truncate text-gray-700">{t.acheteur}</span>
                                                <span className={j <= 3 ? 'text-red-600 font-medium' : j <= 7 ? 'text-amber-600' : 'text-gray-400'}>
                                                    J-{j}
                                                </span>
                                            </div>
                                            <div className="truncate text-xs text-gray-400">{t.objet}</div>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                </div>

                {/* À contacter — email disponible (action rapide) */}
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="font-semibold text-gray-800">
                            📧 À contacter — email disponible
                            <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">{avecEmailTotal}</span>
                        </h3>
                        <Link href={route('prospects.index', { statut: 'a_contacter', has_email: 1 })}
                            className="text-sm text-indigo-600 hover:underline">Voir tous →</Link>
                    </div>
                    {aContacterEmail.length === 0 ? (
                        <p className="text-sm text-gray-400">Aucun prospect à contacter avec un email pour l’instant.</p>
                    ) : (
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            {aContacterEmail.map((p) => (
                                <Link key={p.id} href={route('prospects.show', p.id)}
                                    className="rounded-md border border-gray-100 p-3 hover:border-indigo-200 hover:bg-indigo-50/40">
                                    <div className="truncate text-sm font-medium text-gray-800">{p.entreprise}</div>
                                    <div className="truncate text-xs text-indigo-600">{p.email}</div>
                                    <div className="truncate text-xs text-gray-400">
                                        {[p.secteur, p.localite].filter(Boolean).join(' · ') || '—'}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
