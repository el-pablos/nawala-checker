import React from 'react';
import { Link } from '@inertiajs/react';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

interface LayoutProps {
    title: string;
    breadcrumbs?: BreadcrumbItem[];
    actions?: React.ReactNode;
    children: React.ReactNode;
}

export default function Layout({ title, breadcrumbs, actions, children }: LayoutProps) {
    return (
        <div className="min-h-screen bg-gray-100 py-6">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {/* Breadcrumbs */}
                {breadcrumbs && breadcrumbs.length > 0 && (
                    <nav className="mb-4" aria-label="Breadcrumb">
                        <ol className="flex items-center space-x-2 text-sm">
                            {breadcrumbs.map((item, index) => (
                                <li key={index} className="flex items-center">
                                    {index > 0 && (
                                        <svg
                                            className="w-4 h-4 mx-2 text-gray-400"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fillRule="evenodd"
                                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                clipRule="evenodd"
                                            />
                                        </svg>
                                    )}
                                    {item.href ? (
                                        <Link
                                            href={item.href}
                                            className="text-blue-600 hover:text-blue-800"
                                        >
                                            {item.label}
                                        </Link>
                                    ) : (
                                        <span className="text-gray-500">{item.label}</span>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </nav>
                )}

                {/* Header */}
                <div className="mb-6">
                    <div className="flex justify-between items-center">
                        <h1 className="text-3xl font-bold text-gray-900">{title}</h1>
                        {actions && <div className="flex gap-2">{actions}</div>}
                    </div>
                </div>

                {/* Content */}
                {children}
            </div>
        </div>
    );
}

