<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? 'Email from ' . ($branding->email_sender_name ?? config('app.name')) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f7;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f7;
            padding: 20px 0;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .email-header {
            background-color: {{ $branding->primary_color ?? '#4f46e5' }};
            background: linear-gradient(135deg, {{ $branding->primary_color ?? '#4f46e5' }} 0%, {{ $branding->secondary_color ?? '#6366f1' }} 100%);
            padding: 30px 40px;
            text-align: center;
        }
        
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .email-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            line-height: 1.3;
        }
        
        .email-body {
            padding: 40px;
            color: #333333;
            font-size: 16px;
        }
        
        .email-footer {
            background-color: #f9fafb;
            padding: 30px 40px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .footer-links {
            margin: 15px 0;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        
        .footer-links a:hover {
            color: {{ $branding->primary_color ?? '#4f46e5' }};
        }
        
        .footer-text {
            color: #9ca3af;
            font-size: 13px;
            line-height: 1.5;
            margin-top: 15px;
        }
        
        .button {
            display: inline-block;
            padding: 14px 32px;
            background-color: {{ $branding->primary_color ?? '#4f46e5' }};
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            transition: background-color 0.3s ease;
        }
        
        .button:hover {
            background-color: {{ $branding->secondary_color ?? '#6366f1' }};
        }
        
        .button-secondary {
            background-color: #6b7280;
        }
        
        .button-secondary:hover {
            background-color: #4b5563;
        }
        
        .info-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .info-box.success {
            background-color: #ecfdf5;
            border-color: #10b981;
        }
        
        .info-box.warning {
            background-color: #fffbeb;
            border-color: #f59e0b;
        }
        
        .info-box.danger {
            background-color: #fef2f2;
            border-color: #ef4444;
        }
        
        .info-box.info {
            background-color: #eff6ff;
            border-color: #3b82f6;
        }
        
        .info-row {
            display: table;
            width: 100%;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            display: table-cell;
            font-weight: 600;
            color: #6b7280;
            width: 40%;
            padding-right: 15px;
        }
        
        .info-value {
            display: table-cell;
            color: #111827;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .badge-success {
            background-color: #10b981;
            color: #ffffff;
        }
        
        .badge-warning {
            background-color: #f59e0b;
            color: #ffffff;
        }
        
        .badge-danger {
            background-color: #ef4444;
            color: #ffffff;
        }
        
        .badge-info {
            background-color: #3b82f6;
            color: #ffffff;
        }
        
        h2 {
            color: #111827;
            font-size: 20px;
            font-weight: 600;
            margin: 25px 0 15px 0;
        }
        
        h3 {
            color: #374151;
            font-size: 18px;
            font-weight: 600;
            margin: 20px 0 12px 0;
        }
        
        p {
            margin: 15px 0;
            line-height: 1.6;
        }
        
        ul, ol {
            margin: 15px 0;
            padding-left: 25px;
        }
        
        li {
            margin: 8px 0;
            line-height: 1.5;
        }
        
        .divider {
            border: 0;
            border-top: 1px solid #e5e7eb;
            margin: 30px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table th {
            background-color: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        /* Mobile responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                border-radius: 0 !important;
            }
            
            .email-header,
            .email-body,
            .email-footer {
                padding: 25px 20px !important;
            }
            
            .logo {
                max-width: 150px;
            }
            
            .email-header h1 {
                font-size: 20px;
            }
            
            .button {
                display: block;
                width: 100%;
            }
            
            .info-row {
                display: block;
            }
            
            .info-label,
            .info-value {
                display: block;
                width: 100%;
            }
            
            .info-label {
                padding-bottom: 5px;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1f2937;
            }
            
            .email-container {
                background-color: #111827;
            }
            
            .email-body {
                color: #e5e7eb;
            }
            
            h2, h3 {
                color: #f3f4f6;
            }
            
            .info-box {
                background-color: #1f2937;
                border-color: #374151;
            }
            
            .email-footer {
                background-color: #0f172a;
                border-top-color: #374151;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                @if(!empty($branding->logo_path))
                    <img src="{{ asset('storage/' . $branding->logo_path) }}" alt="{{ $branding->email_sender_name ?? config('app.name') }} Logo" class="logo">
                @else
                    <h1 style="margin-bottom: 0;">{{ $branding->email_sender_name ?? config('app.name') }}</h1>
                @endif
                
                @if(isset($headerTitle))
                    <h1>{{ $headerTitle }}</h1>
                @endif
            </div>
            
            <!-- Body -->
            <div class="email-body">
                @yield('content')
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                @if(!empty($branding->support_email) || !empty($branding->support_phone) || !empty($branding->support_url))
                    <p style="color: #6b7280; font-size: 14px; margin-bottom: 15px;">
                        <strong>Need Help?</strong>
                    </p>
                    <p style="color: #6b7280; font-size: 14px;">
                        @if(!empty($branding->support_email))
                            Email: <a href="mailto:{{ $branding->support_email }}" style="color: {{ $branding->primary_color ?? '#4f46e5' }};">{{ $branding->support_email }}</a><br>
                        @endif
                        @if(!empty($branding->support_phone))
                            Phone: {{ $branding->support_phone }}<br>
                        @endif
                        @if(!empty($branding->support_url))
                            <a href="{{ $branding->support_url }}" style="color: {{ $branding->primary_color ?? '#4f46e5' }};">Visit Support Center</a>
                        @endif
                    </p>
                @endif
                
                <div class="footer-links">
                    <a href="{{ url('/') }}">Home</a>
                    @if(!empty($dashboardUrl))
                        <a href="{{ $dashboardUrl }}">Dashboard</a>
                    @endif
                    <a href="{{ url('/support') }}">Support</a>
                    <a href="{{ url('/privacy') }}">Privacy</a>
                </div>
                
                <p class="footer-text">
                    © {{ date('Y') }} {{ $branding->email_sender_name ?? config('app.name') }}. All rights reserved.<br>
                    You're receiving this email because you have an account with us.
                </p>
                
                @if(isset($unsubscribeUrl))
                    <p class="footer-text">
                        <a href="{{ $unsubscribeUrl }}" style="color: #9ca3af;">Manage Email Preferences</a>
                    </p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
