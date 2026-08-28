<?php

return [
    'products' => [
        'duplicate' => 'A product with this name already exists.',
        'already_in_household' => 'Household product already exists.',
    ],
    'shopping' => [
        'already_completed' => 'The shopping list item is already completed.',
    ],
    'storage_locations' => [
        'name_required' => 'The storage location name cannot be empty.',
        'contains_stock' => 'Storage location contains stock and cannot be deleted.',
    ],
    'inventory' => [
        'insufficient_stock' => 'Insufficient stock.',
        'unit_incompatible' => 'The unit is not compatible with the product.',
        'quantity_whole' => 'Countable products require a whole-number quantity.',
        'quantity_too_large' => 'The quantity is too large.',
        'quantity_format' => 'The quantity must be a decimal with at most three decimal places.',
        'quantity_negative' => 'The quantity cannot be negative.',
        'threshold_format' => 'The low stock threshold must be a decimal with at most three decimal places.',
        'threshold_negative' => 'The low stock threshold cannot be negative.',
        'threshold_whole' => 'Countable products require a whole-number threshold.',
        'threshold_too_large' => 'The low stock threshold is too big.',
    ],
    'households' => [
        'owner_only_delete' => 'Only the household owner can delete the household.',
        'owner_only_add_members' => 'Only the household owner can add members.',
        'owner_already_member' => 'The owner is already a household member.',
        'user_already_member' => 'The user is already a household member.',
        'owner_only_remove_members' => 'Only the household owner can remove members.',
        'cannot_remove_owner' => 'The household owner cannot be removed through the members endpoint.',
        'cannot_transfer_to_self' => 'The owner cannot transfer ownership to themselves.',
        'current_user_not_owner' => 'The current user is not the household owner.',
        'new_owner_not_member' => 'The new owner is not a household member.',
        'name_required' => 'The household name cannot be empty.',
        'owner_only_update' => 'Only the household owner can update the household.',
    ],
    'password' => [
        'current_mismatch' => 'The provided password does not match your current password.',
    ],
    'mail' => [
        'greeting' => 'Hello!',
        'salutation' => 'Regards,',
        'subcopy' => 'If you are having trouble clicking the ":action" button, copy and paste the URL below into your web browser:',
        'verify' => [
            'subject' => 'Verify your email address',
            'intro' => 'Please click the button below to verify your email address.',
            'action' => 'Verify Email Address',
            'outro' => 'If you did not create an account, no further action is required.',
        ],
        'password_reset' => [
            'subject' => 'Reset your password',
            'intro' => 'You are receiving this email because we received a password reset request for your account.',
            'action' => 'Reset Password',
            'expires' => 'This password reset link will expire in :count minutes.',
            'outro' => 'If you did not request a password reset, no further action is required.',
        ],
    ],
];
