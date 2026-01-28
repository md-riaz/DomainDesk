@extends('emails.layout', [
    'headerTitle' => '🚨 CRITICAL: Domain Has Expired!',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <div class="info-box danger" style="border-width: 3px;">
        <h2 style="margin-top: 0; color: #dc2626; font-size: 24px;">⚠️ DOMAIN EXPIRED</h2>
        <p style="margin: 0; color: #991b1b; font-size: 18px; font-weight: 600;">
            Your domain has expired and may stop functioning at any moment!
        </p>
    </div>
    
    <p style="font-size: 17px;">
        <strong>IMMEDIATE ACTION REQUIRED:</strong> Your domain <strong>{{ $domain->name }}</strong> expired on 
        <strong>{{ $domain->expires_at->format('F j, Y') }}</strong>. To restore your services and prevent permanent loss of your domain, 
        you must renew it immediately.
    </p>
    
    <div class="info-box">
        <h2 style="margin-top: 0; color: #dc2626;">{{ $domain->name }}</h2>
        
        <div class="info-row">
            <span class="info-label">Status:</span>
            <span class="info-value">
                <span class="badge badge-danger">EXPIRED</span>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Expired On:</span>
            <span class="info-value"><strong>{{ $domain->expires_at->format('F j, Y') }}</strong></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Days Expired:</span>
            <span class="info-value" style="color: #dc2626;">
                <strong>{{ $daysExpired }} day{{ $daysExpired != 1 ? 's' : '' }}</strong>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Grace Period Ends:</span>
            <span class="info-value">
                <strong style="color: #dc2626;">{{ $gracePeriodEnds->format('F j, Y') }}</strong>
                ({{ $daysUntilGracePeriodEnds }} days remaining)
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Renewal Cost:</span>
            <span class="info-value"><strong>${{ number_format($renewalCost, 2) }}</strong></span>
        </div>
        
        @if($redemptionFee > 0)
        <div class="info-row">
            <span class="info-label">Redemption Fee:</span>
            <span class="info-value" style="color: #dc2626;">
                <strong>${{ number_format($redemptionFee, 2) }}</strong>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Total to Restore:</span>
            <span class="info-value">
                <strong>${{ number_format($renewalCost + $redemptionFee, 2) }}</strong>
            </span>
        </div>
        @endif
    </div>
    
    <h3 style="color: #dc2626;">🚨 Current Impact</h3>
    <div class="info-box danger">
        <ul style="margin: 10px 0; color: #991b1b;">
            <li><strong>Website Offline:</strong> Your website is no longer accessible</li>
            <li><strong>Email Not Working:</strong> Email addresses using this domain are not functioning</li>
            <li><strong>DNS Not Resolving:</strong> Domain name system lookups are failing</li>
            <li><strong>Services Disrupted:</strong> Any services dependent on this domain are affected</li>
            <li><strong>Risk of Permanent Loss:</strong> After grace period, domain may be lost forever</li>
        </ul>
    </div>
    
    <h3>⏰ Grace Period Information</h3>
    <p>
        You have <strong style="color: #dc2626;">{{ $daysUntilGracePeriodEnds }} days</strong> remaining in the grace period to renew your domain. 
        After the grace period expires:
    </p>
    <ul>
        <li>The domain enters a redemption period with <strong>significantly higher recovery costs</strong></li>
        <li>Eventually, the domain will be released and anyone can register it</li>
        <li>You may <strong>permanently lose ownership</strong> of your domain name</li>
        <li>Recovery becomes increasingly difficult and expensive</li>
    </ul>
    
    <div style="text-align: center; margin: 30px 0; padding: 20px; background-color: #fef2f2; border: 2px solid #dc2626; border-radius: 8px;">
        <h3 style="color: #dc2626; margin-top: 0;">Don't Lose Your Domain!</h3>
        <p style="margin: 15px 0; font-size: 16px;">
            Renew now to restore your website, email, and prevent permanent loss of your domain.
        </p>
        <a href="{{ $renewalUrl ?? url('/dashboard/domains/' . $domain->id . '/renew') }}" class="button" style="font-size: 18px; padding: 16px 40px;">
            🚨 Renew Domain NOW
        </a>
    </div>
    
    <h3>💰 Renewal Costs</h3>
    <table>
        <tbody>
            <tr>
                <td><strong>Domain Renewal (1 Year)</strong></td>
                <td style="text-align: right;"><strong>${{ number_format($renewalCost, 2) }}</strong></td>
            </tr>
            @if($redemptionFee > 0)
            <tr>
                <td><strong>Redemption Fee</strong></td>
                <td style="text-align: right; color: #dc2626;"><strong>${{ number_format($redemptionFee, 2) }}</strong></td>
            </tr>
            <tr style="background-color: #fef2f2;">
                <td><strong>Total Cost</strong></td>
                <td style="text-align: right;"><strong>${{ number_format($renewalCost + $redemptionFee, 2) }}</strong></td>
            </tr>
            @endif
        </tbody>
    </table>
    
    @if($redemptionFee > 0)
        <div class="info-box warning">
            <p style="margin: 0;">
                <strong>⚠️ Additional Redemption Fee Applied:</strong> Because your domain has been expired for several days, 
                a redemption fee of ${{ number_format($redemptionFee, 2) }} is required in addition to the standard renewal cost.
                Renewing within the first few days after expiration can help you avoid these extra charges.
            </p>
        </div>
    @endif
    
    <h3>🔄 Enable Auto-Renewal</h3>
    <p>
        After renewing, we <strong>strongly recommend</strong> enabling auto-renewal to prevent this situation in the future. 
        With auto-renewal:
    </p>
    <ul>
        <li>Your domain renews automatically before expiration</li>
        <li>No service interruptions or downtime</li>
        <li>You'll be notified before each renewal</li>
        <li>Can be disabled anytime from your dashboard</li>
    </ul>
    
    <hr class="divider">
    
    <div class="info-box info">
        <h3 style="margin-top: 0;">Need Assistance?</h3>
        <p style="margin-bottom: 0;">
            Our support team is standing by to help you renew your domain and restore your services. 
            Contact us immediately if you need assistance with the renewal process.
        </p>
    </div>
    
    <p style="color: #dc2626; font-weight: 600; text-align: center; font-size: 16px;">
        ⚠️ Time is critical - Renew your domain now to avoid permanent loss!
    </p>
@endsection
