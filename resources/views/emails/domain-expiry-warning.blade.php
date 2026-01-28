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
            background: {{ $daysUntilExpiry <= 7 ? '#ef4444' : '#f59e0b' }};
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
        .warning-box {
            background: {{ $daysUntilExpiry <= 7 ? '#fee2e2' : '#fef3c7' }};
            border-left: 4px solid {{ $daysUntilExpiry <= 7 ? '#ef4444' : '#f59e0b' }};
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
        .countdown {
            font-size: 36px;
            font-weight: bold;
            color: {{ $daysUntilExpiry <= 7 ? '#ef4444' : '#f59e0b' }};
            text-align: center;
            margin: 20px 0;
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
        <h1 style="margin: 0;">
            @if($daysUntilExpiry <= 1)
                ⚠️ URGENT: Domain Expiring {{ $daysUntilExpiry === 1 ? 'Tomorrow' : 'Today' }}!
            @else
                Domain Expiring Soon
            @endif
        </h1>
    </div>
    
    <div class="content">
        <div class="warning-box">
            <strong>{{ $daysUntilExpiry <= 7 ? 'URGENT:' : 'Reminder:' }}</strong> 
            Your domain is expiring in {{ $daysUntilExpiry }} {{ $daysUntilExpiry === 1 ? 'day' : 'days' }}.
        </div>
        
        <div class="countdown">
            {{ $daysUntilExpiry }} {{ $daysUntilExpiry === 1 ? 'Day' : 'Days' }}
        </div>
        
        <p>This is a {{ $daysUntilExpiry <= 7 ? 'final' : 'friendly' }} reminder that your domain registration is about to expire.</p>
        
        <div class="domain-info">
            <h2 style="margin-top: 0; color: #111827;">{{ $domainName }}</h2>
            
            <div class="info-row">
                <span class="label">Expires On:</span>
                <span class="value" style="font-weight: bold; color: {{ $daysUntilExpiry <= 7 ? '#ef4444' : '#f59e0b' }};">
                    {{ $expiresAt }}
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">Days Remaining:</span>
                <span class="value">{{ $daysUntilExpiry }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Auto-Renew:</span>
                <span class="value">{{ $autoRenew ? 'Enabled ✓' : 'Disabled ✗' }}</span>
            </div>
        </div>
        
        @if($autoRenew)
            <p><strong>Auto-Renewal is Enabled:</strong></p>
            <p>Your domain will be automatically renewed before expiration. Please ensure your wallet has sufficient balance to complete the renewal.</p>
        @else
            <p><strong>Action Required:</strong></p>
            <p>Auto-renewal is <strong>not enabled</strong> for this domain. You must manually renew your domain to prevent it from expiring.</p>
            
            <p><strong>What happens if you don't renew?</strong></p>
            <ul>
                <li>Your domain will expire and may stop working</li>
                <li>Your website and email may become inaccessible</li>
                <li>After the grace period, your domain may be deleted</li>
                <li>Someone else may be able to register your domain</li>
            </ul>
            
            <center>
                <a href="{{ config('app.url') }}" class="cta-button">Renew Domain Now</a>
            </center>
        @endif
        
        <p><strong>Need Help?</strong></p>
        <p>If you have any questions or need assistance with renewing your domain, please contact our support team.</p>
        
        <div class="footer">
            <p>Don't let your domain expire! Renew today to keep your online presence active.</p>
            <p>This is an automated reminder. You will receive additional notifications as your expiry date approaches.</p>
        </div>
    </div>
</body>
</html>
