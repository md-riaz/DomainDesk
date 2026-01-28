<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Domain Transfer Completed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #10b981;">Domain Transfer Completed! 🎉</h2>
        
        <p>Congratulations!</p>
        
        <p>Your domain transfer has been completed successfully:</p>
        
        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <strong>Domain:</strong> {{ $domainName }}<br>
            <strong>New Expiration Date:</strong> {{ $expiresAt }}<br>
            <strong>Status:</strong> <span style="color: #10b981;">Active</span>
        </div>
        
        <h3>What you can do now:</h3>
        <ul>
            <li>Manage your domain settings from the dashboard</li>
            <li>Update nameservers if needed</li>
            <li>Configure DNS records</li>
            <li>Set up auto-renewal</li>
        </ul>
        
        <p>Your domain is now active and under your full control. The transfer included a 1-year renewal as per ICANN policy.</p>
        
        <p>Thank you for choosing our service!</p>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
        <p style="font-size: 12px; color: #6b7280;">
            This is an automated email. Please do not reply to this message.
        </p>
    </div>
</body>
</html>
