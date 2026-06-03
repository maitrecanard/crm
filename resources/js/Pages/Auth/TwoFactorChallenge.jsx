import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function TwoFactorChallenge() {
    const [recovery, setRecovery] = useState(false);
    const form = useForm({ code: '', recovery_code: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('two-factor.login'));
    };

    return (
        <GuestLayout>
            <Head title="Vérification 2FA" />

            <div className="mb-4 text-sm text-gray-600">
                {recovery
                    ? 'Saisis l’un de tes codes de récupération.'
                    : 'Saisis le code de ton application d’authentification.'}
            </div>

            <form onSubmit={submit}>
                {!recovery ? (
                    <div>
                        <input
                            value={form.data.code}
                            onChange={(e) => form.setData('code', e.target.value)}
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            autoFocus
                            placeholder="123456"
                            className="block w-full rounded-md border-gray-300 tracking-widest shadow-sm"
                        />
                        <InputError message={form.errors.code} className="mt-2" />
                    </div>
                ) : (
                    <div>
                        <input
                            value={form.data.recovery_code}
                            onChange={(e) => form.setData('recovery_code', e.target.value)}
                            autoComplete="one-time-code"
                            autoFocus
                            placeholder="XXXXX-XXXXX"
                            className="block w-full rounded-md border-gray-300 font-mono shadow-sm"
                        />
                        <InputError message={form.errors.code} className="mt-2" />
                    </div>
                )}

                <div className="mt-4 flex items-center justify-between">
                    <button
                        type="button"
                        onClick={() => setRecovery((v) => !v)}
                        className="text-sm text-gray-600 underline"
                    >
                        {recovery ? 'Utiliser un code de l’app' : 'Utiliser un code de récupération'}
                    </button>
                    <button
                        disabled={form.processing}
                        className="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        Vérifier
                    </button>
                </div>
            </form>
        </GuestLayout>
    );
}
