<?php

namespace App\Enums;

enum TierFeature: string
{
    case Analytics = 'analytics';
    case LiveSession = 'live_session';
    case LiveSessionRecording = 'live_session_recording';
    case ApiAccess = 'api_access';
    case CustomBranding = 'custom_branding';
    case SSO = 'sso';
    case PrioritySupport = 'priority_support';

    public function label(): string
    {
        return match ($this) {
            self::Analytics => 'Analytics',
            self::LiveSession => 'Live Session',
            self::LiveSessionRecording => 'Live Session Recording',
            self::ApiAccess => 'API Access',
            self::CustomBranding => 'Custom Branding',
            self::SSO => 'Single Sign-On (SSO)',
            self::PrioritySupport => 'Priority Support',
        };
    }
}
