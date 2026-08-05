<?php

namespace App\Enums;

enum DeliveryMode: string
{
    case Online = 'online';
    case Offline = 'offline';
    case VirtualClass = 'virtual_class';
}
