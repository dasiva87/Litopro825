<?php

namespace App\Enums;

enum SuperAdminNavigationGroup: string
{
    case TenantManagement = 'Tenant Management';
    case SubscriptionManagement = 'Subscription Management';
    case UserManagement = 'User Management';
    case AnalyticsReports = 'Analytics & Reports';
    case SystemAdministration = 'System Administration';
}
