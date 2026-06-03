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

export default function Dashboard({ statutsProspect, prospectStats, kpis, relances, aoUrgents }) {
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
            </div>
        </AuthenticatedLayout>
    );
}
