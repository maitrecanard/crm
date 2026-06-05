import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create() {
    const form = useForm({
        entreprise: '', localite: '', telephone: '', email: '', secteur: '', notes: '',
    });
    const submit = (e) => { e.preventDefault(); form.post(route('clients.store')); };

    const field = (name, label, type = 'text') => (
        <div>
            <label className="block text-sm font-medium text-gray-700">{label}</label>
            <input type={type} value={form.data[name]} onChange={(e) => form.setData(name, e.target.value)}
                className="mt-1 w-full rounded-md border-gray-300 text-sm" />
            {form.errors[name] && <p className="mt-1 text-xs text-red-600">{form.errors[name]}</p>}
        </div>
    );

    return (
        <AuthenticatedLayout header={
            <div className="flex items-center gap-3">
                <Link href={route('clients.index')} className="text-gray-400 hover:text-gray-600">← Clients</Link>
                <h2 className="text-xl font-semibold text-gray-800">Nouveau client</h2>
            </div>
        }>
            <Head title="Nouveau client" />
            <div className="mx-auto max-w-2xl p-4 sm:p-6 lg:p-8">
                <form onSubmit={submit} className="space-y-4 rounded-lg bg-white p-6 shadow">
                    {field('entreprise', 'Entreprise *')}
                    <div className="grid grid-cols-2 gap-4">
                        {field('localite', 'Localité')}
                        {field('secteur', 'Secteur')}
                        {field('telephone', 'Téléphone')}
                        {field('email', 'Email', 'email')}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea rows="4" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 text-sm" />
                    </div>
                    <div className="flex items-center justify-end gap-3">
                        <Link href={route('clients.index')} className="text-sm text-gray-500 hover:text-gray-700">Annuler</Link>
                        <button disabled={form.processing || !form.data.entreprise}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            Créer le client
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
