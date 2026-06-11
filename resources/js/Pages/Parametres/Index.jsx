import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Parametres({ contratConditions, conditionsDefaut }) {
    const form = useForm({ contrat_conditions: contratConditions || '' });
    const save = (e) => { e.preventDefault(); form.put(route('parametres.update'), { preserveScroll: true }); };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Paramètres</h2>}>
            <Head title="Paramètres" />

            <div className="mx-auto max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">
                <div className="rounded-lg bg-white p-6 shadow">
                    <div className="mb-3 flex items-center justify-between">
                        <h3 className="font-semibold text-gray-800">📄 Modèle de conditions de contrat</h3>
                        {form.recentlySuccessful && <span className="text-xs text-green-600">Enregistré ✓</span>}
                    </div>
                    <p className="mb-3 text-sm text-gray-500">
                        Ce texte sert de base à chaque nouveau contrat. Le modifier ici impacte les
                        <strong> futurs</strong> contrats ; les contrats déjà créés gardent leurs conditions
                        (éditables individuellement sur la fiche client).
                    </p>
                    <textarea rows="20" value={form.data.contrat_conditions}
                        onChange={(e) => form.setData('contrat_conditions', e.target.value)}
                        className="w-full rounded-md border-gray-300 font-mono text-xs" />
                    {form.errors.contrat_conditions && (
                        <p className="mt-1 text-xs text-rose-600">{form.errors.contrat_conditions}</p>
                    )}
                    <div className="mt-3 flex items-center gap-3">
                        <button onClick={save} disabled={form.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            {form.processing ? 'Enregistrement…' : 'Enregistrer le modèle'}
                        </button>
                        <button type="button"
                            onClick={() => form.setData('contrat_conditions', conditionsDefaut)}
                            className="text-xs text-gray-500 hover:text-gray-700">
                            Réinitialiser au modèle par défaut
                        </button>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
