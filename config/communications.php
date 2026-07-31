<?php

return [
    'templates' => [
        'account.verify_email' => [
            'name' => 'Verify Email Address',
            'category' => 'Account',
            'description' => 'Sent after registration and when a member requests another verification link.',
            'subject' => 'Confirm your Unclad Collection membership',
            'preview_text' => 'Confirm your email address to finish setting up your membership.',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>Thank you for joining Unclad Collection. Please confirm your email address to finish setting up your membership.</p><p><a href="{{ verification_url }}">Confirm my email address</a></p><p>This secure link expires in {{ expiration_minutes }} minutes.</p>',
            'body_text' => "Hello {{ customer_name }},\n\nThank you for joining Unclad Collection. Confirm your email address here:\n{{ verification_url }}\n\nThis secure link expires in {{ expiration_minutes }} minutes.",
            'variables' => ['customer_name', 'customer_email', 'verification_url', 'expiration_minutes'],
            'required_variables' => ['verification_url'],
            'transactional' => true,
        ],
        'account.welcome' => [
            'name' => 'Membership Welcome',
            'category' => 'Account',
            'description' => 'Sent after a member verifies their email address.',
            'subject' => 'Welcome to Unclad Collection',
            'preview_text' => 'Your membership is ready.',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>Your Unclad Collection membership is now active.</p><p><a href="{{ account_url }}">Visit my account</a></p>',
            'body_text' => "Hello {{ customer_name }},\n\nYour Unclad Collection membership is now active.\n\nVisit your account: {{ account_url }}",
            'variables' => ['customer_name', 'customer_email', 'account_url'],
            'required_variables' => [],
            'transactional' => false,
        ],
        'order.confirmed' => [
            'name' => 'Order Confirmation',
            'category' => 'Commerce',
            'description' => 'Sent after a customer successfully completes a purchase.',
            'subject' => 'Order {{ order_number }} confirmed',
            'preview_text' => 'We received your Unclad Collection order.',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>Thank you for your purchase. Order <strong>{{ order_number }}</strong> has been confirmed for {{ order_total }}.</p><p><a href="{{ order_url }}">View order</a></p>',
            'body_text' => "Hello {{ customer_name }},\n\nThank you for your purchase. Order {{ order_number }} has been confirmed for {{ order_total }}.\n\nView order: {{ order_url }}",
            'variables' => ['customer_name', 'customer_email', 'order_number', 'order_total', 'order_url'],
            'required_variables' => ['order_number', 'order_url'],
            'transactional' => true,
        ],
        'license.issued' => [
            'name' => 'License Issued',
            'category' => 'Licenses',
            'description' => 'Sent when a purchased asset license is available.',
            'subject' => 'Your license is ready for {{ asset_title }}',
            'preview_text' => 'Your asset license and download are available.',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>Your {{ license_name }} license for <strong>{{ asset_title }}</strong> is ready.</p><p><a href="{{ license_url }}">View license</a></p>',
            'body_text' => "Hello {{ customer_name }},\n\nYour {{ license_name }} license for {{ asset_title }} is ready.\n\nView license: {{ license_url }}",
            'variables' => ['customer_name', 'customer_email', 'asset_title', 'license_name', 'license_url'],
            'required_variables' => ['asset_title', 'license_url'],
            'transactional' => true,
        ],

        'order.status_updated' => [
            'name' => 'Order Status Update',
            'category' => 'Commerce',
            'description' => 'Sent when an order fails, is canceled, or receives a refund update.',
            'subject' => 'Update for order {{ order_number }}',
            'preview_text' => '{{ order_message }}',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>{{ order_message }}</p><p><a href="{{ order_url }}">View my purchases</a></p>',
            'body_text' => "Hello {{ customer_name }},

{{ order_message }}

View my purchases: {{ order_url }}",
            'variables' => ['customer_name', 'customer_email', 'order_number', 'order_status', 'order_message', 'order_url'],
            'required_variables' => ['order_number', 'order_message', 'order_url'],
            'transactional' => true,
        ],
        'order.fulfillment_updated' => [
            'name' => 'Order Fulfillment Update',
            'category' => 'Commerce',
            'description' => 'Sent when an order fulfillment status or tracking information changes.',
            'subject' => 'Order {{ order_number }} is now {{ fulfillment_status }}',
            'preview_text' => 'Your order fulfillment status has changed.',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>Order <strong>{{ order_number }}</strong> is now <strong>{{ fulfillment_status }}</strong>.</p><p>Tracking number: {{ tracking_number }}</p><p><a href="{{ order_url }}">View order</a></p>',
            'body_text' => "Hello {{ customer_name }},

Order {{ order_number }} is now {{ fulfillment_status }}.
Tracking number: {{ tracking_number }}

View order: {{ order_url }}",
            'variables' => ['customer_name', 'customer_email', 'order_number', 'fulfillment_status', 'tracking_number', 'order_url'],
            'required_variables' => ['order_number', 'fulfillment_status', 'order_url'],
            'transactional' => true,
        ],
        'license.status_updated' => [
            'name' => 'License Status Update',
            'category' => 'Licenses',
            'description' => 'Sent when staff changes a customer license status or terms.',
            'subject' => 'License {{ license_key }} is now {{ license_status }}',
            'preview_text' => '{{ license_message }}',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>{{ license_message }}</p><p>License: <strong>{{ license_name }}</strong> ({{ license_key }})</p><p><a href="{{ license_url }}">View license</a></p>',
            'body_text' => "Hello {{ customer_name }},

{{ license_message }}

License: {{ license_name }} ({{ license_key }})
View license: {{ license_url }}",
            'variables' => ['customer_name', 'customer_email', 'license_key', 'license_name', 'license_status', 'license_message', 'license_url'],
            'required_variables' => ['license_key', 'license_status', 'license_url'],
            'transactional' => true,
        ],
        'download.limit_warning' => [
            'name' => 'Download Limit Warning',
            'category' => 'Downloads',
            'description' => 'Sent when one or no downloads remain on a license.',
            'subject' => 'Download allowance update for {{ license_key }}',
            'preview_text' => '{{ download_message }}',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>{{ download_message }}</p><p>Downloads remaining: <strong>{{ downloads_remaining }}</strong></p><p><a href="{{ license_url }}">View license</a></p>',
            'body_text' => "Hello {{ customer_name }},

{{ download_message }}
Downloads remaining: {{ downloads_remaining }}

View license: {{ license_url }}",
            'variables' => ['customer_name', 'customer_email', 'license_key', 'downloads_remaining', 'download_message', 'license_url'],
            'required_variables' => ['license_key', 'downloads_remaining', 'license_url'],
            'transactional' => true,
        ],
        'support.ticket_created' => [
            'name' => 'Support Ticket Confirmation',
            'category' => 'Support',
            'description' => 'Sent after a customer submits a support request.',
            'subject' => 'Support request {{ ticket_number }} received',
            'preview_text' => 'We received your support request.',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>We received support request <strong>{{ ticket_number }}</strong>: {{ ticket_subject }}</p><p>You can review updates and reply using the secure link below.</p><p><a href="{{ ticket_url }}">View support request</a></p>',
            'body_text' => "Hello {{ customer_name }},

We received support request {{ ticket_number }}: {{ ticket_subject }}

View support request: {{ ticket_url }}",
            'variables' => ['customer_name', 'customer_email', 'ticket_number', 'ticket_subject', 'ticket_url'],
            'required_variables' => ['ticket_number', 'ticket_url'],
            'transactional' => true,
        ],
        'support.ticket_replied' => [
            'name' => 'Support Ticket Reply',
            'category' => 'Support',
            'description' => 'Sent when staff replies to a customer support ticket.',
            'subject' => 'New reply to ticket {{ ticket_number }}',
            'preview_text' => 'The Unclad Collection support team replied to your request.',
            'body_html' => '<p>Hello {{ customer_name }},</p><p>Our support team replied to ticket <strong>{{ ticket_number }}</strong>.</p><p>{{ reply_excerpt }}</p><p><a href="{{ ticket_url }}">View ticket</a></p>',
            'body_text' => "Hello {{ customer_name }},\n\nOur support team replied to ticket {{ ticket_number }}.\n\n{{ reply_excerpt }}\n\nView ticket: {{ ticket_url }}",
            'variables' => ['customer_name', 'customer_email', 'ticket_number', 'reply_excerpt', 'ticket_url'],
            'required_variables' => ['ticket_number', 'ticket_url'],
            'transactional' => true,
        ],
    ],
];
