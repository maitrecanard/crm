import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function TwoFactorForm({ twoFactor, className = '' }) {
    const enableForm = useForm({});
    const disableForm = useForm({});
    const confirmForm = useForm({ code: '' });
    const [showRecovery, setShowRecovery] = useState(false);

    const enable = () => enableForm.post(route('two-factor.enable'), { preserveScroll: true });
    const disable = () => disableForm.delete(route('two-factor.disable'), { preserveScroll: true });
    const confirm = (e) => {
        e.preventDefault();
        confirmForm.post(route('two-factor.confirm'), { preserveScroll: true, onSuccess: () => confirmForm.reset() });
    };

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900">Authentification à deux facteurs (2FA)</h2>
                <p className="mt-1 text-sm text-gray-600">
                    Ajoute une sécurité supplémentaire avec une application d’authentification
                    (Google Authenticator, Authy, 1Password…).
                </p>
            </header>

            {/* Activé */}
            {twoFactor.enabled && (
                <div className="mt-6">
                    <p className="inline-flex items-center rounded bg-green-50 px-3 py-1 text-sm font-medium text-green-700">
                        ✓ 2FA activé
                    </p>
                    {twoFactor.recoveryCodes && (
                        <div className="mt-4">
                            <button type="button" onClick={() => setShowRecovery((v) => !v)}
                                className="text-sm text-indigo-600 underline">
                                {showRecovery ? 'Masquer' : 'Afficher'} les codes de récupération
                            </button>
                            {showRecovery && (
                                <div className="mt-2 grid grid-cols-2 gap-1 rounded bg-gray-50 p-3 font-mono text-xs">
                                    {twoFactor.recoveryCodes.map((c) => <span key={c}>{c}</span>)}
                                </div>
                            )}
                        </div>
                    )}
                    <div className="mt-4">
                        <button onClick={disable} disabled={disableForm.processing}
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            Désactiver le 2FA
                        </button>
                    </div>
                </div>
            )}

            {/* Configuration en cours (secret généré, à confirmer) */}
            {!twoFactor.enabled && twoFactor.pending && (
                <div className="mt-6 space-y-4">
                    <p className="text-sm text-gray-700">
                        1. Scanne ce QR code avec ton application, ou saisis la clé manuellement.
                    </p>
                    <div className="flex flex-wrap items-center gap-4">
                        <img src={twoFactor.qr} alt="QR code 2FA" width="200" height="200"
                            className="rounded border bg-white p-2" />
                        <div className="text-sm">
                            <div className="text-gray-500">Clé de configuration :</div>
                            <code className="select-all break-all rounded bg-gray-100 px-2 py-1">{twoFactor.secretKey}</code>
                        </div>
                    </div>

                    {twoFactor.recoveryCodes && (
                        <div>
                            <p className="text-sm text-gray-700">2. Conserve ces <b>codes de récupération</b> en lieu sûr :</p>
                            <div className="mt-2 grid grid-cols-2 gap-1 rounded bg-gray-50 p-3 font-mono text-xs">
                                {twoFactor.recoveryCodes.map((c) => <span key={c}>{c}</span>)}
                            </div>
                        </div>
                    )}

                    <form onSubmit={confirm} className="flex items-end gap-2">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">3. Code de confirmation</label>
                            <input value={confirmForm.data.code} onChange={(e) => confirmForm.setData('code', e.target.value)}
                                inputMode="numeric" autoComplete="one-time-code" placeholder="123456"
                                className="mt-1 w-40 rounded-md border-gray-300 text-sm tracking-widest" />
                            {confirmForm.errors.code && <p className="mt-1 text-sm text-red-600">{confirmForm.errors.code}</p>}
                        </div>
                        <button disabled={confirmForm.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            Confirmer
                        </button>
                    </form>
                </div>
            )}

            {/* Désactivé : bouton d'activation */}
            {!twoFactor.enabled && !twoFactor.pending && (
                <div className="mt-6">
                    <button onClick={enable} disabled={enableForm.processing}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                        Activer le 2FA
                    </button>
                </div>
            )}
        </section>
    );
}
