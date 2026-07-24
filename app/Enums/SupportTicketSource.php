<?php

namespace App\Enums;

enum SupportTicketSource: string
{
    case Public = 'public';
    case Member = 'member';
    case Advertiser = 'advertiser';
    case Admin = 'admin';
    case System = 'system';
}
