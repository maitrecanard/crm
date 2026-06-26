import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';

export default function Activation({ name, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        // L'URL courante porte la signature : on la réutilise pour le POST.
        post(window.location.pathname + window.location.search, {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Activer mon compte" />

            <div className="mb-4 text-sm text-gray-600">
                Bonjour <strong>{name}</strong>, définissez votre mot de passe pour activer
                votre accès partenaire ({email}).
            </div>

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="password" value="Mot de passe" />
                    <TextInput id="password" type="password" name="password" value={data.password}
                        className="mt-1 block w-full" autoComplete="new-password" isFocused={true}
                        onChange={(e) => setData('password', e.target.value)} />
                    <InputError message={errors.password} className="mt-2" />
                    <p className="mt-1 text-xs text-gray-500">
                        Au moins 12 caractères, avec majuscule, minuscule, chiffre et symbole.
                    </p>
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password_confirmation" value="Confirmer le mot de passe" />
                    <TextInput id="password_confirmation" type="password" name="password_confirmation"
                        value={data.password_confirmation} className="mt-1 block w-full" autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)} />
                    <InputError message={errors.password_confirmation} className="mt-2" />
                </div>

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton className="ms-4" disabled={processing}>
                        Activer mon compte
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
