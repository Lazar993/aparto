<?php

return [
    'greeting' => 'Hello :name!',
    'greeting_comma' => 'Hello :name,',
    'salutation' => 'Best regards, :app',

    'fields' => [
        'apartment' => 'Apartment',
        'check_in' => 'Check-in',
        'check_out' => 'Check-out',
        'nights' => 'Nights',
        'total_price' => 'Total Price',
        'deposit_amount' => 'Deposit Amount',
        'balance_due' => 'Balance Due',
        'address' => 'Address',
    ],

    'reservation_created' => [
        'subject' => 'Reservation Confirmation - :apartment',
        'intro' => 'Thank you for your reservation request.',
        'pending_notice' => 'Your reservation is currently pending confirmation. We will review it and get back to you shortly.',
        'account_created_title' => 'Welcome! Your Account Has Been Created',
        'account_created_intro' => 'To access your account and view your reservations, please set your password by clicking the button below:',
        'set_password_action' => 'Set Your Password',
        'set_password_expiry' => 'This link will expire in 60 minutes.',
        'account_created_fallback_title' => 'Account Created',
        'account_created_fallback_body' => 'An account has been created for you. Please use the "Forgot Password" option on the login page to set your password.',
        'outro' => 'If you have any questions, please contact us.',
    ],

    'reservation_confirmed' => [
        'subject' => 'Reservation Confirmed - :apartment',
        'intro' => 'Great news! Your reservation has been confirmed.',
        'outro_one' => 'We look forward to hosting you!',
        'outro_two' => 'If you have any questions or special requests, please do not hesitate to contact us.',
    ],

    'reservation_canceled' => [
        'subject' => 'Reservation Canceled - :apartment',
        'intro' => 'Your reservation has been canceled.',
        'outro' => 'If you did not request this cancellation or have any questions, please contact us immediately.',
    ],

    'review_reminder' => [
        'subject' => 'How Was Your Stay? Leave a Review',
        'intro' => 'Thank you for staying with us.',
        'stay_ended' => 'Your stay at **:apartment** has ended, and we would love to hear your feedback.',
        'impact' => 'Your review helps other guests make better booking decisions.',
        'action' => 'Leave a Review',
        'already_reviewed' => 'Thank you for choosing :apartment again and for already sharing your review.',
        'already_reviewed_outro' => 'We hope to welcome you back again soon.',
        'outro' => 'Thank you for your time and support.',
    ],

    'host_approved' => [
        'subject' => 'Welcome to :app — Your Host Account Is Ready',
        'intro' => 'Congratulations! Your request to become a host on :app has been approved.',
        'account_ready' => 'Your host account has been created. To get started, please set your password by clicking the button below:',
        'set_password_action' => 'Set Your Password',
        'set_password_expiry' => 'This link will expire in 60 minutes. If it expires, you can use the "Forgot Password" option on the login page.',
        'outro' => 'Once you set your password, you can log in to the admin panel and start managing your apartments.',
    ],

    'host_rejected' => [
        'subject' => 'Update on Your Host Application - :app',
        'intro' => 'Thank you for your interest in becoming a host on :app.',
        'rejection_notice' => 'After careful consideration, we regret to inform you that your application to become a host has not been approved at this time.',
        'outro' => 'We appreciate the time and effort you put into your application. If you have any questions or would like feedback on your application, please feel free to contact us.',
    ],

    'host_request_admin' => [
        'subject' => 'New Host Request from :name',
        'greeting' => 'Hello Admin!',
        'intro' => 'A new host request has been submitted.',
        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'city' => 'City',
            'listing_url' => 'Listing URL',
            'apartments' => 'Number of apartments',
        ],
        'action' => 'Review in Admin Panel',
        'outro' => 'Please review and approve or reject this request.',
    ],
];
