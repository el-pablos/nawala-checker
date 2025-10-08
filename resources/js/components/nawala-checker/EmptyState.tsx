import React from 'react';
import { Link } from '@inertiajs/react';

interface EmptyStateProps {
    icon?: React.ReactNode;
    title: string;
    description: string;
    actionText?: string;
    actionHref?: string;
    onAction?: () => void;
}

export default function EmptyState({
    icon,
    title,
    description,
    actionText,
    actionHref,
    onAction,
}: EmptyStateProps) {
    return (
        <div className="text-center py-12">
            {/* Icon */}
            {icon && (
                <div className="mx-auto h-12 w-12 text-gray-400 mb-4">
                    {icon}
                </div>
            )}

            {/* Title */}
            <h3 className="mt-2 text-sm font-medium text-gray-900">{title}</h3>

            {/* Description */}
            <p className="mt-1 text-sm text-gray-500">{description}</p>

            {/* Action */}
            {(actionText && (actionHref || onAction)) && (
                <div className="mt-6">
                    {actionHref ? (
                        <Link
                            href={actionHref}
                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            {actionText}
                        </Link>
                    ) : (
                        <button
                            type="button"
                            onClick={onAction}
                            className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            {actionText}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

