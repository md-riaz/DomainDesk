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
            background: #10b981;
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
        .badge {
            display: inline-block;
            padding: 4px 12px;
            background: #10b981;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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
        <h1 style="margin: 0;">Domain Renewal Successful!</h1>
    </div>
    
    <div class="content">
        <p>Great news! Your domain has been successfully renewed.</p>
        
        <div class="domain-info">
            <h2 style="margin-top: 0; color: #111827;">{{ $domainName }}</h2>
            
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value"><span class="badge">Active</span></span>
            </div>
            
            <div class="info-row">
                <span class="label">New Expiry Date:</span>
                <span class="value">{{ $expiresAt }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Auto-Renew:</span>
                <span class="value">{{ $autoRenew ? 'Enabled' : 'Disabled' }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Invoice Number:</span>
                <span class="value">{{ $invoiceNumber }}</span>
            </div>
            
            <div class="info-row">
                <span class="label">Amount Paid:</span>
                <span class="value">${{ $total }}</span>
            </div>
        </div>
        
        <p><strong>Important Information:</strong></p>
        <ul>
            <li>Your domain registration has been extended until {{ $expiresAt }}</li>
            <li>All domain settings and configurations remain unchanged</li>
            <li>@if($autoRenew)
                Auto-renewal is enabled - your domain will automatically renew before expiration
            @else
                Consider enabling auto-renewal to prevent your domain from expiring
            @endif</li>
            <li>You can download your invoice from your dashboard</li>
        </ul>
        
        <div class="footer">
            <p>Thank you for choosing our domain services!</p>
            <p>If you have any questions, please contact our support team.</p>
        </div>
    </div>
</body>
</html>
