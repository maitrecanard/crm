import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword, googleEnabled }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value="Email" />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value="Password" />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-gray-600">
                            Remember me
                        </span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-end">
                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Forgot your password?
                        </Link>
                    )}

                    <PrimaryButton className="ms-4" disabled={processing}>
                        Log in
                    </PrimaryButton>
                </div>
            </form>

            {googleEnabled && (
                <>
                    <div className="my-6 flex items-center gap-3 text-xs text-gray-400">
                        <span className="h-px flex-1 bg-gray-200" /> ou <span className="h-px flex-1 bg-gray-200" />
                    </div>
                    <a
                        href={route('google.redirect')}
                        className="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        <svg className="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.24 1.3-.97 2.4-2.06 3.14v2.6h3.33C20.7 18.1 21.6 15.3 21.6 12c0-.66-.06-1.3-.17-1.9H12z" />
                            <path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.62-2.43l-3.33-2.6c-.92.62-2.1.98-3.29.98-2.53 0-4.67-1.7-5.44-4H3.13v2.68C4.78 19.98 8.13 22 12 22z" />
                            <path fill="#FBBC05" d="M6.56 13.95A5.99 5.99 0 0 1 6.24 12c0-.68.12-1.34.32-1.95V7.37H3.13A9.98 9.98 0 0 0 2 12c0 1.62.39 3.15 1.13 4.63l3.43-2.68z" />
                            <path fill="#4285F4" d="M12 6.05c1.47 0 2.78.5 3.82 1.5l2.85-2.85C16.96 2.99 14.7 2 12 2 8.13 2 4.78 4.02 3.13 7.37l3.43 2.68c.77-2.3 2.91-4 5.44-4z" />
                        </svg>
                        Se connecter avec Google
                    </a>
                </>
            )}
        </GuestLayout>
    );
}
