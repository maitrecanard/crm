import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FacturationMensuelle from '@/Components/FacturationMensuelle';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Show({ prospect, statuts, typeOptions, vendeur, facturation }) {
    const vd = vendeur || { societe: 'TechCare Solutions', prenom: '[TON PRÉNOM]', contact: '' };
    const form = useForm({
        statut: prospect.statut,
        prochaine_relance: prospect.prochaine_relance ? prospect.prochaine_relance.substring(0, 10) : '',
        notes: prospect.notes || '',
    });

    const inter = useForm({ type: 'appel', note: '', date: '' });

    const infoForm = useForm({
        entreprise: prospect.entreprise || '',
        email: prospect.email || '',
        telephone: prospect.telephone || '',
        localite: prospect.localite || '',
        secteur: prospect.secteur || '',
    });
    const saveInfo = (e) => {
        e.preventDefault();
        infoForm.put(route('prospects.update', prospect.id), { preserveScroll: true });
    };

    const save = (e) => {
        e.preventDefault();
        form.put(route('prospects.update', prospect.id), { preserveScroll: true });
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

            {prospect.est_client && (
                <div className="mx-auto max-w-5xl px-4 pt-4 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
                        <span className="font-semibold">✓ Client</span>
                        {prospect.client_depuis && <span className="text-emerald-700">depuis le {new Date(prospect.client_depuis).toLocaleDateString('fr-FR')}</span>}
                        <Link href={route('clients.show', prospect.id)}
                            className="rounded-md bg-white px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                            Ouvrir la fiche client →
                        </Link>
                        {prospect.projects?.length > 0 && (
                            <span className="ml-auto flex flex-wrap gap-2">
                                {prospect.projects.map((p) => (
                                    <Link key={p.id} href={route('projects.show', p.id)}
                                        className="rounded-md bg-white px-2 py-1 text-xs text-indigo-600 hover:bg-emerald-100">
                                        📁 {p.titre} →
                                    </Link>
                                ))}
                            </span>
                        )}
                    </div>
                </div>
            )}

            <div className="mx-auto max-w-5xl grid gap-6 p-4 sm:p-6 lg:p-8 md:grid-cols-3">
                {/* Colonne infos */}
                <div className="md:col-span-2 space-y-6">
                    <div className="rounded-lg bg-white p-6 shadow">
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="font-semibold text-gray-800">Coordonnées</h3>
                            {infoForm.recentlySuccessful && <span className="text-xs text-green-600">Enregistré ✓</span>}
                        </div>
                        <div className="grid grid-cols-2 gap-3 text-sm">
                            <Field label="Nom / entreprise" full value={infoForm.data.entreprise}
                                onChange={(v) => infoForm.setData('entreprise', v)} error={infoForm.errors.entreprise} />
                            <Field label="Email" type="email" value={infoForm.data.email}
                                onChange={(v) => infoForm.setData('email', v)} error={infoForm.errors.email} />
                            <Field label="Téléphone" value={infoForm.data.telephone}
                                onChange={(v) => infoForm.setData('telephone', v)} error={infoForm.errors.telephone} />
                            <Field label="Localité" value={infoForm.data.localite}
                                onChange={(v) => infoForm.setData('localite', v)} error={infoForm.errors.localite} />
                            <Field label="Secteur" value={infoForm.data.secteur}
                                onChange={(v) => infoForm.setData('secteur', v)} error={infoForm.errors.secteur} />
                        </div>
                        <div className="mt-3 flex items-center gap-3">
                            <button onClick={saveInfo} disabled={infoForm.processing}
                                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                {infoForm.processing ? 'Enregistrement…' : 'Enregistrer les coordonnées'}
                            </button>
                            <span className="text-xs text-gray-400">
                                Catégorie : {prospect.categorie || '—'} · Source : {prospect.source_fichier}
                            </span>
                        </div>
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

                    {/* Surveillance de facturation mensuelle */}
                    {facturation && <FacturationMensuelle prospect={prospect} facturation={facturation} />}

                    {/* Scénario d'appel téléphonique */}
                    <ScenarioAppel prospect={prospect} vd={vd} />

                    {/* Scénario d'email */}
                    <ScenarioEmail prospect={prospect} vd={vd} />

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
                        <button type="submit" disabled={form.processing}
                            className="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            {form.processing ? 'Enregistrement…' : 'Enregistrer'}
                        </button>
                        {form.recentlySuccessful && <p className="text-sm text-green-600">Enregistré ✓</p>}
                        {Object.values(form.errors).map((err, i) => (
                            <p key={i} className="text-sm text-red-600">{err}</p>
                        ))}
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

// Champ éditable des coordonnées.
function Field({ label, value, onChange, error, type = 'text', full = false }) {
    return (
        <div className={full ? 'col-span-2' : ''}>
            <label className="block text-xs uppercase text-gray-400">{label}</label>
            <input type={type} value={value} onChange={(e) => onChange(e.target.value)}
                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
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

// Carte de scénario éditable et enregistrable (commune aux 3 canaux).
function ScenarioEditable({ prospect, slug, titre, defaut, actions, hint }) {
    const saved = prospect.scenarios?.[slug] ?? null;
    const [text, setText] = useState(saved ?? defaut);
    const [busy, setBusy] = useState(false);
    const dirty = text !== (saved ?? defaut);

    const persist = (value) => {
        setBusy(true);
        router.put(route('prospects.scenarios', prospect.id), { key: slug, value }, {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => setBusy(false),
        });
    };

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <div className="mb-3 flex items-center justify-between gap-2">
                <h3 className="font-semibold text-gray-800">
                    {titre}
                    {saved != null && <span className="ml-2 text-xs font-normal text-green-600">(personnalisé)</span>}
                </h3>
                <div className="flex items-center gap-3">
                    {actions?.(text)}
                    <button type="button" onClick={() => navigator.clipboard?.writeText(text)}
                        className="text-sm text-indigo-600 underline">Copier</button>
                </div>
            </div>
            {hint}
            <textarea value={text} onChange={(e) => setText(e.target.value)}
                rows={Math.min(20, Math.max(6, text.split('\n').length + 1))}
                className="w-full rounded-md border-gray-300 font-mono text-xs leading-relaxed text-gray-700" />
            <div className="mt-2 flex items-center gap-3">
                <button type="button" onClick={() => persist(text)} disabled={!dirty || busy}
                    className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white disabled:opacity-40">
                    Enregistrer
                </button>
                <button type="button" onClick={() => { setText(defaut); persist(null); }} disabled={busy}
                    className="text-sm text-gray-500 underline">Réinitialiser au modèle</button>
            </div>
        </div>
    );
}

function defautAppel(prospect, vd) {
    const v = valueProp(prospect);
    const qui = extraireDecideur(prospect) || 'le responsable';
    return [
        `1. Accroche — « Bonjour, je souhaiterais parler à ${qui}. ${vd.prenom} de ${vd.societe}, vous avez 30 secondes ? »`,
        `2. Raison — « Je me permets de vous appeler car ${v.raison}. Côté ${vd.societe}, ${v.pitch}. »`,
        `3. Question — « ${v.question} »  (laisser parler, écouter le besoin)`,
        `4. RDV — « Le mieux, c’est qu’on prenne 15 min. Plutôt mardi ou jeudi en fin de journée ? »`,
        `5. Objections —\n   • Pas le temps → « 15 min suffisent, je m’adapte à votre agenda. »\n   • Déjà un prestataire → « Je viens en renfort, pas en remplacement. »\n   • Envoyez un mail → « Avec plaisir, et je vous rappelle jeudi ? »\n   • Pas de budget → « On échange 15 min pour le moment venu ? »`,
        `6. Clôture — reformuler le prochain pas, remercier, puis logguer l’appel.`,
    ].join('\n\n');
}

function ScenarioAppel({ prospect, vd }) {
    return (
        <ScenarioEditable
            prospect={prospect} slug="appel" titre="📞 Scénario d’appel"
            defaut={defautAppel(prospect, vd)}
            actions={(text) => prospect.telephone && (
                <a href={`tel:${prospect.telephone.replace(/\s/g, '')}`}
                    className="rounded-md bg-green-600 px-3 py-1 text-sm font-medium text-white">
                    Appeler
                </a>
            )}
            hint={!prospect.telephone && (
                <p className="mb-2 text-xs text-amber-600">Aucun numéro renseigné — à compléter avant l’appel.</p>
            )}
        />
    );
}

// Domaine sous forme NOMINALE (« de l’immobilier », « du droit »…) : marche aussi bien
// après « le secteur … » qu’après « les professionnels … ».
function domaineSecteur(secteur) {
    const s = (secteur || '').toLowerCase();
    if (/immobil/.test(s)) return 'de l’immobilier';
    if (/assur|banque/.test(s)) return 'de l’assurance';
    if (/avocat|juridiqu|notair/.test(s)) return 'du droit';
    if (/architect/.test(s)) return 'de l’architecture';
    if (/comptab|expert-?compt/.test(s)) return 'de l’expertise comptable';
    if (/santé|sante|médico|medico|médical|medical/.test(s)) return 'de la santé';
    if (/hôtel|hotel|restau/.test(s)) return 'de l’hôtellerie-restauration';
    if (/transport|logist/.test(s)) return 'du transport et de la logistique';
    if (/industr/.test(s)) return 'de l’industrie';
    if (/commerce|distrib/.test(s)) return 'du commerce';
    if (/service/.test(s)) return 'des services aux entreprises';
    return null;   // secteur public, inconnu, etc. -> on n’insère pas de domaine
}

// Mot juste pour désigner la structure du prospect (cabinet / agence / étude / entreprise).
function structureMot(secteur) {
    const s = (secteur || '').toLowerCase();
    if (/cabinet/.test(s)) return 'cabinet';
    if (/agence/.test(s)) return 'agence';
    if (/étude|etude|notair/.test(s)) return 'étude';
    return 'entreprise';
}

// Modèle « vitrine / conformité » : on a parcouru le site, repéré un point bloquant,
// et on propose un diagnostic court. Personnalisé par secteur / ville / structure.
function maquetteVitrine(prospect, vd, bonjour) {
    const e = prospect.entreprise;
    const ville = prospect.localite ? ` à ${prospect.localite}` : '';
    const domaine = domaineSecteur(prospect.secteur);
    const specialite = domaine ? ` spécialisé dans le secteur ${domaine}` : '';
    const profDomaine = domaine ? ` ${domaine}` : '';
    const structure = structureMot(prospect.secteur);
    // Accroche par défaut — à VÉRIFIER/ajuster par prospect avant envoi (le scénario est éditable).
    const probleme = 'les mentions légales obligatoires ne sont pas clairement visibles sur votre site';
    const objet = `${e} — un point à vérifier sur votre site`;
    const signature = vd.contact ? `${vd.prenom}\n${vd.societe} · ${vd.contact}` : `${vd.prenom}\n${vd.societe}`;

    const corps =
`${bonjour}

J’ai parcouru votre site internet et j’ai beaucoup aimé vos réalisations${ville}. En tant que développeur${specialite}, j’ai cependant relevé un point qui pourrait poser problème : ${probleme}.

Au-delà du risque réglementaire, c’est un élément indispensable pour rassurer vos futurs clients sur la transparence de votre ${structure}.

J’accompagne les professionnels${profDomaine} à distance pour que leur vitrine web soit à la fois performante visuellement (React/Laravel) et 100 % conforme.

Je serais ravi d’offrir au responsable un rapide diagnostic de 10 minutes, en visio ou par téléphone, pour faire le point sur la sécurité du site et vous donner les clés pour régler ça rapidement.

À qui puis-je m’adresser au sein de l’équipe pour planifier ce court échange la semaine prochaine ?

Excellente journée,
${signature}`;

    const relance =
`${bonjour}

Je me permets un petit up sur mon message précédent. Si la mise en conformité de votre site est un sujet d’actualité chez ${e}, je reste disponible 10 min quand vous voulez — sinon dites-le-moi simplement et je n’insisterai pas. 🙂

${vd.prenom}`;

    return { objet, corps, relance };
}

function scenarioEmail(prospect, vd) {
    const e = prospect.entreprise;
    const d = extraireDecideur(prospect);
    const bonjour = d ? `Bonjour ${d.split(' ')[0]},` : 'Bonjour,';

    // Prospects « vitrine » (pme, artisans, professions libérales…) -> maquette conformité.
    if (!['clients_tech', 'grands_comptes', 'besoins'].includes(prospect.source_fichier)) {
        return maquetteVitrine(prospect, vd, bonjour);
    }

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
    const signature = vd.contact ? `${vd.prenom} · ${vd.contact}` : `${vd.prenom} · [tél · site]`;
    const corps = `${bonjour}\n\n${accroche} Je suis développeur freelance — ${vd.societe} (Laravel/React).\n\n`
        + `${cta}\n\nBonne journée,\n${signature}`;
    const relance = `${bonjour}\n\nJe me permets un petit up — si le sujet « renfort dev » est d’actualité chez ${e}, `
        + 'je reste dispo 15 min quand vous voulez. Sinon dites-moi simplement et je n’insiste pas. 🙂\n\n[TON PRÉNOM]';
    return { objet, corps, relance };
}

function defautEmail(prospect, vd) {
    const { objet, corps } = scenarioEmail(prospect, vd);
    return `Objet : ${objet}\n\n${corps}`;
}

function mailtoDepuisTexte(prospect, text) {
    if (!prospect.email) return null;
    const lines = text.split('\n');
    let subject = '';
    let body = text;
    if (/^\s*objet\s*:/i.test(lines[0])) {
        subject = lines[0].replace(/^\s*objet\s*:\s*/i, '');
        body = lines.slice(1).join('\n').replace(/^\n+/, '');
    }
    return `mailto:${prospect.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
}

function ScenarioEmail({ prospect, vd }) {
    const [busy, setBusy] = useState(false);

    const genererIA = () => {
        setBusy(true);
        router.post(route('prospects.generateEmail', prospect.id), {}, {
            preserveScroll: true, onFinish: () => setBusy(false),
        });
    };
    const envoyer = (text) => {
        if (!confirm(`Envoyer ce mail à ${prospect.email} depuis le CRM ?`)) return;
        router.post(route('prospects.sendEmail', prospect.id), { corps: text }, { preserveScroll: true });
    };

    return (
        <ScenarioEditable
            key={prospect.scenarios?.email ?? 'defaut'}
            prospect={prospect} slug="email" titre="✉️ Scénario d’email"
            defaut={defautEmail(prospect, vd)}
            actions={(text) => (
                <>
                    <button type="button" onClick={genererIA} disabled={busy}
                        className="rounded-md bg-purple-600 px-3 py-1 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50">
                        {busy ? '…' : '✨ Générer (IA)'}
                    </button>
                    {prospect.email && (
                        <button type="button" onClick={() => envoyer(text)}
                            className="rounded-md bg-emerald-600 px-3 py-1 text-sm font-medium text-white hover:bg-emerald-700">
                            📨 Envoyer
                        </button>
                    )}
                </>
            )}
            hint={!prospect.email && (
                <p className="mb-2 text-xs text-amber-600">Aucun email — à trouver (site /contact, LinkedIn).</p>
            )}
        />
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

function defautLinkedIn(prospect) {
    const { note, message } = scenarioLinkedIn(prospect);
    return `Note de connexion (≤ 300 car.) :\n${note}\n\nMessage après connexion :\n${message}`;
}

function ScenarioLinkedIn({ prospect }) {
    const d = extraireDecideur(prospect);
    const recherche = 'https://www.linkedin.com/search/results/people/?keywords='
        + encodeURIComponent(`${d ? d + ' ' : ''}${prospect.entreprise}`);

    return (
        <ScenarioEditable
            prospect={prospect} slug="linkedin" titre="in Scénario LinkedIn"
            defaut={defautLinkedIn(prospect)}
            actions={() => (
                <a href={recherche} target="_blank" rel="noreferrer"
                    className="rounded-md bg-[#0a66c2] px-3 py-1 text-sm font-medium text-white">
                    Chercher
                </a>
            )}
            hint={<p className="mb-2 text-xs text-gray-400">Rappel : la note de connexion est limitée à 300 caractères.</p>}
        />
    );
}

