<?php

namespace App\Enums;

enum MemberPortalAccessMethod: string
{
    case Invitation = 'invitation';
    case Password = 'password';
}
