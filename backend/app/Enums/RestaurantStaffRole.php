<?php

namespace App\Enums;

enum RestaurantStaffRole: string
{
    case RestaurantOwner = 'restaurant_owner';
    case BranchManager = 'branch_manager';
    case RestaurantHost = 'restaurant_host';
}

