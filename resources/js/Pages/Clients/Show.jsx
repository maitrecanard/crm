import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FacturationMensuelle from '@/Components/FacturationMensuelle';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const euro = (v) => (v == null || v === '' ? '—' : Number(v).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) + ' € HT');

const BESOIN_BADGE = {
    a_traiter: 'bg-amber-100 text-amber-700', en_cours: 'bg-blue-100 text-blue-700',
    devis: 'bg-violet-100 text-violet-700', traite: 'bg-green-100 text-green-700',
};
const DEVIS_BADGE = {
    brouillon: 'bg-gray-100 text-gray-600', envoye: 'bg-blue-100 text-blue-700',
    accepte: 'bg-green-100 text-green-700', refuse: 'bg-rose-100 text-rose-700',
};
const FACT_BADGE = {
    a_envoyer: 'bg-amber-100 text-amber-700', envoyee: 'bg-blue-100 text-blue-700',
    payee: 'bg-green-100 text-green-700', impayee: 'bg-rose-100 text-rose-700',
};

export default function ClientShow({ client, besoins, devis, facturesPonctuelles, facturation,
    emailGenere, statutsBesoin, statutsDevis, statutsFacture }) {
    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('clients.index')} className="text-gray-400 hover:text-gray-600">← Clients</Link>
                <h2 className="text-xl font-semibold text-gray-800">{client.entreprise}</h2>
            </div>
        }>
            <Head title={`Client — ${client.entreprise}`} />

            <div className="mx-auto max-w-5xl space-y-6 p-4 sm:p-6 lg:p-8">
                {/* Bandeau client + navigation rapide */}
                <div className="flex flex-wrap items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <span className="font-semibold text-emerald-800">✓ Client</span>
                    {client.client_depuis && (
                        <span className="text-sm text-emerald-700">
                            depuis le {new Date(client.client_depuis).toLocaleDateString('fr-FR')}
                        </span>
                    )}
                    <nav className="ml-auto flex flex-wrap gap-2 text-sm">
                        <a href="#besoins" className="rounded-md bg-white px-3 py-1 text-indigo-600 hover:bg-emerald-100">Besoins</a>
                        <a href="#devis" className="rounded-md bg-white px-3 py-1 text-indigo-600 hover:bg-emerald-100">Devis</a>
                        <a href="#factures" className="rounded-md bg-white px-3 py-1 text-indigo-600 hover:bg-emerald-100">Factures</a>
                        <a href="#email" className="rounded-md bg-white px-3 py-1 text-indigo-600 hover:bg-emerald-100">✉️ Email</a>
                    </nav>
                </div>

                <InfosClient client={client} />

                {client.projects?.length > 0 && (
                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-3 font-semibold text-gray-800">📁 Projets</h3>
                        <div className="flex flex-wrap gap-2">
                            {client.projects.map((p) => (
                                <Link key={p.id} href={route('projects.show', p.id)}
                                    className="rounded-md bg-indigo-50 px-3 py-1 text-sm text-indigo-700 hover:bg-indigo-100">
                                    {p.titre} →
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

                <BesoinsCard client={client} besoins={besoins} statuts={statutsBesoin} />
                <DevisCard client={client} devis={devis} statuts={statutsDevis} />

                <div id="factures" className="space-y-6">
                    <FacturationMensuelle prospect={client} facturation={facturation} />
                    <FacturesPonctuellesCard client={client} factures={facturesPonctuelles} statuts={statutsFacture} />
                </div>

                <EmailCard client={client} emailGenere={emailGenere} />
            </div>
        </AuthenticatedLayout>
    );
}

function InfosClient({ client }) {
    const form = useForm({
        entreprise: client.entreprise || '', email: client.email || '',
        telephone: client.telephone || '', localite: client.localite || '',
        secteur: client.secteur || '',
    });
    const save = (e) => { e.preventDefault(); form.put(route('prospects.update', client.id), { preserveScroll: true }); };
    const F = ({ k, label, type = 'text' }) => (
        <div>
            <label className="block text-xs text-gray-500">{label}</label>
            <input type={type} value={form.data[k]} onChange={(e) => form.setData(k, e.target.value)}
                className="w-full rounded-md border-gray-300 text-sm" />
        </div>
    );
    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold text-gray-800">Informations</h3>
                {form.recentlySuccessful && <span className="text-xs text-green-600">Enregistré ✓</span>}
            </div>
            <div className="grid grid-cols-2 gap-3">
                <F k="entreprise" label="Nom / entreprise" />
                <F k="email" label="Email" type="email" />
                <F k="telephone" label="Téléphone" />
                <F k="localite" label="Localité" />
                <F k="secteur" label="Secteur" />
            </div>
            <button onClick={save} disabled={form.processing}
                className="mt-3 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                {form.processing ? 'Enregistrement…' : 'Enregistrer'}
            </button>
        </div>
    );
}

function BesoinsCard({ client, besoins, statuts }) {
    const add = useForm({ titre: '', description: '', statut: 'a_traiter' });
    const submit = (e) => { e.preventDefault(); add.post(route('besoins.store', client.id), { preserveScroll: true, onSuccess: () => add.reset() }); };
    const setStatut = (b, statut) => router.put(route('besoins.update', b.id), { statut }, { preserveScroll: true });
    const remove = (b) => confirm('Supprimer ce besoin ?') && router.delete(route('besoins.destroy', b.id), { preserveScroll: true });
    return (
        <div id="besoins" className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-3 font-semibold text-gray-800">🎯 Besoins ({besoins.length})</h3>
            <ul className="space-y-2">
                {besoins.map((b) => (
                    <li key={b.id} className="flex items-start gap-2 rounded-md border border-gray-100 p-3 text-sm">
                        <div className="flex-1">
                            <p className="font-medium text-gray-800">{b.titre}</p>
                            {b.description && <p className="text-gray-500">{b.description}</p>}
                        </div>
                        <select value={b.statut} onChange={(e) => setStatut(b, e.target.value)}
                            className={`rounded-full border-0 px-2 py-0.5 text-xs font-medium ${BESOIN_BADGE[b.statut]}`}>
                            {Object.entries(statuts).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                        </select>
                        <button onClick={() => remove(b)} className="text-gray-300 hover:text-rose-500" title="Supprimer">✕</button>
                    </li>
                ))}
                {besoins.length === 0 && <li className="text-sm text-gray-400">Aucun besoin enregistré.</li>}
            </ul>
            <form onSubmit={submit} className="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                <input value={add.data.titre} onChange={(e) => add.setData('titre', e.target.value)}
                    placeholder="Nouveau besoin…" className="flex-1 rounded-md border-gray-300 text-sm" />
                <input value={add.data.description} onChange={(e) => add.setData('description', e.target.value)}
                    placeholder="Détails (optionnel)" className="flex-1 rounded-md border-gray-300 text-sm" />
                <button disabled={add.processing || !add.data.titre}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Ajouter</button>
            </form>
        </div>
    );
}

function DevisCard({ client, devis, statuts }) {
    const add = useForm({ reference: '', montant_ht: '', statut: 'envoye', date_devis: '', lien: '' });
    const submit = (e) => { e.preventDefault(); add.post(route('devis.store', client.id), { preserveScroll: true, onSuccess: () => add.reset() }); };
    const setStatut = (d, statut) => router.put(route('devis.update', d.id), { statut }, { preserveScroll: true });
    const remove = (d) => confirm('Supprimer ce devis ?') && router.delete(route('devis.destroy', d.id), { preserveScroll: true });
    return (
        <div id="devis" className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-3 font-semibold text-gray-800">📝 Devis ({devis.length})</h3>
            <ul className="space-y-2">
                {devis.map((d) => (
                    <li key={d.id} className="flex flex-wrap items-center gap-2 rounded-md border border-gray-100 p-3 text-sm">
                        <span className="font-medium text-gray-800">{d.reference || 'Sans référence'}</span>
                        <span className="text-gray-600">{euro(d.montant_ht)}</span>
                        {d.date_devis && <span className="text-xs text-gray-400">{new Date(d.date_devis).toLocaleDateString('fr-FR')}</span>}
                        {d.lien && <a href={d.lien} target="_blank" rel="noreferrer" className="text-xs text-indigo-600 underline">PDF ↗</a>}
                        <select value={d.statut} onChange={(e) => setStatut(d, e.target.value)}
                            className={`ml-auto rounded-full border-0 px-2 py-0.5 text-xs font-medium ${DEVIS_BADGE[d.statut]}`}>
                            {Object.entries(statuts).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                        </select>
                        <button onClick={() => remove(d)} className="text-gray-300 hover:text-rose-500" title="Supprimer">✕</button>
                    </li>
                ))}
                {devis.length === 0 && <li className="text-sm text-gray-400">Aucun devis.</li>}
            </ul>
            <form onSubmit={submit} className="mt-4 grid grid-cols-2 gap-2 border-t border-gray-100 pt-4 sm:grid-cols-5">
                <input value={add.data.reference} onChange={(e) => add.setData('reference', e.target.value)}
                    placeholder="Référence" className="rounded-md border-gray-300 text-sm" />
                <input type="number" step="0.01" value={add.data.montant_ht} onChange={(e) => add.setData('montant_ht', e.target.value)}
                    placeholder="Montant HT" className="rounded-md border-gray-300 text-sm" />
                <input type="date" value={add.data.date_devis} onChange={(e) => add.setData('date_devis', e.target.value)}
                    className="rounded-md border-gray-300 text-sm" />
                <input value={add.data.lien} onChange={(e) => add.setData('lien', e.target.value)}
                    placeholder="Lien PDF" className="rounded-md border-gray-300 text-sm" />
                <button disabled={add.processing}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Ajouter</button>
            </form>
        </div>
    );
}

function FacturesPonctuellesCard({ client, factures, statuts }) {
    const add = useForm({ reference: '', montant_ht: '', statut: 'envoyee', date_facture: '', lien: '' });
    const submit = (e) => { e.preventDefault(); add.post(route('factures.store', client.id), { preserveScroll: true, onSuccess: () => add.reset() }); };
    const setStatut = (f, statut) => router.put(route('factures.update', f.id), { statut }, { preserveScroll: true });
    const remove = (f) => confirm('Supprimer cette facture ?') && router.delete(route('factures.destroy', f.id), { preserveScroll: true });
    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <h3 className="mb-3 font-semibold text-gray-800">🧾 Factures ponctuelles ({factures.length})</h3>
            <ul className="space-y-2">
                {factures.map((f) => (
                    <li key={f.id} className="flex flex-wrap items-center gap-2 rounded-md border border-gray-100 p-3 text-sm">
                        <span className="font-medium text-gray-800">{f.reference || 'Sans référence'}</span>
                        <span className="text-gray-600">{euro(f.montant_ht)}</span>
                        {f.date_facture && <span className="text-xs text-gray-400">{new Date(f.date_facture).toLocaleDateString('fr-FR')}</span>}
                        {f.lien && <a href={f.lien} target="_blank" rel="noreferrer" className="text-xs text-indigo-600 underline">PDF ↗</a>}
                        <select value={f.statut} onChange={(e) => setStatut(f, e.target.value)}
                            className={`ml-auto rounded-full border-0 px-2 py-0.5 text-xs font-medium ${FACT_BADGE[f.statut]}`}>
                            {Object.entries(statuts).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                        </select>
                        <button onClick={() => remove(f)} className="text-gray-300 hover:text-rose-500" title="Supprimer">✕</button>
                    </li>
                ))}
                {factures.length === 0 && <li className="text-sm text-gray-400">Aucune facture ponctuelle.</li>}
            </ul>
            <form onSubmit={submit} className="mt-4 grid grid-cols-2 gap-2 border-t border-gray-100 pt-4 sm:grid-cols-5">
                <input value={add.data.reference} onChange={(e) => add.setData('reference', e.target.value)}
                    placeholder="Référence" className="rounded-md border-gray-300 text-sm" />
                <input type="number" step="0.01" value={add.data.montant_ht} onChange={(e) => add.setData('montant_ht', e.target.value)}
                    placeholder="Montant HT" className="rounded-md border-gray-300 text-sm" />
                <input type="date" value={add.data.date_facture} onChange={(e) => add.setData('date_facture', e.target.value)}
                    className="rounded-md border-gray-300 text-sm" />
                <input value={add.data.lien} onChange={(e) => add.setData('lien', e.target.value)}
                    placeholder="Lien PDF" className="rounded-md border-gray-300 text-sm" />
                <button disabled={add.processing}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">Ajouter</button>
            </form>
        </div>
    );
}

function EmailCard({ client, emailGenere }) {
    const mail = useForm({ corps: emailGenere || '' });
    // Resynchronise le corps quand l'IA régénère l'email.
    useEffect(() => { mail.setData('corps', emailGenere || ''); }, [emailGenere]);
    const [generating, setGenerating] = useState(false);

    const generate = () => {
        setGenerating(true);
        router.post(route('prospects.generateEmail', client.id), {}, {
            preserveScroll: true, onFinish: () => setGenerating(false),
        });
    };
    const send = (e) => { e.preventDefault(); mail.post(route('prospects.sendEmail', client.id), { preserveScroll: true }); };

    return (
        <div id="email" className="rounded-lg bg-white p-6 shadow">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold text-gray-800">✉️ Email personnalisé</h3>
                {mail.recentlySuccessful && <span className="text-xs text-green-600">Envoyé ✓</span>}
            </div>
            {!client.email && (
                <p className="mb-3 rounded bg-amber-50 p-2 text-xs text-amber-800">
                    ⚠️ Ce client n’a pas d’adresse e-mail : renseigne-la dans « Informations » pour pouvoir envoyer.
                </p>
            )}
            <textarea rows="10" value={mail.data.corps} onChange={(e) => mail.setData('corps', e.target.value)}
                placeholder="Génère un email par IA, ou écris-le ici. 1re ligne « Objet : … » pour fixer l’objet."
                className="w-full rounded-md border-gray-300 text-sm" />
            <div className="mt-3 flex flex-wrap gap-3">
                <button type="button" onClick={generate} disabled={generating}
                    className="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                    {generating ? 'Génération…' : '✨ Générer par IA'}
                </button>
                <button onClick={send} disabled={mail.processing || !client.email || !mail.data.corps}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                    {mail.processing ? 'Envoi…' : `Envoyer à ${client.email || '—'}`}
                </button>
            </div>
        </div>
    );
}
