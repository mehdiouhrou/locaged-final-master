<?php

return [
    'types' => [
        'approved' => [
            'type' => 'success',
            'title' => 'Document approved',
            'body' => 'The document ":title" has been approved.',
            'icon' => 'assets/approved.png',
        ],
'created' => [
        'type' => 'success',
        'title' => 'Document created',
        'body' => 'The document ":title" has been created.',
        'icon' => 'assets/created.png',
    ],
    'pending_approval_1w' => [
        'type' => 'pending_approval_1w',
        'title' => 'Approval Reminder',
        'body' => 'The document ":title" has been pending approval for a week.',
        'icon' => 'assets/created.png',
    ],

    'pending_approval_1m' => [
        'type' => 'pending_approval_1m',
        'title' => 'Approval Reminder',
        'body' => 'The document ":title" has been pending approval for a month.',
        'icon' => 'assets/expired.png',
    ],
    'unlocked' => [
            'type' => 'success',
            'title' => 'Document unlocked',
            'body' => 'The document ":title" has been unlocked.',
            'icon' => 'assets/created.png',
        ],
        'expired' => [
            'type' => 'warning',
            'title' => 'Document expired',
            'body' => 'The document ":title" has expired.',
            'icon' => 'assets/expired.png',
        ],
        'locked' => [
            'type' => 'danger',
            'title' => 'Document locked',
            'body' => 'The document ":title" has been locked.',
            'icon' => 'assets/declined.png',
        ],
        'declined' => [
            'type' => 'danger',
            'title' => 'Document declined',
            'body' => 'The document ":title" has been declined.',
            'icon' => 'assets/declined.png',
        ],
        'archived' => [
            'type' => 'danger',
            'title' => 'Document archived',
            'body' => 'The document ":title" has been archived.',
            'icon' => 'assets/declined.png',
        ],
'destroyed' => [
            'type' => 'danger',
            'title' => 'Document destroyed',
            'body' => 'The document ":title" has been destroyed.',
            'icon' => 'assets/declined.png',
        ],
        'permanently_deleted' => [
            'type' => 'danger',
            'title' => 'Document permanently deleted',
            'body' => 'The document ":title" has been permanently deleted from the system.',
            'icon' => 'assets/declined.png',
        ],
        'renamed' => [
            'type' => 'info',
            'title' => 'Document renamed',
            'body' => 'The document ":title" has been renamed.',
            'icon' => 'assets/created.png',
        ],
        'moved' => [
            'type' => 'info',
            'title' => 'Document moved',
            'body' => 'The document ":title" has been moved.',
            'icon' => 'assets/created.png',
        ],
        'collab_reviewer_assigned' => [
            'type' => 'info',
            'title' => 'Review requested',
            'body' => 'You have been assigned as a reviewer for ":title".',
            'icon' => 'assets/created.png',
        ],
        'collab_document_rejected' => [
            'type' => 'danger',
            'title' => 'Document rejected',
            'body' => 'The document ":title" has been rejected by a reviewer.',
            'icon' => 'assets/declined.png',
        ],
        'collab_document_validated' => [
            'type' => 'success',
            'title' => 'Document validated',
            'body' => 'All reviewers have validated ":title". You can now assign a category.',
            'icon' => 'assets/approved.png',
        ],
        'collab_resubmitted' => [
            'type' => 'info',
            'title' => 'Document resubmitted for review',
            'body' => 'The document ":title" has been corrected and resubmitted for your review.',
            'icon' => 'assets/created.png',
        ],
        'collab_document_archived' => [
            'type' => 'success',
            'title' => 'Document archived',
            'body' => 'The document ":title" has been archived.',
            'icon' => 'assets/approved.png',
        ],
    ],
];
