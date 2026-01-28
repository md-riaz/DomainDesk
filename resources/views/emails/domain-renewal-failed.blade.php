<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #ef4444;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .alert-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .domain-info {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #6b7280;
        }
        .value {
            color: #111827;
        }
        .cta-button {
            display: inline-block;
            padding: 12px 24px;
            background: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">{{ $isAutoRenewal ? 'Auto-Renewal Failed' : 'Domain Renewal Failed' }}</h1>
    </div>
    
    <div class="content">
        <div class="alert-box">
            <strong>Action Required:</strong> Your domain renewal {{ $isAutoRenewal ? 'could not be processed automatically' : 'has failed' }}.
        </div>
        
        <p>We were unable to renew your domain registration. Please take action to prevent your domain from expiring.</p>
        
        <div class="domain-info">
            <h2 style="margin-top: 0; color: #111827;">{{ $domainName }}</h2>
            
            <div class="info-row">
                <span class="label">Expiry Date:</span>
                <span class="value" style="color: #ef4444; font-weight: bold;">{{ $expiresAt }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Days Until Expiry:</span>
                <span class="value">
                    @if($daysUntilExpiry > 0)
                        {{ $daysUntilExpiry }} days
                    @else
                        Expired {{ abs($daysUntilExpiry) }} days ago
                    @endif
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">Failure Reason:</span>
                <span class="value">{{ $reason }}</span>
            </div>
        </div>
        
        <p><strong>Why did this happen?</strong></p>
        <p>{{ $reason }}</p>
        
        <p><strong>What happens next?</strong></p>
        <ul>
            @if($daysUntilExpiry > 0)
                <li>You have {{ $daysUntilExpiry }} days to renew your domain</li>
                <li>After expiry, your domain will enter a grace period (additional fees may apply)</li>
            @else
                <li>Your domain is currently in the grace period</li>
                <li>Additional fees will apply for late renewal</li>
            @endif
            <li>If not renewed, your domain may be deleted and become available for others to register</li>
        </ul>
        
        <p><strong>Action Required:</strong></p>
        <ol>
            <li>Log in to your dashboard</li>
            @if($isAutoRenewal)
                <li>Ensure your wallet has sufficient balance</li>
                <li>Manually renew your domain or wait for the next auto-renewal attempt</li>
            @else
                <li>Navigate to your domain details</li>
                <li>Click "Renew Domain" to complete the renewal process</li>
            @endif
        </ol>
        
        <center>
            <a href="{{ config('app.url') }}" class="cta-button">Renew Domain Now</a>
        </center>
        
        <div class="footer">
            <p>Don't lose your domain! Take action today.</p>
            <p>If you need assistance, please contact our support team immediately.</p>
        </div>
    </div>
</body>
</html>
