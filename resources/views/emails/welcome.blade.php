@extends('emails.layout', [
    'headerTitle' => '👋 Welcome to ' . ($branding->email_sender_name ?? config('app.name')) . '!',
    'branding' => $branding,
    'dashboardUrl' => $dashboardUrl ?? url('/dashboard'),
])

@section('content')
    <div class="info-box success">
        <h2 style="margin-top: 0; color: #10b981; font-size: 24px;">🎉 Welcome Aboard!</h2>
        <p style="margin: 0; color: #065f46; font-size: 16px;">
            Your account has been successfully created. We're excited to have you with us!
        </p>
    </div>
    
    <p>
        Thank you for choosing <strong>{{ $branding->email_sender_name ?? config('app.name') }}</strong> for your domain management needs. 
        We're here to make domain registration, management, and renewal as simple as possible.
    </p>
    
    <h3>👤 Your Account Details</h3>
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value">{{ $user->name }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $user->email }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Account Created:</span>
            <span class="info-value">{{ $user->created_at->format('F j, Y g:i A') }}</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Account Status:</span>
            <span class="info-value"><span class="badge badge-success">Active</span></span>
        </div>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $dashboardUrl ?? url('/dashboard') }}" class="button">
            Access Your Dashboard
        </a>
    </div>
    
    <h3>🚀 Getting Started</h3>
    <p>Here's how to get the most out of your account:</p>
    
    <div class="info-box">
        <h4 style="margin-top: 0;">1️⃣ Search for Domains</h4>
        <p style="margin-bottom: 0;">
            Use our powerful domain search tool to find the perfect domain name for your business or project.
            Check availability across hundreds of TLDs.
        </p>
    </div>
    
    <div class="info-box">
        <h4 style="margin-top: 0;">2️⃣ Register Your Domain</h4>
        <p style="margin-bottom: 0;">
            Once you find your ideal domain, register it in just a few clicks. Competitive pricing and 
            instant activation guaranteed.
        </p>
    </div>
    
    <div class="info-box">
        <h4 style="margin-top: 0;">3️⃣ Configure DNS & Nameservers</h4>
        <p style="margin-bottom: 0;">
            Point your domain to your hosting provider or configure DNS records directly in our dashboard. 
            Full control over your domain settings.
        </p>
    </div>
    
    <div class="info-box">
        <h4 style="margin-top: 0;">4️⃣ Enable Auto-Renewal</h4>
        <p style="margin-bottom: 0;">
            Never worry about domain expiration again. Enable auto-renewal to keep your domains active 
            without manual intervention.
        </p>
    </div>
    
    <h3>✨ Platform Features</h3>
    <ul>
        <li><strong>Domain Search:</strong> Check availability across 500+ TLDs instantly</li>
        <li><strong>DNS Management:</strong> Full control over A, CNAME, MX, TXT, and other records</li>
        <li><strong>Nameserver Management:</strong> Easily configure custom nameservers</li>
        <li><strong>Auto-Renewal:</strong> Set it and forget it - domains renew automatically</li>
        <li><strong>Domain Transfer:</strong> Transfer domains from other registrars seamlessly</li>
        <li><strong>WHOIS Privacy:</strong> Protect your personal information (where available)</li>
        <li><strong>Email Notifications:</strong> Stay informed about renewals and expirations</li>
        <li><strong>Invoice Management:</strong> Track all your billing in one place</li>
        <li><strong>Account Balance:</strong> Prepay for faster checkouts and automatic renewals</li>
    </ul>
    
    <h3>💡 Quick Tips</h3>
    <div class="info-box info">
        <ul style="margin: 10px 0;">
            <li><strong>Add Funds:</strong> Maintain an account balance for instant domain purchases</li>
            <li><strong>Enable 2FA:</strong> Secure your account with two-factor authentication</li>
            <li><strong>Set Contact Info:</strong> Complete your profile for domain registration requirements</li>
            <li><strong>Bookmark Dashboard:</strong> Quick access to all your domain management tools</li>
        </ul>
    </div>
    
    <h3>📚 Helpful Resources</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0;">
        <div style="padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
            <strong>📖 Documentation</strong>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">
                Detailed guides and tutorials
            </p>
        </div>
        
        <div style="padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
            <strong>❓ FAQs</strong>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">
                Common questions answered
            </p>
        </div>
        
        <div style="padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
            <strong>💬 Support</strong>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">
                24/7 support team ready to help
            </p>
        </div>
        
        <div style="padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
            <strong>📹 Video Tutorials</strong>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">
                Step-by-step video guides
            </p>
        </div>
    </div>
    
    <h3>🆘 Need Help?</h3>
    <div class="info-box">
        <p>Our support team is here to assist you:</p>
        @if(!empty($branding->support_email))
            <p style="margin: 5px 0;">
                <strong>Email:</strong> <a href="mailto:{{ $branding->support_email }}" style="color: {{ $branding->primary_color ?? '#4f46e5' }};">{{ $branding->support_email }}</a>
            </p>
        @endif
        @if(!empty($branding->support_phone))
            <p style="margin: 5px 0;">
                <strong>Phone:</strong> {{ $branding->support_phone }}
            </p>
        @endif
        @if(!empty($branding->support_url))
            <p style="margin: 5px 0 0 0;">
                <strong>Support Center:</strong> <a href="{{ $branding->support_url }}" style="color: {{ $branding->primary_color ?? '#4f46e5' }};">{{ $branding->support_url }}</a>
            </p>
        @endif
    </div>
    
    <hr class="divider">
    
    <div class="info-box success">
        <h3 style="margin-top: 0;">🎁 Special Welcome Offer</h3>
        <p style="margin-bottom: 0;">
            @if(isset($welcomeOffer))
                {{ $welcomeOffer }}
            @else
                As a new member, you have access to competitive pricing on all domain registrations and transfers. 
                Start searching for your perfect domain today!
            @endif
        </p>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ url('/dashboard/domains/search') }}" class="button">
            Start Searching for Domains
        </a>
    </div>
    
    <p style="text-align: center; color: #6b7280; font-size: 14px;">
        Thank you for choosing {{ $branding->email_sender_name ?? config('app.name') }}.<br>
        We look forward to serving your domain management needs!
    </p>
@endsection
