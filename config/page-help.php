<?php

return [
    'registry' => [
        'public.home' => ['name' => 'Public Home', 'area' => 'Public', 'route' => 'home'],
        'public.account' => ['name' => 'Customer Account', 'area' => 'Public', 'route' => 'account.index'],
        'public.assets.index' => ['name' => 'Browse Assets', 'area' => 'Public', 'route' => 'images.index'],
        'public.assets.show' => ['name' => 'Asset Details', 'area' => 'Public', 'route' => 'assets.show'],
        'public.blog.index' => ['name' => 'Stories', 'area' => 'Public', 'route' => 'blog.index'],
        'public.support.index' => ['name' => 'Support', 'area' => 'Public', 'route' => 'support.public.index'],
        'public.support.create' => ['name' => 'Submit a Support Request', 'area' => 'Public', 'route' => 'support.public.create'],

        'member.dashboard' => ['name' => 'Member Dashboard', 'area' => 'Member', 'route' => 'dashboard'],
        'member.purchases.index' => ['name' => 'My Library', 'area' => 'Member', 'route' => 'purchases.index'],
        'member.support.index' => ['name' => 'Support Center', 'area' => 'Member', 'route' => 'support.index'],
        'member.support.create' => ['name' => 'Create Support Ticket', 'area' => 'Member', 'route' => 'support.create'],
        'member.support.show' => ['name' => 'Support Ticket', 'area' => 'Member', 'route' => 'support.show'],

        'advertiser.dashboard' => ['name' => 'Advertiser Dashboard', 'area' => 'Advertiser', 'route' => 'advertiser.dashboard'],
        'advertiser.campaigns.index' => ['name' => 'Advertising Campaigns', 'area' => 'Advertiser', 'route' => 'advertiser.campaigns.index'],
        'advertiser.campaigns.edit' => ['name' => 'Edit Advertising Campaign', 'area' => 'Advertiser', 'route' => 'advertiser.campaigns.edit'],
        'advertiser.account' => ['name' => 'Advertiser Account', 'area' => 'Advertiser', 'route' => 'advertiser.account.edit'],

        'admin.dashboard' => ['name' => 'Admin Overview', 'area' => 'Admin', 'route' => 'admin.dashboard'],
        'admin.assets.index' => ['name' => 'Assets', 'area' => 'Admin', 'route' => 'admin.assets.index'],
        'admin.assets.create' => ['name' => 'Create Asset', 'area' => 'Admin', 'route' => 'admin.assets.create'],
        'admin.assets.edit' => ['name' => 'Edit Asset', 'area' => 'Admin', 'route' => 'admin.assets.edit'],
        'admin.orders.index' => ['name' => 'Orders', 'area' => 'Admin', 'route' => 'admin.orders.index'],
        'admin.support.dashboard' => ['name' => 'Support Dashboard', 'area' => 'Admin', 'route' => 'admin.support.dashboard'],
        'admin.support.tickets.index' => ['name' => 'Support Tickets', 'area' => 'Admin', 'route' => 'admin.support.tickets.index'],
        'admin.support.tickets.show' => ['name' => 'Support Ticket Workspace', 'area' => 'Admin', 'route' => 'admin.support.tickets.show'],
        'admin.support.categories.index' => ['name' => 'Support Categories', 'area' => 'Admin', 'route' => 'admin.support.categories.index'],
        'admin.page-help.index' => ['name' => 'Page Help Administration', 'area' => 'Admin', 'route' => 'admin.page-help.index'],
        'admin.page-help.create' => ['name' => 'Create Page Help', 'area' => 'Admin', 'route' => 'admin.page-help.create'],
        'admin.page-help.coverage' => ['name' => 'Page Help Coverage', 'area' => 'Admin', 'route' => 'admin.page-help.coverage'],
    ],
];
