import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

const STATUT_COLORS = {
    nouveau: 'bg-gray-100 text-gray-700',
    en_cours: 'bg-blue-100 text-blue-700',
    en_test: 'bg-violet-100 text-violet-700',
    livre: 'bg-green-100 text-green-700',
    ferme: 'bg-gray-200 text-gray-500',
};
const GRAVITE_STYLE = {
    bloquant: 'bg-red-100 text-red-700',
    majeur: 'bg-amber-100 text-amber-700',
    mineur: 'bg-gray-100 text-gray-600',
};

function dt(d) {
    return d ? new Date(d).toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }) : '';
}

function Meta({ label, children }) {
    return (
        <div className="flex justify-between gap-3 py-1 text-sm">
            <span className="text-gray-500">{label}</span>
            <span className="text-right font-medium text-gray-800">{children ?? '—'}</span>
        </div>
    );
}

/** Un élément de la timeline (création, changement de statut, ou message). */
function Evenement({ item, statuts }) {
    if (item.kind === 'message') {
        const interne = item.interne;
        return (
            <div className={`rounded-lg border p-3 ${interne ? 'border-amber-200 bg-amber-50' : 'border-indigo-100 bg-indigo-50/40'}`}>
                <div className="mb-1 flex items-center justify-between text-xs">
                    <span className={interne ? 'font-medium text-amber-700' : 'font-medium text-indigo-700'}>
                        {interne ? '🔒 Note interne' : '✉️ Message au client'}
                    </span>
                    <span className="text-gray-400">{dt(item.date)}</span>
                </div>
                <p className="whitespace-pre-wrap text-sm text-gray-800">{item.corps}</p>
                {!interne && item.notifie_le && (
                    <p className="mt-1 text-xs text-gray-400">Transmis le {dt(item.notifie_le)}</p>
                )}
            </div>
        );
    }

    const texte = item.kind === 'creation'
        ? `Ticket ouvert (${item.auteur === 'client' ? 'par le client' : 'en interne'})`
        : `Statut : ${statuts[item.ancien] ?? item.ancien ?? '—'} → ${statuts[item.nouveau] ?? item.nouveau}`;

    return (
        <div className="flex items-center gap-2 px-1 text-xs text-gray-500">
            <span className="h-2 w-2 rounded-full bg-gray-300" />
            <span>{texte}</span>
            <span className="text-gray-300">·</span>
            <span className="text-gray-400">{dt(item.date)}</span>
        </div>
    );
}

export default function Show({ ticket, historique, destinataires = [], statuts, types, gravites }) {
    const statutForm = useForm({ statut: ticket.statut });
    const msgForm = useForm({ corps: '', interne: false });

    const changerStatut = (e) => {
        const statut = e.target.value;
        statutForm.setData('statut', statut);
        router.put(route('bugs.update', ticket.id), { statut }, { preserveScroll: true });
    };
    const envoyerMessage = (e) => {
        e.preventDefault();
        msgForm.post(route('bugs.messages.store', ticket.id), {
            preserveScroll: true,
            onSuccess: () => msgForm.reset(),
        });
    };
    const supprimer = () => {
        if (confirm('Supprimer définitivement ce ticket et son historique ?')) {
            router.delete(route('bugs.destroy', ticket.id));
        }
    };

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('tickets.index')} className="text-sm text-indigo-600 hover:underline">← Tickets</Link>
                <h2 className="text-xl font-semibold text-gray-800">
                    <span className="font-mono text-sm text-gray-400">{ticket.reference}</span> — {ticket.titre}
                </h2>
            </div>
        }>
            <Head title={`Ticket ${ticket.reference}`} />
            <div className="mx-auto grid max-w-6xl gap-6 p-4 sm:p-6 lg:grid-cols-3 lg:p-8">

                {/* Colonne principale : description + historique + message */}
                <div className="space-y-6 lg:col-span-2">
                    {ticket.description && (
                        <div className="rounded-lg bg-white p-6 shadow">
                            <h3 className="mb-2 font-semibold text-gray-800">Description</h3>
                            <p className="whitespace-pre-wrap text-sm text-gray-700">{ticket.description}</p>
                        </div>
                    )}

                    {ticket.images?.length > 0 && (
                        <div className="rounded-lg bg-white p-6 shadow">
                            <h3 className="mb-2 font-semibold text-gray-800">Pièces jointes</h3>
                            <div className="flex flex-wrap gap-2">
                                {ticket.images.map((img) => (
                                    <a key={img.id} href={img.url} target="_blank" rel="noreferrer"
                                        className="text-xs text-indigo-600 underline">{img.nom || `image-${img.id}`}</a>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-4 font-semibold text-gray-800">Historique</h3>
                        <div className="space-y-3">
                            {historique.map((item, i) => <Evenement key={i} item={item} statuts={statuts} />)}
                        </div>

                        {/* Ajout d'un message / note interne */}
                        <form onSubmit={envoyerMessage} className="mt-5 space-y-2 border-t border-gray-100 pt-4">
                            <textarea rows="3" required placeholder="Répondre au client ou ajouter une note interne…"
                                value={msgForm.data.corps} onChange={(e) => msgForm.setData('corps', e.target.value)}
                                className="w-full rounded-md border-gray-300 text-sm" />
                            {msgForm.errors.corps && <p className="text-xs text-red-600">{msgForm.errors.corps}</p>}
                            <div className="flex items-center justify-between">
                                <label className="flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" checked={msgForm.data.interne}
                                        onChange={(e) => msgForm.setData('interne', e.target.checked)}
                                        className="rounded border-gray-300" />
                                    Note interne (non transmise au client)
                                </label>
                                <button disabled={msgForm.processing || !msgForm.data.corps}
                                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                    {msgForm.data.interne ? 'Ajouter la note' : 'Envoyer au client'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {/* Colonne latérale : statut + métadonnées */}
                <div className="space-y-6">
                    <div className="rounded-lg bg-white p-6 shadow">
                        <label className="block text-sm font-medium text-gray-700">Statut</label>
                        <select value={statutForm.data.statut} onChange={changerStatut}
                            className="mt-1 w-full rounded-md border-gray-300 text-sm">
                            {Object.entries(statuts).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                        </select>
                        <p className="mt-2 text-xs text-gray-400">Changer le statut notifie automatiquement la liste de diffusion du client.</p>
                        <div className="mt-3 border-t border-gray-100 pt-2">
                            <p className="text-xs font-medium text-gray-500">📬 Destinataires ({destinataires.length})</p>
                            {destinataires.length ? (
                                <ul className="mt-1 space-y-0.5">
                                    {destinataires.map((e) => <li key={e} className="truncate text-xs text-gray-600">{e}</li>)}
                                </ul>
                            ) : (
                                <p className="mt-1 text-xs text-rose-500">Aucun destinataire — ajoutez un contact ou l’e-mail du client.</p>
                            )}
                            {ticket.client && (
                                <Link href={route('clients.show', ticket.client.id)} className="mt-1 inline-block text-xs text-indigo-600 hover:underline">
                                    Gérer les contacts →
                                </Link>
                            )}
                        </div>
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-2 font-semibold text-gray-800">Détails</h3>
                        <Meta label="Statut actuel">
                            <span className={`rounded-full px-2 py-0.5 text-xs ${STATUT_COLORS[ticket.statut] || ''}`}>{ticket.statut_label}</span>
                        </Meta>
                        <Meta label="Type">{types[ticket.type] || ticket.type}</Meta>
                        <Meta label="Gravité">
                            <span className={`rounded px-1.5 py-0.5 text-xs ${GRAVITE_STYLE[ticket.gravite] || ''}`}>{gravites[ticket.gravite] || ticket.gravite}</span>
                        </Meta>
                        {ticket.motif && <Meta label="Motif client">{ticket.motif}</Meta>}
                        {ticket.prochaine_echeance && <Meta label="Prochaine échéance">{new Date(ticket.prochaine_echeance).toLocaleDateString('fr-FR')}</Meta>}
                        {ticket.issue_git && <Meta label="Issue Git">{ticket.issue_git}</Meta>}
                        <Meta label="Ouvert le">{dt(ticket.created_at)}</Meta>
                        {ticket.resolved_at && <Meta label="Résolu le">{dt(ticket.resolved_at)}</Meta>}
                    </div>

                    <div className="rounded-lg bg-white p-6 shadow">
                        <h3 className="mb-2 font-semibold text-gray-800">Client & projet</h3>
                        {ticket.client && (
                            <>
                                <Meta label="Client">
                                    <Link href={route('prospects.show', ticket.client.id)} className="text-indigo-600 hover:underline">
                                        {ticket.client.entreprise}
                                    </Link>
                                </Meta>
                                {ticket.client.email && <Meta label="E-mail">{ticket.client.email}</Meta>}
                                {ticket.client.telephone && <Meta label="Tél.">{ticket.client.telephone}</Meta>}
                            </>
                        )}
                        {ticket.project && (
                            <Meta label="Projet">
                                <Link href={route('projects.show', ticket.project.id)} className="text-indigo-600 hover:underline">
                                    {ticket.project.titre}
                                </Link>
                            </Meta>
                        )}
                    </div>

                    <button onClick={supprimer} className="text-xs text-red-500 hover:underline">Supprimer ce ticket</button>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
