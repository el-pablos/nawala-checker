import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

interface Target {
    id: number;
    domain_or_url: string;
    type: 'domain' | 'url';
    enabled: boolean;
    group_id: number | null;
    check_interval: number | null;
    notes: string | null;
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

interface Group {
    id: number;
    name: string;
    slug: string;
}

interface Tag {
    id: number;
    name: string;
    slug: string;
    color: string;
}

interface EditTargetProps {
    target: Target;
    groups: Group[];
    tags: Tag[];
}

interface FormData {
    domain_or_url: string;
    type: 'domain' | 'url';
    enabled: boolean;
    group_id: number | null;
    check_interval: number | null;
    tags: number[];
    notes: string;
}

export default function EditTarget({ target, groups, tags }: EditTargetProps) {
    const { data, setData, put, processing, errors } = useForm<FormData>({
        domain_or_url: target.domain_or_url,
        type: target.type,
        enabled: target.enabled,
        group_id: target.group_id,
        check_interval: target.check_interval,
        tags: target.tags.map(t => t.id),
        notes: target.notes || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/nawala-checker/targets/${target.id}`);
    };

    const handleTagToggle = (tagId: number) => {
        if (data.tags.includes(tagId)) {
            setData('tags', data.tags.filter(id => id !== tagId));
        } else {
            setData('tags', [...data.tags, tagId]);
        }
    };

    return (
        <>
            <Head title={`Edit ${target.domain_or_url} - Nawala Checker`} />

            <div className="min-h-screen bg-gray-100 py-6">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href="/nawala-checker/targets"
                            className="text-sm text-blue-600 hover:text-blue-800 mb-2 inline-block"
                        >
                            ← Back to Targets
                        </Link>
                        <h1 className="text-3xl font-bold text-gray-900">Edit Target</h1>
                        <p className="mt-2 text-sm text-gray-600">
                            Update target configuration
                        </p>
                    </div>

                    {/* Form */}
                    <div className="bg-white shadow rounded-lg p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Domain/URL */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Domain or URL *
                                </label>
                                <input
                                    type="text"
                                    value={data.domain_or_url}
                                    onChange={(e) => setData('domain_or_url', e.target.value)}
                                    placeholder="example.com or https://example.com"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                {errors.domain_or_url && (
                                    <p className="mt-1 text-sm text-red-600">{errors.domain_or_url}</p>
                                )}
                            </div>

                            {/* Type */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Type *
                                </label>
                                <select
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value as 'domain' | 'url')}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="domain">Domain</option>
                                    <option value="url">URL</option>
                                </select>
                                {errors.type && (
                                    <p className="mt-1 text-sm text-red-600">{errors.type}</p>
                                )}
                            </div>

                            {/* Group */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Group (Optional)
                                </label>
                                <select
                                    value={data.group_id || ''}
                                    onChange={(e) => setData('group_id', e.target.value ? parseInt(e.target.value) : null)}
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">No Group</option>
                                    {groups.map((group) => (
                                        <option key={group.id} value={group.id}>
                                            {group.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.group_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.group_id}</p>
                                )}
                            </div>

                            {/* Check Interval */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Check Interval (seconds)
                                </label>
                                <input
                                    type="number"
                                    value={data.check_interval || ''}
                                    onChange={(e) => setData('check_interval', e.target.value ? parseInt(e.target.value) : null)}
                                    placeholder="Leave empty to use group default"
                                    min="60"
                                    max="86400"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Min: 60 seconds (1 minute), Max: 86400 seconds (24 hours)
                                </p>
                                {errors.check_interval && (
                                    <p className="mt-1 text-sm text-red-600">{errors.check_interval}</p>
                                )}
                            </div>

                            {/* Tags */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Tags (Optional)
                                </label>
                                <div className="flex flex-wrap gap-2">
                                    {tags.map((tag) => (
                                        <button
                                            key={tag.id}
                                            type="button"
                                            onClick={() => handleTagToggle(tag.id)}
                                            className={`px-3 py-1 rounded-full text-sm font-medium transition-colors ${
                                                data.tags.includes(tag.id)
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                            }`}
                                            style={
                                                data.tags.includes(tag.id)
                                                    ? { backgroundColor: tag.color }
                                                    : {}
                                            }
                                        >
                                            {tag.name}
                                        </button>
                                    ))}
                                </div>
                                {errors.tags && (
                                    <p className="mt-1 text-sm text-red-600">{errors.tags}</p>
                                )}
                            </div>

                            {/* Notes */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700">
                                    Notes (Optional)
                                </label>
                                <textarea
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    rows={3}
                                    placeholder="Add any notes about this target..."
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                {errors.notes && (
                                    <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
                                )}
                            </div>

                            {/* Enabled */}
                            <div className="flex items-center">
                                <input
                                    type="checkbox"
                                    checked={data.enabled}
                                    onChange={(e) => setData('enabled', e.target.checked)}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label className="ml-2 block text-sm text-gray-900">
                                    Enable monitoring
                                </label>
                            </div>

                            {/* Submit Buttons */}
                            <div className="flex justify-end gap-3 pt-4 border-t">
                                <Link
                                    href="/nawala-checker/targets"
                                    className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                >
                                    {processing ? 'Updating...' : 'Update Target'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}

