<?php

return [
    'open' => 'Notifications, :count unread',
    'panel_title' => 'Notifications',
    'empty' => 'You are all caught up.',
    'mark_all_read' => 'Mark all read',
    'dismiss' => 'Dismiss notification',

    'leave_submitted_title' => 'Leave request submitted',
    'leave_submitted_body' => ':employee requested :type.',
    'leave_reviewed_title' => 'Leave request updated',
    'leave_reviewed_body' => 'Your :type request was :status.',

    'expense_submitted_title' => 'Expense claim submitted',
    'expense_submitted_body' => ':employee submitted :title (:amount).',
    'expense_reviewed_title' => 'Expense claim updated',
    'expense_reviewed_body' => 'Your claim “:title” was :status.',

    'admin_suspended_title' => 'Suspended organizations',
    'admin_suspended_body' => '{1} :count organization is suspended.|[2,*] :count organizations are suspended.',
    'admin_trial_expiring_title' => 'Trials expiring soon',
    'admin_trial_expiring_body' => '{1} :count trial expires within 7 days.|[2,*] :count trials expire within 7 days.',
    'admin_new_org_title' => 'New organization',
    'admin_new_org_body' => ':name joined the platform.',
];
