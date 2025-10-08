import React from 'react';
import { Head, Link } from '@inertiajs/react';

interface DashboardProps {
    stats: {
        total_targets: number;
        enabled_targets: number;
        blocked_targets: number;
        ok_targets: number;
        unknown_targets: number;
    };
}

export default function Dashboard({ stats }: DashboardProps) {
    return (
        <>
            <Head title="Nawala Checker - Dashboard" />

            <div className="min-h-screen bg-gray-100 py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-3xl font-bold text-gray-900">
                            Nawala Checker Dashboard
                        </h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Monitor domain/URL blocking status 24/7
                        </p>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-6">
                        <StatCard
                            title="Total Targets"
                            value={stats.total_targets}
                            icon="📊"
                            color="blue"
                        />
                        <StatCard
                            title="Enabled"
                            value={stats.enabled_targets}
                            icon="✅"
                            color="green"
                        />
                        <StatCard
                            title="Blocked"
                            value={stats.blocked_targets}
                            icon="🚫"
                            color="red"
                        />
                        <StatCard
                            title="OK"
                            value={stats.ok_targets}
                            icon="✓"
                            color="green"
                        />
                        <StatCard
                            title="Unknown"
                            value={stats.unknown_targets}
                            icon="❓"
                            color="gray"
                        />
                    </div>

                    {/* Quick Actions */}
                    <div className="bg-white shadow rounded-lg p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Quick Actions
                        </h2>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Link
                                href="/nawala-checker/targets"
                                className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                View Targets
                            </Link>
                            <Link
                                href="/nawala-checker/targets/create"
                                className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                            >
                                Add Target
                            </Link>
                            <Link
                                href="/nawala-checker/shortlinks"
                                className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700"
                            >
                                Shortlinks
                            </Link>
                            <Link
                                href="/nawala-checker/shortlinks/create"
                                className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                            >
                                Add Shortlink
                            </Link>
                        </div>
                    </div>

                    {/* Info Section */}
                    <div className="bg-white shadow rounded-lg p-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            About Nawala Checker
                        </h2>
                        <div className="prose prose-sm text-gray-600">
                            <p>
                                Nawala Checker adalah tool untuk monitoring domain/URL 24/7 untuk mendeteksi
                                pemblokiran (DNS/HTTP/HTTPS/SNI) oleh resolver/ISP termasuk Nawala.
                            </p>
                            <ul className="mt-4 space-y-2">
                                <li>✅ Multi-resolver check (Nawala, Google DNS, Cloudflare, dll)</li>
                                <li>🔄 Auto-rotation shortlink saat target terblokir</li>
                                <li>📱 Telegram notification untuk status changes</li>
                                <li>📊 Riwayat hasil cek & grafik tren</li>
                                <li>🏷️ Groups & Tags untuk pengelompokan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

interface StatCardProps {
    title: string;
    value: number;
    icon: string;
    color: 'blue' | 'green' | 'red' | 'gray';
}

function StatCard({ title, value, icon, color }: StatCardProps) {
    const colorClasses = {
        blue: 'bg-blue-50 text-blue-700',
        green: 'bg-green-50 text-green-700',
        red: 'bg-red-50 text-red-700',
        gray: 'bg-gray-50 text-gray-700',
    };

    return (
        <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
                <div className="flex items-center">
                    <div className={`flex-shrink-0 rounded-md p-3 ${colorClasses[color]}`}>
                        <span className="text-2xl">{icon}</span>
                    </div>
                    <div className="ml-5 w-0 flex-1">
                        <dl>
                            <dt className="text-sm font-medium text-gray-500 truncate">
                                {title}
                            </dt>
                            <dd className="text-2xl font-semibold text-gray-900">
                                {value}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    );
}

