import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';

interface Target {
    id: number;
    domain_or_url: string;
    type: string;
    enabled: boolean;
    current_status: string;
    consecutive_failures: number;
    last_checked_at: string | null;
    created_at: string;
    owner: {
        id: number;
        name: string;
    };
    group: {
        id: number;
        name: string;
    } | null;
    tags: Array<{
        id: number;
        name: string;
        color: string;
    }>;
}

interface TargetsIndexProps {
    targets: {
        data: Target[];
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        enabled?: boolean;
    };
    groups: Array<{ id: number; name: string }>;
    tags: Array<{ id: number; name: string; color: string }>;
    stats: {
        total_targets: number;
        enabled_targets: number;
        blocked_targets: number;
        ok_targets: number;
    };
}

export default function TargetsIndex({ targets, filters, groups, tags, stats }: TargetsIndexProps) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/nawala-checker/targets', { search }, { preserveState: true });
    };

    const handleDelete = (target: Target) => {
        if (confirm(`Hapus target ${target.domain_or_url}?`)) {
            router.delete(`/nawala-checker/targets/${target.id}`);
        }
    };

    const handleToggle = (target: Target) => {
        router.post(`/nawala-checker/targets/${target.id}/toggle`);
    };

    const handleRunCheck = (target: Target) => {
        router.post(`/nawala-checker/targets/${target.id}/run-check`);
    };

    return (
        <>
            <Head title="Targets - Nawala Checker" />

            <div className="min-h-screen bg-gray-100 py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 flex justify-between items-center">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Targets</h1>
                            <p className="mt-2 text-sm text-gray-600">
                                Manage your monitoring targets
                            </p>
                        </div>
                        <Link
                            href="/nawala-checker/targets/create"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                        >
                            + Add Target
                        </Link>
                    </div>

                    {/* Stats */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-4 mb-6">
                        <div className="bg-white overflow-hidden shadow rounded-lg p-5">
                            <div className="text-sm font-medium text-gray-500">Total</div>
                            <div className="mt-1 text-3xl font-semibold text-gray-900">
                                {stats.total_targets}
                            </div>
                        </div>
                        <div className="bg-white overflow-hidden shadow rounded-lg p-5">
                            <div className="text-sm font-medium text-gray-500">Enabled</div>
                            <div className="mt-1 text-3xl font-semibold text-green-600">
                                {stats.enabled_targets}
                            </div>
                        </div>
                        <div className="bg-white overflow-hidden shadow rounded-lg p-5">
                            <div className="text-sm font-medium text-gray-500">Blocked</div>
                            <div className="mt-1 text-3xl font-semibold text-red-600">
                                {stats.blocked_targets}
                            </div>
                        </div>
                        <div className="bg-white overflow-hidden shadow rounded-lg p-5">
                            <div className="text-sm font-medium text-gray-500">OK</div>
                            <div className="mt-1 text-3xl font-semibold text-green-600">
                                {stats.ok_targets}
                            </div>
                        </div>
                    </div>

                    {/* Search & Filters */}
                    <div className="bg-white shadow rounded-lg p-4 mb-6">
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search targets..."
                                className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <button
                                type="submit"
                                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                            >
                                Search
                            </button>
                        </form>
                    </div>

                    {/* Targets Table */}
                    <div className="bg-white shadow rounded-lg overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Target
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Group
                                    </th>
                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Last Checked
                                    </th>
                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white divide-y divide-gray-200">
                                {targets.data.map((target) => (
                                    <tr key={target.id}>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center">
                                                <div>
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {target.domain_or_url}
                                                    </div>
                                                    <div className="text-sm text-gray-500">
                                                        {target.type}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <StatusBadge status={target.current_status} />
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {target.group?.name || '-'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {target.last_checked_at
                                                ? new Date(target.last_checked_at).toLocaleString()
                                                : 'Never'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button
                                                onClick={() => handleRunCheck(target)}
                                                className="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Check
                                            </button>
                                            <button
                                                onClick={() => handleToggle(target)}
                                                className="text-yellow-600 hover:text-yellow-900 mr-3"
                                            >
                                                {target.enabled ? 'Disable' : 'Enable'}
                                            </button>
                                            <Link
                                                href={`/nawala-checker/targets/${target.id}/edit`}
                                                className="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(target)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

function StatusBadge({ status }: { status: string }) {
    const statusConfig: Record<string, { label: string; className: string }> = {
        OK: { label: '✅ OK', className: 'bg-green-100 text-green-800' },
        DNS_FILTERED: { label: '🚫 DNS Filtered', className: 'bg-red-100 text-red-800' },
        HTTP_BLOCKPAGE: { label: '⛔ Block Page', className: 'bg-red-100 text-red-800' },
        HTTPS_SNI_BLOCK: { label: '🔒 SNI Block', className: 'bg-red-100 text-red-800' },
        TIMEOUT: { label: '⏱️ Timeout', className: 'bg-yellow-100 text-yellow-800' },
        RST: { label: '❌ RST', className: 'bg-red-100 text-red-800' },
        UNKNOWN: { label: '❓ Unknown', className: 'bg-gray-100 text-gray-800' },
    };

    const config = statusConfig[status] || statusConfig.UNKNOWN;

    return (
        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${config.className}`}>
            {config.label}
        </span>
    );
}

