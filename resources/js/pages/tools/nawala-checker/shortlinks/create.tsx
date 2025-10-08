import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

interface Group {
    id: number;
    name: string;
    slug: string;
}

interface CreateShortlinkProps {
    groups: Group[];
}

interface TargetInput {
    url: string;
    priority: number;
    weight: number;
    is_active: boolean;
}

interface FormData {
    slug: string;
    group_id: number | null;
    is_active: boolean;
    targets: TargetInput[];
}

export default function CreateShortlink({ groups }: CreateShortlinkProps) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        slug: '',
        group_id: null,
        is_active: true,
        targets: [
            { url: '', priority: 1, weight: 100, is_active: true },
            { url: '', priority: 2, weight: 100, is_active: true },
        ],
    });

    const addTarget = () => {
        setData('targets', [
            ...data.targets,
            { url: '', priority: data.targets.length + 1, weight: 100, is_active: true },
        ]);
    };

    const removeTarget = (index: number) => {
        if (data.targets.length > 2) {
            setData('targets', data.targets.filter((_, i) => i !== index));
        }
    };

    const updateTarget = (index: number, field: keyof TargetInput, value: any) => {
        const newTargets = [...data.targets];
        newTargets[index] = { ...newTargets[index], [field]: value };
        setData('targets', newTargets);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/nawala-checker/shortlinks');
    };

    return (
        <>
            <Head title="Add Shortlink - Nawala Checker" />

            <div className="min-h-screen bg-gray-100 py-6">
                <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href="/nawala-checker/shortlinks"
                            className="text-sm text-blue-600 hover:text-blue-800 mb-2 inline-block"
                        >
                            ← Back to Shortlinks
                        </Link>
                        <h1 className="text-3xl font-bold text-gray-900">Add New Shortlink</h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Create a shortlink with auto-rotation capability
                        </p>
                    </div>

                    {/* Form */}
                    <div className="bg-white shadow rounded-lg p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Slug */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Slug *
                                </label>
                                <div className="mt-1 flex rounded-md shadow-sm">
                                    <span className="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                        /
                                    </span>
                                    <input
                                        type="text"
                                        value={data.slug}
                                        onChange={(e) => setData('slug', e.target.value)}
                                        placeholder="my-shortlink"
                                        className="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                                <p className="mt-1 text-xs text-gray-500">
                                    Lowercase, alphanumeric, and hyphens only
                                </p>
                                {errors.slug && (
                                    <p className="mt-1 text-sm text-red-600">{errors.slug}</p>
                                )}
                            </div>

                            {/* Group */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Group *
                                </label>
                                <select
                                    value={data.group_id || ''}
                                    onChange={(e) => setData('group_id', e.target.value ? parseInt(e.target.value) : null)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Select a group</option>
                                    {groups.map((group) => (
                                        <option key={group.id} value={group.id}>
                                            {group.name}
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 text-xs text-gray-500">
                                    Group determines rotation threshold and cooldown settings
                                </p>
                                {errors.group_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.group_id}</p>
                                )}
                            </div>

                            {/* Targets */}
                            <div>
                                <div className="flex justify-between items-center mb-3">
                                    <label className="block text-sm font-medium text-gray-700">
                                        Targets * (minimum 2)
                                    </label>
                                    <button
                                        type="button"
                                        onClick={addTarget}
                                        className="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        + Add Target
                                    </button>
                                </div>

                                <div className="space-y-4">
                                    {data.targets.map((target, index) => (
                                        <div key={index} className="border border-gray-200 rounded-lg p-4">
                                            <div className="flex justify-between items-start mb-3">
                                                <h4 className="text-sm font-medium text-gray-900">
                                                    Target #{index + 1}
                                                </h4>
                                                {data.targets.length > 2 && (
                                                    <button
                                                        type="button"
                                                        onClick={() => removeTarget(index)}
                                                        className="text-sm text-red-600 hover:text-red-800"
                                                    >
                                                        Remove
                                                    </button>
                                                )}
                                            </div>

                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="md:col-span-2">
                                                    <label className="block text-xs font-medium text-gray-700">
                                                        URL *
                                                    </label>
                                                    <input
                                                        type="url"
                                                        value={target.url}
                                                        onChange={(e) => updateTarget(index, 'url', e.target.value)}
                                                        placeholder="https://example.com"
                                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700">
                                                        Priority (lower = higher priority)
                                                    </label>
                                                    <input
                                                        type="number"
                                                        value={target.priority}
                                                        onChange={(e) => updateTarget(index, 'priority', parseInt(e.target.value))}
                                                        min="1"
                                                        max="1000"
                                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="block text-xs font-medium text-gray-700">
                                                        Weight (for load balancing)
                                                    </label>
                                                    <input
                                                        type="number"
                                                        value={target.weight}
                                                        onChange={(e) => updateTarget(index, 'weight', parseInt(e.target.value))}
                                                        min="1"
                                                        max="1000"
                                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                    />
                                                </div>

                                                <div className="md:col-span-2">
                                                    <label className="flex items-center">
                                                        <input
                                                            type="checkbox"
                                                            checked={target.is_active}
                                                            onChange={(e) => updateTarget(index, 'is_active', e.target.checked)}
                                                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                        />
                                                        <span className="ml-2 text-xs text-gray-700">
                                                            Active (can be used for rotation)
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {errors.targets && (
                                    <p className="mt-1 text-sm text-red-600">{errors.targets}</p>
                                )}
                            </div>

                            {/* Active */}
                            <div className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label className="ml-2 block text-sm text-gray-900">
                                    Activate shortlink immediately
                                </label>
                            </div>

                            {/* Submit Buttons */}
                            <div className="flex justify-end gap-3 pt-4 border-t">
                                <Link
                                    href="/nawala-checker/shortlinks"
                                    className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                >
                                    {processing ? 'Creating...' : 'Create Shortlink'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}

