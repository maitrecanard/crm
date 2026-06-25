import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const page = usePage().props;
    const user = page.auth.user;
    const flash = page.flash || {};
    const isPartenaire = user.role === 'partenaire';
    const notifications = page.notifications || [];

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    const markAllRead = () => router.post(route('notifications.read'), {}, { preserveScroll: true });

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {isPartenaire ? (
                                    <NavLink
                                        href={route('portail.index')}
                                        active={route().current('portail.*')}
                                    >
                                        Mon espace
                                    </NavLink>
                                ) : (
                                    <>
                                        <NavLink
                                            href={route('dashboard')}
                                            active={route().current('dashboard')}
                                        >
                                            Tableau de bord
                                        </NavLink>
                                        <NavLink
                                            href={route('prospects.index')}
                                            active={route().current('prospects.*')}
                                        >
                                            Prospects
                                        </NavLink>
                                        <NavLink
                                            href={route('ao.index')}
                                            active={route().current('ao.*')}
                                        >
                                            Appels d’offres
                                        </NavLink>
                                        <NavLink
                                            href={route('clients.index')}
                                            active={route().current('clients.*')}
                                        >
                                            Clients
                                        </NavLink>
                                        <NavLink
                                            href={route('projects.index')}
                                            active={route().current('projects.*')}
                                        >
                                            Projets
                                        </NavLink>
                                        <NavLink
                                            href={route('partenaires.index')}
                                            active={route().current('partenaires.*')}
                                        >
                                            Partenaires
                                        </NavLink>
                                        <NavLink
                                            href={route('parametres.edit')}
                                            active={route().current('parametres.*')}
                                        >
                                            Paramètres
                                        </NavLink>
                                    </>
                                )}
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center">
                            <div className="relative me-3">
                                <Dropdown>
                                        <Dropdown.Trigger>
                                            <button type="button"
                                                className="relative inline-flex items-center rounded-md p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                                                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                </svg>
                                                {notifications.length > 0 && (
                                                    <span className="absolute right-1 top-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                                                        {notifications.length}
                                                    </span>
                                                )}
                                            </button>
                                        </Dropdown.Trigger>
                                        <Dropdown.Content width="48">
                                            <div className="px-4 py-2 text-xs font-semibold uppercase text-gray-400">
                                                Notifications
                                            </div>
                                            {notifications.length ? (
                                                <>
                                                    {notifications.map((n) => (
                                                        <Link key={n.id} href={n.url || '#'}
                                                            className="block border-t border-gray-100 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                            {n.titre}
                                                        </Link>
                                                    ))}
                                                    <button onClick={markAllRead}
                                                        className="block w-full border-t border-gray-100 px-4 py-2 text-left text-xs text-indigo-600 hover:bg-gray-50">
                                                        Tout marquer comme lu
                                                    </button>
                                                </>
                                            ) : (
                                                <div className="border-t border-gray-100 px-4 py-3 text-sm text-gray-400">
                                                    Aucune notification.
                                                </div>
                                            )}
                                        </Dropdown.Content>
                                    </Dropdown>
                                </div>
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        {isPartenaire ? (
                            <ResponsiveNavLink
                                href={route('portail.index')}
                                active={route().current('portail.*')}
                            >
                                Mon espace
                            </ResponsiveNavLink>
                        ) : (
                            <>
                                <ResponsiveNavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    Tableau de bord
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('prospects.index')}
                                    active={route().current('prospects.*')}
                                >
                                    Prospects
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('ao.index')}
                                    active={route().current('ao.*')}
                                >
                                    Appels d’offres
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('clients.index')}
                                    active={route().current('clients.*')}
                                >
                                    Clients
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('projects.index')}
                                    active={route().current('projects.*')}
                                >
                                    Projets
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('partenaires.index')}
                                    active={route().current('partenaires.*')}
                                >
                                    Partenaires
                                </ResponsiveNavLink>
                            </>
                        )}
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            {(flash.success || flash.error) && (
                <div className="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                    <div className={`rounded-md border p-3 text-sm ${flash.error
                        ? 'border-red-200 bg-red-50 text-red-800'
                        : 'border-green-200 bg-green-50 text-green-800'}`}>
                        {flash.error || flash.success}
                    </div>
                </div>
            )}

            <main>{children}</main>
        </div>
    );
}
