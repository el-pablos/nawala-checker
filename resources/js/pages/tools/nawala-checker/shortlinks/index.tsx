import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';

interface ShortlinkTarget {
    id: number;
    url: string;
    priority: number;
    is_active: boolean;
    current_status: string;
}

interface Shortlink {
    id: number;
    slug: string;
    is_active: boolean;
    rotation_count: number;
    last_rotated_at: string | null;
    created_at: string;
    group: {
        id: number;
        name: string;
        rotation_threshold: number;
        cooldown_seconds: number;
    };
    current_target: ShortlinkTarget | null;
}

interface ShortlinksIndexProps {
    shortlinks: {
        data: Shortlink[];
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
        is_active?: boolean;
    };
    groups: Array<{ id: number; name: string }>;
}

export default function ShortlinksIndex({ shortlinks, filters, groups }: ShortlinksIndexProps) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/nawala-checker/shortlinks', { search }, { preserveState: true });
    };

    const handleDelete = (shortlink: Shortlink) => {
        if (confirm(`Hapus shortlink ${shortlink.slug}?`)) {
            router.delete(`/nawala-checker/shortlinks/${shortlink.id}`);
        }
    };

    const handleRotate = (shortlink: Shortlink) => {
        if (confirm(`Rotate shortlink ${shortlink.slug} sekarang?`)) {
            router.post(`/nawala-checker/shortlinks/${shortlink.id}/rotate`);
        }
    };

    const handleRollback = (shortlink: Shortlink) => {
        if (confirm(`Rollback shortlink ${shortlink.slug} ke target original?`)) {
            router.post(`/nawala-checker/shortlinks/${shortlink.id}/rollback`);
        }
    };

    return (
        <>
            <Head title="Shortlinks - Nawala Checker" />

            <div className="min-h-screen bg-gray-100 py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 flex justify-between items-center">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Shortlinks</h1>
                            <p className="mt-2 text-sm text-gray-600">
                                Manage auto-rotating shortlinks
                            </p>
                        </div>
                        <Link
                            href="/nawala-checker/shortlinks/create"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                        >
                            + Add Shortlink
                        </Link>
                    </div>

                    {/* Search */}
                    <div className="bg-white shadow rounded-lg p-4 mb-6">
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search shortlinks..."
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

                    {/* Shortlinks Grid */}
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {shortlinks.data.map((shortlink) => (
                            <div key={shortlink.id} className="bg-white shadow rounded-lg overflow-hidden">
                                <div className="p-6">
                                    {/* Slug */}
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-semibold text-gray-900">
                                            /{shortlink.slug}
                                        </h3>
                                        {shortlink.is_active ? (
                                            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        ) : (
                                            <span className="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        )}
                                    </div>

                                    {/* Current Target */}
                                    <div className="mb-4">
                                        <p className="text-xs text-gray-500 mb-1">Current Target:</p>
                                        {shortlink.current_target ? (
                                            <div className="flex items-center gap-2">
                                                <p className="text-sm text-gray-900 truncate">
                                                    {shortlink.current_target.url}
                                                </p>
                                                <StatusDot status={shortlink.current_target.current_status} />
                                            </div>
                                        ) : (
                                            <p className="text-sm text-gray-500">No target set</p>
                                        )}
                                    </div>

                                    {/* Stats */}
                                    <div className="grid grid-cols-2 gap-4 mb-4 pb-4 border-b">
                                        <div>
                                            <p className="text-xs text-gray-500">Rotations</p>
                                            <p className="text-lg font-semibold text-gray-900">
                                                {shortlink.rotation_count}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-gray-500">Last Rotated</p>
                                            <p className="text-sm text-gray-900">
                                                {shortlink.last_rotated_at
                                                    ? new Date(shortlink.last_rotated_at).toLocaleDateString()
                                                    : 'Never'}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Group Info */}
                                    <div className="mb-4">
                                        <p className="text-xs text-gray-500">Group: {shortlink.group.name}</p>
                                        <p className="text-xs text-gray-500">
                                            Threshold: {shortlink.group.rotation_threshold} failures
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            Cooldown: {Math.floor(shortlink.group.cooldown_seconds / 60)} minutes
                                        </p>
                                    </div>

                                    {/* Actions */}
                                    <div className="flex flex-col gap-2">
                                        <Link
                                            href={`/nawala-checker/shortlinks/${shortlink.id}`}
                                            className="w-full text-center px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                                        >
                                            View Details
                                        </Link>
                                        <div className="grid grid-cols-2 gap-2">
                                            <button
                                                onClick={() => handleRotate(shortlink)}
                                                className="px-3 py-2 bg-yellow-600 text-white text-sm rounded-md hover:bg-yellow-700"
                                            >
                                                🔄 Rotate
                                            </button>
                                            <button
                                                onClick={() => handleRollback(shortlink)}
                                                className="px-3 py-2 bg-purple-600 text-white text-sm rounded-md hover:bg-purple-700"
                                            >
                                                ↩️ Rollback
                                            </button>
                                        </div>
                                        <button
                                            onClick={() => handleDelete(shortlink)}
                                            className="w-full px-3 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Empty State */}
                    {shortlinks.data.length === 0 && (
                        <div className="bg-white shadow rounded-lg p-12 text-center">
                            <p className="text-gray-500 mb-4">No shortlinks found</p>
                            <Link
                                href="/nawala-checker/shortlinks/create"
                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                Create Your First Shortlink
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

function StatusDot({ status }: { status: string }) {
    const statusColors: Record<string, string> = {
        OK: 'bg-green-500',
        DNS_FILTERED: 'bg-red-500',
        HTTP_BLOCKPAGE: 'bg-red-500',
        HTTPS_SNI_BLOCK: 'bg-red-500',
        TIMEOUT: 'bg-yellow-500',
        RST: 'bg-red-500',
        UNKNOWN: 'bg-gray-500',
    };

    const color = statusColors[status] || statusColors.UNKNOWN;

    return (
        <span
            className={`inline-block w-2 h-2 rounded-full ${color}`}
            title={status}
        />
    );
}

