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
        'outro' => 'Thank you for your time and support.',
    ],
];
