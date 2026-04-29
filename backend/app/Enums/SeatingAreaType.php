<?php

namespace App\Enums;

enum SeatingAreaType: string
{
    case Indoor = 'indoor';
    case Outdoor = 'outdoor';
    case PrivateRoom = 'private_room';
}

