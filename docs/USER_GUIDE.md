# User Guide

Complete guide for using DomainDesk - White-Label Domain Reseller & Client Billing Platform.

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Client Portal](#client-portal)
4. [Partner Portal](#partner-portal)
5. [Admin Panel](#admin-panel)
6. [Common Tasks](#common-tasks)
7. [FAQ](#faq)
8. [Troubleshooting](#troubleshooting)

---

## Introduction

### What is DomainDesk?

DomainDesk is a multi-tenant SaaS platform that enables domain resellers to offer domain registration, renewal, and management services under their own brand.

### User Roles

- **Super Admin**: Full system access, manages partners and registrars
- **Partner (Reseller)**: Manages their clients, branding, and pricing
- **Client (End User)**: Manages their domains and invoices

### Key Features

- ✅ Domain registration, renewal, and transfer
- ✅ DNS management (A, AAAA, CNAME, MX, TXT records)
- ✅ Nameserver management
- ✅ Wallet-based billing system
- ✅ Automated renewals
- ✅ Invoice management
- ✅ White-label branding
- ✅ Multi-tenant security

---

## Getting Started

### First Login

1. Navigate to your DomainDesk instance URL
2. Click "Login" in the top right
3. Enter your email and password
4. You'll be redirected to your dashboard based on your role

### Password Reset

1. Click "Forgot Password?" on the login page
2. Enter your email address
3. Check your email for reset link
4. Create a new password

### Profile Settings

1. Click your name in the top right
2. Select "Profile"
3. Update your information:
   - Name
   - Email
   - Password
   - Notification preferences

---

## Client Portal

The client portal allows end-users to manage their domains, view invoices, and handle billing.

### Dashboard Overview

The client dashboard displays:
- Active domains count
- Domains expiring soon (< 30 days)
- Recent invoices
- Wallet balance
- Quick actions (search domains, add funds)

### Domain Management

#### Viewing Your Domains

1. Navigate to **Domains** → **My Domains**
2. View list of all your domains with status:
   - **Active**: Domain is active and operational
   - **Expiring Soon**: Less than 30 days until expiry
   - **Expired**: Domain has expired
   - **Pending Transfer**: Transfer in progress

#### Domain Details

Click on any domain to view:
- Registration date
- Expiry date
- Auto-renewal status
- Nameservers
- DNS records
- Contact information
- Lock status

#### DNS Management

1. Go to **Domains** → Select domain → **DNS Records**
2. Click **Add Record**
3. Select record type:
   - **A Record**: IPv4 address
   - **AAAA Record**: IPv6 address
   - **CNAME**: Canonical name (alias)
   - **MX Record**: Mail server
   - **TXT Record**: Text data (SPF, DKIM, etc.)
4. Fill in the form:
   - **Name**: Subdomain or @ for root
   - **Value**: Target value
   - **TTL**: Time to live (default: 3600)
5. Click **Save**

**Example DNS Records**:
```
Type: A
Name: @
Value: 192.0.2.1
TTL: 3600

Type: CNAME
Name: www
Value: example.com
TTL: 3600

Type: MX
Name: @
Value: mail.example.com
Priority: 10
TTL: 3600
```

#### Nameserver Management

1. Go to **Domains** → Select domain → **Nameservers**
2. Choose option:
   - **Use default nameservers**: Partner's default NS
   - **Use custom nameservers**: Your own NS
3. If custom, add 2-4 nameservers:
   ```
   ns1.yourdns.com
   ns2.yourdns.com
   ```
4. Click **Update Nameservers**
5. Changes propagate within 24-48 hours

#### Domain Renewal

##### Manual Renewal

1. Go to **Domains** → Select domain
2. Click **Renew Domain**
3. Select renewal period (1-10 years)
4. Review cost
5. Click **Confirm Renewal**
6. Funds are deducted from wallet

##### Auto-Renewal

1. Go to **Domains** → Select domain
2. Toggle **Auto-Renewal** switch
3. System automatically renews 7 days before expiry
4. Ensure sufficient wallet balance

#### Domain Transfer

##### Transfer In (To Your Account)

1. Go to **Domains** → **Transfer Domain**
2. Enter domain name
3. Enter authorization code (EPP code)
4. Review transfer fee
5. Click **Initiate Transfer**
6. Approve transfer email from current registrar
7. Transfer completes in 5-7 days

##### Transfer Out (To Another Registrar)

1. Go to **Domains** → Select domain
2. Click **Get Authorization Code**
3. Code is emailed to you
4. Unlock domain if locked
5. Provide code to new registrar

### Wallet Management

#### View Balance

- Displayed on dashboard
- Navigate to **Billing** → **Wallet** for details

#### Add Funds

1. Go to **Billing** → **Wallet**
2. Click **Add Funds**
3. Enter amount
4. Select payment method
5. Complete payment
6. Funds credited immediately

#### Transaction History

1. Go to **Billing** → **Wallet** → **Transactions**
2. View all transactions:
   - Date and time
   - Type (credit/debit)
   - Amount
   - Description
   - Running balance

### Invoice Management

#### View Invoices

1. Go to **Billing** → **Invoices**
2. See all invoices with:
   - Invoice number
   - Date
   - Amount
   - Status (paid/pending/overdue)

#### Download Invoice

1. Navigate to invoice
2. Click **Download PDF**
3. Save for your records

### Support

#### Contact Partner Support

1. Go to **Support**
2. View partner contact information
3. Use provided email or phone

---

## Partner Portal

The partner portal enables resellers to manage clients, branding, pricing, and business operations.

### Dashboard Overview

The partner dashboard shows:
- Total clients
- Active domains
- Revenue this month
- Wallet balance across all clients
- Recent registrations
- Expiring domains alert

### Client Management

#### Add New Client

1. Go to **Clients** → **Add Client**
2. Fill in client information:
   - Name
   - Email
   - Phone
   - Company (optional)
3. Set initial wallet balance (optional)
4. Click **Create Client**
5. Client receives welcome email with login credentials

#### View Client List

1. Go to **Clients** → **All Clients**
2. View client information:
   - Name and email
   - Domain count
   - Wallet balance
   - Registration date
   - Status (active/suspended)

#### Client Details

Click on any client to view:
- Profile information
- Domain list
- Wallet transactions
- Invoice history
- Activity log

#### Manage Client Wallet

1. Go to **Clients** → Select client
2. Click **Adjust Wallet**
3. Select action:
   - **Add Credit**: Add funds
   - **Deduct Funds**: Remove funds
   - **Set Balance**: Set specific amount
4. Enter amount and reason
5. Click **Confirm**

### Branding Settings

#### Logo and Colors

1. Go to **Settings** → **Branding**
2. Upload logo (PNG/JPG, max 2MB)
3. Set brand colors:
   - Primary color
   - Secondary color
   - Accent color
4. Click **Save Changes**

#### Custom Domain

1. Go to **Settings** → **Domains**
2. Click **Add Custom Domain**
3. Enter your domain (e.g., domains.yourcompany.com)
4. Follow DNS verification steps:
   ```
   CNAME: yoursubdomain
   Target: cname.domaindesk.com
   ```
5. Wait for verification (up to 24 hours)
6. Set as primary domain

#### Email Settings

1. Go to **Settings** → **Branding** → **Email**
2. Configure email templates:
   - Welcome email
   - Domain expiry notice
   - Invoice notification
3. Set sender information:
   - From name: Your Company
   - From email: noreply@yourcompany.com

### Pricing Management

#### View Base Prices

1. Go to **Pricing** → **Base Prices**
2. View system base prices per TLD
3. These are the costs from the registrar

#### Set Markup Rules

1. Go to **Pricing** → **Pricing Rules**
2. Click **Add Pricing Rule**
3. Select TLD (e.g., .com, .net)
4. Choose markup type:
   - **Fixed Amount**: Add fixed price (e.g., +$5)
   - **Percentage**: Add percentage (e.g., +20%)
5. Set prices for:
   - Registration
   - Renewal
   - Transfer
6. Click **Save Rule**

**Example Pricing Rules**:
```
TLD: .com
Markup Type: Fixed
Registration: Base ($10) + $5 = $15
Renewal: Base ($12) + $5 = $17
Transfer: Base ($10) + $5 = $15

TLD: .net
Markup Type: Percentage
Registration: Base ($15) + 30% = $19.50
Renewal: Base ($18) + 30% = $23.40
Transfer: Base ($15) + 30% = $19.50
```

### Domain Settings

#### Default Nameservers

1. Go to **Settings** → **Domain Settings**
2. Set default nameservers for new registrations:
   ```
   Primary NS: ns1.yourcompany.com
   Secondary NS: ns2.yourcompany.com
   ```
3. Click **Save**

#### Auto-Renewal Settings

1. Go to **Settings** → **Domain Settings**
2. Configure auto-renewal defaults:
   - Enable by default: Yes/No
   - Renewal attempts: 3
   - Retry interval: 24 hours
3. Set expiry notifications:
   - 60 days before expiry
   - 30 days before expiry
   - 7 days before expiry
   - Day of expiry

### Reports

#### Revenue Report

1. Go to **Reports** → **Revenue**
2. Select date range
3. View breakdown by:
   - Domain registrations
   - Renewals
   - Transfers
4. Export to CSV

#### Domain Report

1. Go to **Reports** → **Domains**
2. View statistics:
   - Total domains
   - Domains by TLD
   - Expiring domains
   - Auto-renewal enabled
3. Export to CSV

#### Client Report

1. Go to **Reports** → **Clients**
2. View client metrics:
   - Top clients by domains
   - Top clients by revenue
   - Wallet balances
3. Export to CSV

---

## Admin Panel

The admin panel provides super admin access to manage the entire platform.

### Dashboard Overview

System-wide statistics:
- Total partners
- Total clients
- Total domains
- System revenue
- Active registrar connections

### Partner Management

#### Add New Partner

1. Go to **Partners** → **Add Partner**
2. Fill in partner information:
   - Company name
   - Contact name
   - Email
   - Phone
3. Set default settings:
   - Default nameservers
   - Base wallet balance
4. Click **Create Partner**

#### Manage Partners

1. Go to **Partners** → **All Partners**
2. View all partners with:
   - Company name
   - Client count
   - Domain count
   - Status
3. Click partner to view details

#### Adjust Partner Wallet

1. Select partner
2. Click **Adjust Wallet**
3. Add/deduct funds
4. Enter reason (required)
5. Confirm adjustment

### Registrar Management

#### Add Registrar

1. Go to **Registrars** → **Add Registrar**
2. Select provider:
   - ResellerClub
   - LogicBoxes
   - Mock (testing)
3. Enter API credentials:
   - API URL
   - User ID
   - API Key
4. Set default nameservers
5. Click **Save and Test Connection**

#### Configure Registrar

1. Go to **Registrars** → Select registrar
2. Update settings:
   - API credentials
   - Timeout settings
   - Rate limits
   - Enable/disable test mode
3. Click **Save Changes**

#### Test Registrar Connection

1. Go to **Registrars** → Select registrar
2. Click **Test Connection**
3. System performs:
   - API authentication test
   - Domain availability check
   - Balance inquiry
4. View test results

### System Settings

#### General Settings

1. Go to **Settings** → **General**
2. Configure:
   - Application name
   - Timezone
   - Currency
   - Date format
   - Pagination size

#### Email Settings

1. Go to **Settings** → **Email**
2. Configure SMTP:
   - Mail driver (SMTP/SendGrid/AWS SES)
   - Host and port
   - Username and password
   - Encryption
3. Test email delivery

#### Security Settings

1. Go to **Settings** → **Security**
2. Configure:
   - Password policy
   - Session timeout
   - Two-factor authentication
   - IP whitelist (optional)

### Audit Logs

1. Go to **System** → **Audit Logs**
2. View all system activity:
   - User logins
   - Domain operations
   - Wallet transactions
   - Settings changes
3. Filter by:
   - Date range
   - User
   - Action type
   - Entity type
4. Export to CSV

---

## Common Tasks

### Register a New Domain

**As Client**:
1. Go to **Domains** → **Search Domains**
2. Enter domain name
3. Click **Search**
4. Select available domain
5. Review price
6. Click **Register**
7. Confirm payment from wallet

**As Partner** (for client):
1. Go to **Clients** → Select client
2. Click **Register Domain for Client**
3. Enter domain name
4. Search availability
5. Confirm registration
6. Funds deducted from client wallet

### Renew Multiple Domains

1. Go to **Domains** → **My Domains**
2. Check domains to renew
3. Click **Bulk Actions** → **Renew Selected**
4. Select renewal period
5. Review total cost
6. Confirm renewal

### Export Domain List

1. Go to **Domains** → **My Domains**
2. Click **Export**
3. Select format (CSV/Excel)
4. Choose fields to include
5. Download file

### Change Contact Information

1. Go to **Domains** → Select domain
2. Click **Contacts**
3. Update contact details:
   - Registrant
   - Administrative
   - Technical
   - Billing
4. Click **Save Changes**
5. Verify via email if required

### Enable WHOIS Privacy

1. Go to **Domains** → Select domain
2. Click **Privacy Protection**
3. Toggle **Enable WHOIS Privacy**
4. Confirm (may have additional cost)
5. Privacy enabled within 24 hours

---

## FAQ

### General Questions

**Q: How long does domain registration take?**  
A: Most domains are registered instantly. Some TLDs may take up to 24 hours.

**Q: When should I renew my domain?**  
A: We recommend renewing at least 7 days before expiry. Enable auto-renewal for automatic renewal.

**Q: Can I transfer my domain to another registrar?**  
A: Yes, you can transfer out anytime. Get your authorization code from the domain details page.

**Q: What happens if my domain expires?**  
A: Your domain enters a grace period (typically 30 days) where you can still renew. After that, it may be deleted and become available for registration.

### Billing Questions

**Q: How does the wallet system work?**  
A: You pre-load funds into your wallet. Domain operations deduct from your balance. Add funds anytime to maintain positive balance.

**Q: What payment methods are accepted?**  
A: Payment methods are configured by your partner. Common options include credit card, PayPal, and bank transfer.

**Q: Can I get a refund?**  
A: Refund policies vary by partner. Contact your partner support for refund requests.

**Q: Why was I charged for auto-renewal?**  
A: If auto-renewal is enabled, domains automatically renew before expiry to prevent loss of domain.

### Technical Questions

**Q: How long does DNS propagation take?**  
A: DNS changes typically propagate within 24-48 hours globally, but many see changes within 4-6 hours.

**Q: Can I use my own nameservers?**  
A: Yes, you can use custom nameservers. Update them in the domain settings.

**Q: What's the difference between DNS and nameservers?**  
A: Nameservers point to the DNS servers that host your DNS records. DNS records (A, CNAME, etc.) contain the actual routing information.

**Q: Can I have multiple domains point to the same website?**  
A: Yes, use A records or CNAME records to point multiple domains to the same IP or target.

### Account Questions

**Q: How do I change my password?**  
A: Go to Profile → Security → Change Password.

**Q: Can I have multiple users for one account?**  
A: Contact your partner to add additional users to your account.

**Q: How do I cancel my account?**  
A: Contact your partner support to request account closure. Ensure all domains are transferred or expired.

---

## Troubleshooting

### Cannot Login

**Symptoms**: Login fails with incorrect credentials error

**Solutions**:
1. Verify email and password (check Caps Lock)
2. Use "Forgot Password" to reset
3. Clear browser cache and cookies
4. Try a different browser
5. Contact partner support if issue persists

### Domain Not Resolving

**Symptoms**: Website not loading after changing DNS

**Solutions**:
1. Wait 24-48 hours for DNS propagation
2. Verify DNS records are correct:
   ```bash
   nslookup yourdomain.com
   dig yourdomain.com
   ```
3. Check nameservers are correct
4. Flush local DNS cache:
   ```bash
   # Windows
   ipconfig /flushdns
   
   # Mac
   sudo dscacheutil -flushcache
   
   # Linux
   sudo systemd-resolve --flush-caches
   ```

### Email Not Received

**Symptoms**: Not receiving system emails

**Solutions**:
1. Check spam/junk folder
2. Add sender to safe senders list
3. Verify email address in profile
4. Check email notification preferences
5. Contact partner if emails still not received

### Wallet Not Updated

**Symptoms**: Wallet balance not reflecting recent transaction

**Solutions**:
1. Refresh the page
2. Clear browser cache
3. Check transaction history for pending transactions
4. Wait 5-10 minutes for processing
5. Contact support if balance still incorrect

### Cannot Upload Logo

**Symptoms**: Logo upload fails

**Solutions**:
1. Check file size (max 2MB)
2. Verify file format (PNG/JPG only)
3. Ensure image dimensions are reasonable (max 2000x2000)
4. Try a different browser
5. Compress image if too large

### DNS Records Not Saving

**Symptoms**: DNS changes not applied

**Solutions**:
1. Verify record format is correct
2. Check for conflicting records
3. Ensure TTL is valid (300-86400)
4. Remove trailing dots from values
5. Contact support if issue persists

### Performance Issues

**Symptoms**: Slow page loads

**Solutions**:
1. Clear browser cache
2. Disable browser extensions
3. Check internet connection
4. Try different browser
5. Check system status page

---

## Getting Help

### Partner Support

Contact your partner's support team (shown in your portal):
- Email: Available in Support section
- Phone: Available in Support section
- Live Chat: If enabled by partner

### Documentation

- [Installation Guide](INSTALLATION.md)
- [Development Guide](DEVELOPMENT.md)
- [API Documentation](API_DOCUMENTATION.md)
- [Architecture Guide](ARCHITECTURE.md)

### Community

- GitHub Issues: https://github.com/md-riaz/DomainDesk/issues
- Discussions: https://github.com/md-riaz/DomainDesk/discussions

---

## Tips & Best Practices

### Domain Management

1. **Enable auto-renewal** for critical domains
2. **Keep contact information up-to-date**
3. **Use DNS templates** for consistent configurations
4. **Monitor expiry dates** regularly
5. **Lock domains** to prevent unauthorized transfers

### Security

1. **Use strong passwords** (12+ characters)
2. **Enable two-factor authentication** if available
3. **Review audit logs** periodically
4. **Don't share account credentials**
5. **Be cautious with authorization codes**

### Billing

1. **Maintain positive wallet balance**
2. **Set up low balance alerts**
3. **Review invoices regularly**
4. **Keep payment information current**
5. **Download invoices for records**

### DNS Management

1. **Start with low TTL** for testing (300s)
2. **Increase TTL** after confirming changes (3600s+)
3. **Document DNS changes**
4. **Test before changing**
5. **Keep backup of DNS records**

---

**Last Updated**: January 2025  
**Version**: 1.0

For additional support, contact your partner or visit our documentation at https://github.com/md-riaz/DomainDesk
