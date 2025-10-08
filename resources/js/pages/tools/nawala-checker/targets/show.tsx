import React from 'react';
import { Head, Link, router } from '@inertiajs/react';

interface Target {
    id: number;
    domain_or_url: string;
    type: string;
    enabled: boolean;
    current_status: string;
    consecutive_failures: number;
    check_interval: number;
    effective_check_interval: number;
    last_checked_at: string | null;
    last_status_change_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    owner: {
        id: number;
        name: string;
        email: string;
    };
    group: {
        id: number;
        name: string;
        slug: string;
    } | null;
    tags: Array<{
        id: number;
        name: string;
        slug: string;
        color: string;
    }>;
}

interface CheckResult {
    id: number;
    status: string;
    response_time_ms: number | null;
    resolved_ip: string | null;
    http_status_code: number | null;
    error_message: string | null;
    confidence: number;
    checked_at: string;
    resolver: {
        id: number;
        name: string;
        type: string;
    };
}

interface Statistics {
    total_checks: number;
    ok_count: number;
    blocked_count: number;
    uptime_percentage: number;
    avg_response_time: number;
}

interface ShowTargetProps {
    target: Target;
    checkResults: CheckResult[];
    statistics: Statistics;
}

export default function ShowTarget({ target, checkResults, statistics }: ShowTargetProps) {
    const handleRunCheck = () => {
        router.post(`/nawala-checker/targets/${target.id}/run-check`);
    };

    const handleToggle = () => {
        router.post(`/nawala-checker/targets/${target.id}/toggle`);
    };

    const handleDelete = () => {
        if (confirm(`Hapus target ${target.domain_or_url}?`)) {
            router.delete(`/nawala-checker/targets/${target.id}`, {
                onSuccess: () => router.visit('/nawala-checker/targets'),
            });
        }
    };

    return (
        <>
            <Head title={`${target.domain_or_url} - Nawala Checker`} />

            <div className="min-h-screen bg-gray-100 py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href="/nawala-checker/targets"
                            className="text-sm text-blue-600 hover:text-blue-800 mb-2 inline-block"
                        >
                            ← Back to Targets
                        </Link>
                        <div className="flex justify-between items-start">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">
                                    {target.domain_or_url}
                                </h1>
                                <p className="mt-2 text-sm text-gray-600">
                                    Target Details & Check History
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    onClick={handleRunCheck}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                >
                                    🔄 Run Check Now
                                </button>
                                <Link
                                    href={`/nawala-checker/targets/${target.id}/edit`}
                                    className="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                                >
                                    ✏️ Edit
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Status Card */}
                    <div className="bg-white shadow rounded-lg p-6 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <p className="text-sm text-gray-500">Current Status</p>
                                <StatusBadge status={target.current_status} large />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">Enabled</p>
                                <p className="text-lg font-semibold text-gray-900">
                                    {target.enabled ? '✅ Yes' : '❌ No'}
                                </p>
                                <button
                                    onClick={handleToggle}
                                    className="mt-1 text-sm text-blue-600 hover:text-blue-800"
                                >
                                    {target.enabled ? 'Disable' : 'Enable'}
                                </button>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">Consecutive Failures</p>
                                <p className="text-lg font-semibold text-gray-900">
                                    {target.consecutive_failures}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-500">Last Checked</p>
                                <p className="text-lg font-semibold text-gray-900">
                                    {target.last_checked_at
                                        ? new Date(target.last_checked_at).toLocaleString()
                                        : 'Never'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Statistics */}
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <StatCard title="Total Checks" value={statistics.total_checks} />
                        <StatCard title="OK Count" value={statistics.ok_count} color="green" />
                        <StatCard title="Blocked Count" value={statistics.blocked_count} color="red" />
                        <StatCard
                            title="Uptime"
                            value={`${statistics.uptime_percentage.toFixed(1)}%`}
                            color="blue"
                        />
                        <StatCard
                            title="Avg Response"
                            value={`${statistics.avg_response_time.toFixed(0)}ms`}
                        />
                    </div>

                    {/* Details */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        {/* Info */}
                        <div className="bg-white shadow rounded-lg p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Information</h2>
                            <dl className="space-y-3">
                                <div>
                                    <dt className="text-sm text-gray-500">Type</dt>
                                    <dd className="text-sm font-medium text-gray-900">{target.type}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-gray-500">Owner</dt>
                                    <dd className="text-sm font-medium text-gray-900">
                                        {target.owner.name} ({target.owner.email})
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-gray-500">Group</dt>
                                    <dd className="text-sm font-medium text-gray-900">
                                        {target.group?.name || 'No Group'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-gray-500">Check Interval</dt>
                                    <dd className="text-sm font-medium text-gray-900">
                                        {target.effective_check_interval} seconds
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-gray-500">Tags</dt>
                                    <dd className="flex flex-wrap gap-2 mt-1">
                                        {target.tags.length > 0 ? (
                                            target.tags.map((tag) => (
                                                <span
                                                    key={tag.id}
                                                    className="px-2 py-1 rounded-full text-xs font-medium text-white"
                                                    style={{ backgroundColor: tag.color }}
                                                >
                                                    {tag.name}
                                                </span>
                                            ))
                                        ) : (
                                            <span className="text-sm text-gray-500">No tags</span>
                                        )}
                                    </dd>
                                </div>
                                {target.notes && (
                                    <div>
                                        <dt className="text-sm text-gray-500">Notes</dt>
                                        <dd className="text-sm text-gray-900">{target.notes}</dd>
                                    </div>
                                )}
                            </dl>
                        </div>

                        {/* Timestamps */}
                        <div className="bg-white shadow rounded-lg p-6">
                            <h2 className="text-lg font-semibold text-gray-900 mb-4">Timestamps</h2>
                            <dl className="space-y-3">
                                <div>
                                    <dt className="text-sm text-gray-500">Created At</dt>
                                    <dd className="text-sm font-medium text-gray-900">
                                        {new Date(target.created_at).toLocaleString()}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-gray-500">Updated At</dt>
                                    <dd className="text-sm font-medium text-gray-900">
                                        {new Date(target.updated_at).toLocaleString()}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-gray-500">Last Status Change</dt>
                                    <dd className="text-sm font-medium text-gray-900">
                                        {target.last_status_change_at
                                            ? new Date(target.last_status_change_at).toLocaleString()
                                            : 'Never'}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Check History */}
                    <div className="bg-white shadow rounded-lg p-6 mb-6">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Recent Check Results (Last 24 hours)
                        </h2>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Time
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Resolver
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            IP
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Response Time
                                        </th>
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Confidence
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {checkResults.map((result) => (
                                        <tr key={result.id}>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {new Date(result.checked_at).toLocaleTimeString()}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {result.resolver.name}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <StatusBadge status={result.status} />
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {result.resolved_ip || '-'}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {result.response_time_ms ? `${result.response_time_ms}ms` : '-'}
                                            </td>
                                            <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                {result.confidence}%
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Danger Zone */}
                    <div className="bg-white shadow rounded-lg p-6 border-2 border-red-200">
                        <h2 className="text-lg font-semibold text-red-900 mb-4">Danger Zone</h2>
                        <button
                            onClick={handleDelete}
                            className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                        >
                            🗑️ Delete Target
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}

function StatusBadge({ status, large = false }: { status: string; large?: boolean }) {
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
    const sizeClass = large ? 'text-lg px-3 py-2' : 'text-xs px-2 py-1';

    return (
        <span className={`inline-flex leading-5 font-semibold rounded-full ${config.className} ${sizeClass}`}>
            {config.label}
        </span>
    );
}

function StatCard({ title, value, color = 'gray' }: { title: string; value: string | number; color?: string }) {
    const colorClasses: Record<string, string> = {
        gray: 'text-gray-900',
        green: 'text-green-600',
        red: 'text-red-600',
        blue: 'text-blue-600',
    };

    return (
        <div className="bg-white shadow rounded-lg p-4">
            <p className="text-sm text-gray-500">{title}</p>
            <p className={`text-2xl font-semibold ${colorClasses[color]}`}>{value}</p>
        </div>
    );
}

