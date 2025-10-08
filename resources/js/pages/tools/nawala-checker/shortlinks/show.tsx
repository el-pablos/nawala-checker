import React from 'react';
import { Head, Link, router } from '@inertiajs/react';

interface ShortlinkTarget {
    id: number;
    url: string;
    priority: number;
    weight: number;
    is_active: boolean;
    is_current: boolean;
}

interface RotationHistory {
    id: number;
    from_target_id: number | null;
    to_target_id: number;
    reason: string;
    rotated_at: string;
    from_target: {
        url: string;
    } | null;
    to_target: {
        url: string;
    };
}

interface Shortlink {
    id: number;
    slug: string;
    is_active: boolean;
    current_target_id: number | null;
    group: {
        id: number;
        name: string;
        rotation_threshold: number;
        rotation_cooldown: number;
    };
    current_target: {
        id: number;
        url: string;
    } | null;
    targets: ShortlinkTarget[];
    rotation_history: RotationHistory[];
}

interface ShowShortlinkProps {
    shortlink: Shortlink;
}

export default function ShowShortlink({ shortlink }: ShowShortlinkProps) {
    const handleRotate = () => {
        if (confirm('Are you sure you want to force rotate this shortlink?')) {
            router.post(`/nawala-checker/shortlinks/${shortlink.id}/rotate`);
        }
    };

    const handleRollback = () => {
        if (confirm('Are you sure you want to rollback to the original target?')) {
            router.post(`/nawala-checker/shortlinks/${shortlink.id}/rollback`);
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this shortlink? This action cannot be undone.')) {
            router.delete(`/nawala-checker/shortlinks/${shortlink.id}`);
        }
    };

    const StatusDot = ({ active }: { active: boolean }) => (
        <span className={`inline-block w-2 h-2 rounded-full ${active ? 'bg-green-500' : 'bg-gray-400'}`} />
    );

    return (
        <>
            <Head title={`/${shortlink.slug} - Nawala Checker`} />

            <div className="min-h-screen bg-gray-100 py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href="/nawala-checker/shortlinks"
                            className="text-sm text-blue-600 hover:text-blue-800 mb-2 inline-block"
                        >
                            ← Back to Shortlinks
                        </Link>
                        <div className="flex justify-between items-start">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">/{shortlink.slug}</h1>
                                <p className="mt-2 text-sm text-gray-600">
                                    Group: {shortlink.group.name}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <button
                                    onClick={handleRotate}
                                    className="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 text-sm"
                                >
                                    Force Rotate
                                </button>
                                <button
                                    onClick={handleRollback}
                                    className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm"
                                >
                                    Rollback
                                </button>
                                <button
                                    onClick={handleDelete}
                                    className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Current Target */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Current Target</h2>
                                {shortlink.current_target ? (
                                    <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                        <div className="flex items-center gap-2 mb-2">
                                            <StatusDot active={true} />
                                            <span className="text-sm font-medium text-green-800">Active</span>
                                        </div>
                                        <a
                                            href={shortlink.current_target.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-blue-600 hover:text-blue-800 break-all"
                                        >
                                            {shortlink.current_target.url}
                                        </a>
                                    </div>
                                ) : (
                                    <p className="text-gray-500 text-sm">No target currently active</p>
                                )}
                            </div>

                            {/* All Targets */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">All Targets</h2>
                                <div className="space-y-3">
                                    {shortlink.targets.map((target) => (
                                        <div
                                            key={target.id}
                                            className={`border rounded-lg p-4 ${
                                                target.is_current
                                                    ? 'border-green-500 bg-green-50'
                                                    : 'border-gray-200'
                                            }`}
                                        >
                                            <div className="flex items-start justify-between mb-2">
                                                <div className="flex items-center gap-2">
                                                    <StatusDot active={target.is_active} />
                                                    <span className="text-xs font-medium text-gray-600">
                                                        Priority: {target.priority} | Weight: {target.weight}
                                                    </span>
                                                </div>
                                                {target.is_current && (
                                                    <span className="px-2 py-1 bg-green-600 text-white text-xs rounded-full">
                                                        Current
                                                    </span>
                                                )}
                                            </div>
                                            <a
                                                href={target.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-blue-600 hover:text-blue-800 text-sm break-all"
                                            >
                                                {target.url}
                                            </a>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            {/* Rotation History */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">Rotation History</h2>
                                {shortlink.rotation_history.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Date
                                                    </th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                        From
                                                    </th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                        To
                                                    </th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                                        Reason
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-200">
                                                {shortlink.rotation_history.map((history) => (
                                                    <tr key={history.id}>
                                                        <td className="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                                                            {new Date(history.rotated_at).toLocaleString()}
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-600">
                                                            {history.from_target?.url || 'N/A'}
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-900">
                                                            {history.to_target.url}
                                                        </td>
                                                        <td className="px-4 py-3 text-sm text-gray-600">
                                                            {history.reason}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <p className="text-gray-500 text-sm">No rotation history yet</p>
                                )}
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Status */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h3 className="text-sm font-semibold text-gray-900 mb-3">Status</h3>
                                <div className="space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Active:</span>
                                        <span className={shortlink.is_active ? 'text-green-600' : 'text-red-600'}>
                                            {shortlink.is_active ? 'Yes' : 'No'}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Total Targets:</span>
                                        <span className="text-gray-900">{shortlink.targets.length}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Active Targets:</span>
                                        <span className="text-gray-900">
                                            {shortlink.targets.filter(t => t.is_active).length}
                                        </span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Rotations:</span>
                                        <span className="text-gray-900">{shortlink.rotation_history.length}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Group Settings */}
                            <div className="bg-white shadow rounded-lg p-6">
                                <h3 className="text-sm font-semibold text-gray-900 mb-3">Group Settings</h3>
                                <div className="space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Threshold:</span>
                                        <span className="text-gray-900">{shortlink.group.rotation_threshold}%</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Cooldown:</span>
                                        <span className="text-gray-900">{shortlink.group.rotation_cooldown}s</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

