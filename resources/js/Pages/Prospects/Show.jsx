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

                    {/* Scénario d'email */}
                    <ScenarioEmail prospect={prospect} />

                    {/* Scénario LinkedIn */}
                    <ScenarioLinkedIn prospect={prospect} />

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

function scenarioEmail(prospect) {
    const e = prospect.entreprise;
    const d = extraireDecideur(prospect);
    const bonjour = d ? `Bonjour ${d.split(' ')[0]},` : 'Bonjour,';
    let objet, accroche, cta;
    switch (prospect.source_fichier) {
        case 'clients_tech':
            objet = `${e} — un renfort dev (Laravel/React) ?`;
            accroche = `J’ai vu que ${e} développe son propre produit — beau travail.`;
            cta = 'Auriez-vous 15 min cette semaine pour voir si je peux vous être utile sur un chantier en cours ?';
            break;
        case 'grands_comptes':
            objet = `Renfort dev en régie — ${e}`;
            accroche = 'Dans le cadre de vos projets digitaux, vous faites sans doute appel à des renforts externes.';
            cta = 'Seriez-vous disponible pour un bref échange (15 min) afin que je vous présente mon profil et mes références ?';
            break;
        case 'besoins':
            objet = `Votre consultation — ${e}`;
            accroche = 'J’ai vu votre appel d’offres et je suis en mesure d’y répondre.';
            cta = 'Pouvez-vous me confirmer que la consultation est ouverte et m’indiquer où récupérer le DCE ?';
            break;
        default:
            objet = `${e} — votre site internet`;
            accroche = `Je suis développeur web et j’accompagne les ${prospect.secteur || 'professionnels'}${prospect.localite ? ' de ' + prospect.localite : ''}.`;
            cta = 'Votre site est-il à jour ? Je peux vous proposer un point gratuit de 15 min.';
    }
    const corps = `${bonjour}\n\n${accroche} Je suis développeur freelance — TechCare Solutions (Laravel/React).\n\n`
        + `${cta}\n\nBonne journée,\n[TON PRÉNOM] · [TÉL] · [SITE / LinkedIn]`;
    const relance = `${bonjour}\n\nJe me permets un petit up — si le sujet « renfort dev » est d’actualité chez ${e}, `
        + 'je reste dispo 15 min quand vous voulez. Sinon dites-moi simplement et je n’insiste pas. 🙂\n\n[TON PRÉNOM]';
    return { objet, corps, relance };
}

function ScenarioEmail({ prospect }) {
    const { objet, corps, relance } = scenarioEmail(prospect);
    const mailto = prospect.email
        ? `mailto:${prospect.email}?subject=${encodeURIComponent(objet)}&body=${encodeURIComponent(corps)}`
        : null;

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold text-gray-800">✉️ Scénario d’email</h3>
                <div className="flex items-center gap-3">
                    {mailto && (
                        <a href={mailto}
                            className="rounded-md bg-indigo-600 px-3 py-1 text-sm font-medium text-white">
                            Écrire l’email
                        </a>
                    )}
                    <button type="button" onClick={() => navigator.clipboard?.writeText(`Objet : ${objet}\n\n${corps}`)}
                        className="text-sm text-indigo-600 underline">Copier</button>
                </div>
            </div>
            {!prospect.email && (
                <p className="mb-3 text-xs text-amber-600">Aucun email renseigné — à trouver (site /contact, LinkedIn).</p>
            )}
            <div className="space-y-3 text-sm">
                <div>
                    <div className="text-xs uppercase text-gray-400">Objet</div>
                    <div className="font-medium text-gray-800">{objet}</div>
                </div>
                <div>
                    <div className="text-xs uppercase text-gray-400">Corps</div>
                    <div className="whitespace-pre-line text-gray-600">{corps}</div>
                </div>
                <details>
                    <summary className="cursor-pointer text-xs uppercase text-gray-400">Relance (J+5)</summary>
                    <div className="mt-1 flex items-start gap-2">
                        <div className="whitespace-pre-line text-gray-600">{relance}</div>
                        <button type="button" onClick={() => navigator.clipboard?.writeText(relance)}
                            className="shrink-0 text-xs text-indigo-600 underline">Copier</button>
                    </div>
                </details>
            </div>
        </div>
    );
}

function scenarioLinkedIn(prospect) {
    const e = prospect.entreprise;
    const d = extraireDecideur(prospect);
    const bonjour = d ? `Bonjour ${d.split(' ')[0]},` : 'Bonjour,';
    let note, message;
    switch (prospect.source_fichier) {
        case 'clients_tech':
            note = `${bonjour} je suis le travail de ${e} sur votre produit. Dév freelance (Laravel/React), j’accompagne les éditeurs sur leurs chantiers. Au plaisir d’échanger si le sujet « renfort tech » vous parle un jour.`;
            message = `Merci pour la connexion ! Je me permets : si ${e} a parfois besoin de renfort dev sur sa roadmap, je serais ravi d’échanger 15 min. Sans engagement bien sûr.`;
            break;
        case 'grands_comptes':
            note = `${bonjour} dév freelance, j’interviens en renfort/régie sur des projets digitaux de structures comme ${e}. Heureux d’entrer en contact.`;
            message = `Merci pour la connexion ! Si vous faites appel à des prestataires sur vos projets web/applicatifs, je serais ravi de vous présenter mon profil en 15 min.`;
            break;
        case 'besoins':
            note = `${bonjour} j’ai vu la consultation portée par ${e}. Développeur, je peux y répondre — au plaisir d’échanger.`;
            message = `Merci pour la connexion ! Concernant votre consultation, pourriez-vous m’indiquer si elle est toujours ouverte et où récupérer le DCE ?`;
            break;
        default:
            note = `${bonjour} dév web, j’accompagne les ${prospect.secteur || 'pros'}${prospect.localite ? ' de ' + prospect.localite : ''} sur leur site et leur visibilité. Au plaisir d’échanger.`;
            message = `Merci pour la connexion ! Si refondre ou créer votre site est un sujet, je propose un point gratuit de 15 min quand vous voulez.`;
    }
    return { note, message };
}

function ScenarioLinkedIn({ prospect }) {
    const { note, message } = scenarioLinkedIn(prospect);
    const d = extraireDecideur(prospect);
    const recherche = `https://www.linkedin.com/search/results/people/?keywords=`
        + encodeURIComponent(`${d ? d + ' ' : ''}${prospect.entreprise}`);

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-3 flex items-center justify-between">
                <h3 className="font-semibold text-gray-800">in Scénario LinkedIn</h3>
                <a href={recherche} target="_blank" rel="noreferrer"
                    className="rounded-md bg-[#0a66c2] px-3 py-1 text-sm font-medium text-white">
                    Chercher sur LinkedIn
                </a>
            </div>
            <div className="space-y-3 text-sm">
                <div>
                    <div className="flex items-center justify-between">
                        <div className="text-xs uppercase text-gray-400">
                            Note de connexion <span className={note.length > 300 ? 'text-red-600' : 'text-gray-400'}>({note.length}/300)</span>
                        </div>
                        <button type="button" onClick={() => navigator.clipboard?.writeText(note)}
                            className="text-xs text-indigo-600 underline">Copier</button>
                    </div>
                    <div className="whitespace-pre-line text-gray-600">{note}</div>
                </div>
                <details>
                    <summary className="cursor-pointer text-xs uppercase text-gray-400">Message après connexion</summary>
                    <div className="mt-1 flex items-start gap-2">
                        <div className="whitespace-pre-line text-gray-600">{message}</div>
                        <button type="button" onClick={() => navigator.clipboard?.writeText(message)}
                            className="shrink-0 text-xs text-indigo-600 underline">Copier</button>
                    </div>
                </details>
            </div>
        </div>
    );
}
