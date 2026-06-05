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

                    {/* Scénario d'appel téléphonique */}
                    <ScenarioAppel prospect={prospect} />

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

// --- Scénario d'appel personnalisé ---------------------------------------

function extraireDecideur(prospect) {
    const m = (prospect.signal_alerte || '').match(
        /(?:dirigeant|contacter en direct)\s*:?\s*([A-ZÀ-Ÿ][\wÀ-ÿ'’.-]+(?:\s+[A-ZÀ-Ÿ][\wÀ-ÿ'’.-]+)?)/i
    );
    return m ? m[1].trim() : null;
}

function valueProp(prospect) {
    const e = prospect.entreprise;
    switch (prospect.source_fichier) {
        case 'clients_tech':
            return {
                raison: `je vois que ${e} développe son propre produit logiciel`,
                pitch: 'développeur freelance (Laravel/React), j’aide les éditeurs et scale-ups à accélérer leur delivery produit, en renfort de l’équipe',
                question: 'Est-ce qu’il vous arrive de prendre des renforts dev externes sur votre roadmap ?',
            };
        case 'grands_comptes':
            return {
                raison: `vous menez sûrement des projets de transformation digitale chez ${e}`,
                pitch: 'développeur freelance, j’interviens en renfort / régie sur les chantiers digitaux des grands comptes',
                question: 'Vous faites appel à des prestataires externes pour vos projets web/applicatifs ?',
            };
        case 'besoins':
            return {
                raison: `j’ai vu votre appel d’offres « ${(prospect.signal_alerte || '').replace(/^Besoin exprimé\s*:\s*«?\s*/i, '').split('»')[0].slice(0, 60)} »`,
                pitch: 'développeur, je peux répondre à votre consultation',
                question: 'La consultation est-elle toujours ouverte, et qui la pilote ?',
            };
        default:
            return {
                raison: `j’accompagne les ${prospect.secteur || 'professionnels'} ${prospect.localite ? 'de ' + prospect.localite : ''}`,
                pitch: 'je crée et modernise des sites web qui ramènent des clients',
                question: 'Votre site internet est-il à jour, ou c’est un sujet que vous repoussez un peu ?',
            };
    }
}

function ScenarioAppel({ prospect }) {
    const v = valueProp(prospect);
    const qui = extraireDecideur(prospect);
    const demande = qui ? `${qui}` : 'le responsable';

    const scenario = [
        ['1. Accroche (0-20 s)',
            `« Bonjour, je souhaiterais parler à ${demande}. … ` +
            `[TON PRÉNOM] de TechCare Solutions, vous avez 30 secondes ? »`],
        ['2. Raison de l’appel',
            `« Je me permets de vous appeler car ${v.raison}. Côté TechCare, ${v.pitch}. »`],
        ['3. Question d’accroche',
            `« ${v.question} »  → on laisse parler, on écoute le besoin.`],
        ['4. Décrocher un échange',
            '« Le mieux, c’est qu’on prenne 15 min pour voir si je peux vous être utile concrètement. ' +
            'Vous préférez plutôt mardi ou jeudi en fin de journée ? »'],
        ['5. Réponses aux objections',
            '• « Pas le temps » → « Justement, 15 min suffisent, je m’adapte à votre agenda. »\n' +
            '• « On a déjà une équipe/un prestataire » → « Parfait, je viens en renfort sur les pics ou les sujets pointus, pas en remplacement. »\n' +
            '• « Envoyez un mail » → « Avec plaisir — je vous envoie 3 lignes + 2 références ; je vous rappelle jeudi pour votre avis ? »\n' +
            '• « Pas de budget » → « Compris. On échange quand même 15 min pour que je sois en tête le moment venu ? »'],
        ['6. Clôture',
            'Reformuler le prochain pas (RDV, mail, rappel), remercier. → puis logguer l’appel dans l’historique ci-dessous.'],
    ];

    const texte = `Scénario d'appel — ${prospect.entreprise}\n` +
        (prospect.telephone ? `Tél : ${prospect.telephone}\n` : '') + '\n' +
        scenario.map(([t, c]) => `${t}\n${c}`).join('\n\n');

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold text-gray-800">📞 Scénario d’appel</h3>
                <div className="flex items-center gap-3">
                    {prospect.telephone && (
                        <a href={`tel:${prospect.telephone.replace(/\s/g, '')}`}
                            className="rounded-md bg-green-600 px-3 py-1 text-sm font-medium text-white">
                            Appeler {prospect.telephone}
                        </a>
                    )}
                    <button type="button" onClick={() => navigator.clipboard?.writeText(texte)}
                        className="text-sm text-indigo-600 underline">Copier</button>
                </div>
            </div>
            {!prospect.telephone && (
                <p className="mb-3 text-xs text-amber-600">Aucun numéro renseigné — à compléter avant l’appel.</p>
            )}
            <ol className="space-y-3">
                {scenario.map(([titre, contenu]) => (
                    <li key={titre}>
                        <div className="text-sm font-medium text-gray-700">{titre}</div>
                        <div className="whitespace-pre-line text-sm text-gray-600">{contenu}</div>
                    </li>
                ))}
            </ol>
        </div>
    );
}
