<?php

return [
    'policy_version' => '2026-03-31',

    'otp_length' => 6,
    'otp_ttl_minutes' => 15,
    'otp_max_attempts' => 5,
    'invite_ttl_days' => 7,

    'policies' => [
        [
            'key' => 'privacy_policy',
            'label' => 'Privacy Policy',
            'url' => 'https://www.truckbill.in/policies/privacy-policy.html',
        ],
        [
            'key' => 'terms_and_conditions',
            'label' => 'Terms and Conditions',
            'url' => 'https://www.truckbill.in/policies/terms-and-conditions.html',
        ],
        [
            'key' => 'refund_policy',
            'label' => 'Refund Policy',
            'url' => 'https://www.truckbill.in/policies/refund-policy.html',
        ],
        [
            'key' => 'acceptable_use_policy',
            'label' => 'Acceptable Use Policy',
            'url' => 'https://www.truckbill.in/policies/acceptable-use-policy.html',
        ],
    ],
];
