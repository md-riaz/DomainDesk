# API Documentation

REST API documentation for DomainDesk - White-Label Domain Reseller Platform.

## Table of Contents

1. [Introduction](#introduction)
2. [Authentication](#authentication)
3. [API Endpoints](#api-endpoints)
4. [Rate Limiting](#rate-limiting)
5. [Error Handling](#error-handling)
6. [Webhooks](#webhooks)
7. [Code Examples](#code-examples)

---

## Introduction

### Base URL

```
Production: https://api.yourdomain.com/api/v1
Development: http://localhost:8000/api/v1
```

### API Versioning

The API uses URL-based versioning. Current version: `v1`

### Content Type

All requests and responses use JSON:

```
Content-Type: application/json
Accept: application/json
```

---

## Authentication

### API Key Authentication

DomainDesk uses Bearer token authentication.

#### Obtaining API Key

1. Login to your account
2. Navigate to **Settings** → **API Keys**
3. Click **Generate New API Key**
4. Copy and securely store your API key

#### Using API Key

Include the API key in the Authorization header:

```http
Authorization: Bearer your_api_key_here
```

#### Example Request

```bash
curl -X GET https://api.yourdomain.com/api/v1/domains \
  -H "Authorization: Bearer your_api_key_here" \
  -H "Accept: application/json"
```

### Partner Context

For partners making requests on behalf of clients:

```http
X-Partner-Id: partner_uuid
X-Client-Id: client_uuid
```

---

## API Endpoints

### Domain Endpoints

#### Check Domain Availability

Check if a domain is available for registration.

**Endpoint**: `GET /domains/check`

**Query Parameters**:
- `domain` (required): Domain name to check

**Request**:
```bash
curl -X GET "https://api.yourdomain.com/api/v1/domains/check?domain=example.com" \
  -H "Authorization: Bearer your_api_key"
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "domain": "example.com",
    "available": true,
    "price": {
      "registration": 15.00,
      "renewal": 17.00,
      "transfer": 15.00,
      "currency": "USD"
    },
    "premium": false
  }
}
```

#### Register Domain

Register a new domain.

**Endpoint**: `POST /domains/register`

**Request Body**:
```json
{
  "domain": "example.com",
  "period": 1,
  "nameservers": [
    "ns1.example.com",
    "ns2.example.com"
  ],
  "contacts": {
    "registrant": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+1.1234567890",
      "address": "123 Main St",
      "city": "New York",
      "state": "NY",
      "zip": "10001",
      "country": "US"
    }
  },
  "auto_renew": true
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": "domain_uuid",
    "domain": "example.com",
    "status": "active",
    "registered_at": "2025-01-28T12:00:00Z",
    "expires_at": "2026-01-28T12:00:00Z",
    "auto_renew": true,
    "nameservers": [
      "ns1.example.com",
      "ns2.example.com"
    ]
  },
  "message": "Domain registered successfully"
}
```

#### List Domains

Retrieve list of domains.

**Endpoint**: `GET /domains`

**Query Parameters**:
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Results per page (default: 15, max: 100)
- `status` (optional): Filter by status (active, expiring, expired)
- `search` (optional): Search domain name

**Request**:
```bash
curl -X GET "https://api.yourdomain.com/api/v1/domains?page=1&per_page=20&status=active" \
  -H "Authorization: Bearer your_api_key"
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": "domain_uuid",
      "domain": "example.com",
      "status": "active",
      "registered_at": "2025-01-28T12:00:00Z",
      "expires_at": "2026-01-28T12:00:00Z",
      "auto_renew": true
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  }
}
```

#### Get Domain Details

Retrieve detailed information about a specific domain.

**Endpoint**: `GET /domains/{domain_id}`

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": "domain_uuid",
    "domain": "example.com",
    "status": "active",
    "registered_at": "2025-01-28T12:00:00Z",
    "expires_at": "2026-01-28T12:00:00Z",
    "auto_renew": true,
    "locked": false,
    "nameservers": [
      "ns1.example.com",
      "ns2.example.com"
    ],
    "contacts": {
      "registrant": {
        "name": "John Doe",
        "email": "john@example.com"
      }
    },
    "dns_records_count": 5
  }
}
```

#### Renew Domain

Renew an existing domain.

**Endpoint**: `POST /domains/{domain_id}/renew`

**Request Body**:
```json
{
  "period": 1
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": "domain_uuid",
    "domain": "example.com",
    "old_expiry": "2026-01-28T12:00:00Z",
    "new_expiry": "2027-01-28T12:00:00Z",
    "cost": 17.00,
    "invoice_id": "invoice_uuid"
  },
  "message": "Domain renewed successfully"
}
```

#### Transfer Domain

Initiate domain transfer.

**Endpoint**: `POST /domains/transfer`

**Request Body**:
```json
{
  "domain": "example.com",
  "auth_code": "ABC123XYZ",
  "period": 1,
  "auto_renew": true
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": "transfer_uuid",
    "domain": "example.com",
    "status": "pending",
    "initiated_at": "2025-01-28T12:00:00Z",
    "estimated_completion": "2025-02-04T12:00:00Z"
  },
  "message": "Domain transfer initiated"
}
```

### DNS Endpoints

#### List DNS Records

Get DNS records for a domain.

**Endpoint**: `GET /domains/{domain_id}/dns`

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": "record_uuid",
      "type": "A",
      "name": "@",
      "value": "192.0.2.1",
      "ttl": 3600,
      "priority": null
    },
    {
      "id": "record_uuid_2",
      "type": "MX",
      "name": "@",
      "value": "mail.example.com",
      "ttl": 3600,
      "priority": 10
    }
  ]
}
```

#### Create DNS Record

Add a new DNS record.

**Endpoint**: `POST /domains/{domain_id}/dns`

**Request Body**:
```json
{
  "type": "A",
  "name": "www",
  "value": "192.0.2.1",
  "ttl": 3600
}
```

**Response** (201 Created):
```json
{
  "success": true,
  "data": {
    "id": "record_uuid",
    "type": "A",
    "name": "www",
    "value": "192.0.2.1",
    "ttl": 3600,
    "created_at": "2025-01-28T12:00:00Z"
  },
  "message": "DNS record created successfully"
}
```

#### Update DNS Record

Update an existing DNS record.

**Endpoint**: `PUT /domains/{domain_id}/dns/{record_id}`

**Request Body**:
```json
{
  "value": "192.0.2.2",
  "ttl": 7200
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": "record_uuid",
    "type": "A",
    "name": "www",
    "value": "192.0.2.2",
    "ttl": 7200,
    "updated_at": "2025-01-28T12:00:00Z"
  },
  "message": "DNS record updated successfully"
}
```

#### Delete DNS Record

Delete a DNS record.

**Endpoint**: `DELETE /domains/{domain_id}/dns/{record_id}`

**Response** (200 OK):
```json
{
  "success": true,
  "message": "DNS record deleted successfully"
}
```

### Nameserver Endpoints

#### Get Nameservers

Retrieve nameservers for a domain.

**Endpoint**: `GET /domains/{domain_id}/nameservers`

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "nameservers": [
      "ns1.example.com",
      "ns2.example.com"
    ],
    "using_default": false
  }
}
```

#### Update Nameservers

Update domain nameservers.

**Endpoint**: `PUT /domains/{domain_id}/nameservers`

**Request Body**:
```json
{
  "nameservers": [
    "ns1.newhost.com",
    "ns2.newhost.com"
  ]
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "nameservers": [
      "ns1.newhost.com",
      "ns2.newhost.com"
    ]
  },
  "message": "Nameservers updated successfully"
}
```

### Wallet Endpoints

#### Get Wallet Balance

Retrieve wallet balance.

**Endpoint**: `GET /wallet/balance`

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "balance": 250.00,
    "currency": "USD",
    "last_transaction_at": "2025-01-28T12:00:00Z"
  }
}
```

#### Get Wallet Transactions

List wallet transactions.

**Endpoint**: `GET /wallet/transactions`

**Query Parameters**:
- `page` (optional): Page number
- `per_page` (optional): Results per page
- `type` (optional): Filter by type (credit, debit)

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": "transaction_uuid",
      "type": "debit",
      "amount": -15.00,
      "balance_after": 235.00,
      "description": "Domain registration: example.com",
      "created_at": "2025-01-28T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 150
  }
}
```

#### Add Funds

Add funds to wallet (admin/partner only).

**Endpoint**: `POST /wallet/add-funds`

**Request Body**:
```json
{
  "amount": 100.00,
  "description": "Account top-up"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "transaction_id": "transaction_uuid",
    "amount": 100.00,
    "new_balance": 350.00
  },
  "message": "Funds added successfully"
}
```

### Invoice Endpoints

#### List Invoices

Get list of invoices.

**Endpoint**: `GET /invoices`

**Query Parameters**:
- `page` (optional): Page number
- `status` (optional): Filter by status (paid, pending, overdue)

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": "invoice_uuid",
      "invoice_number": "INV-2025-0001",
      "amount": 15.00,
      "status": "paid",
      "issued_at": "2025-01-28T12:00:00Z",
      "due_at": "2025-02-28T12:00:00Z",
      "paid_at": "2025-01-28T13:00:00Z"
    }
  ]
}
```

#### Get Invoice Details

Retrieve specific invoice.

**Endpoint**: `GET /invoices/{invoice_id}`

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": "invoice_uuid",
    "invoice_number": "INV-2025-0001",
    "status": "paid",
    "amount": 15.00,
    "items": [
      {
        "description": "Domain Registration - example.com",
        "quantity": 1,
        "unit_price": 15.00,
        "total": 15.00
      }
    ],
    "issued_at": "2025-01-28T12:00:00Z",
    "paid_at": "2025-01-28T13:00:00Z"
  }
}
```

---

## Rate Limiting

### Rate Limits

- **Authenticated requests**: 60 requests per minute
- **Domain searches**: 30 requests per minute
- **DNS updates**: 20 requests per minute

### Rate Limit Headers

Response includes rate limit information:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1706443200
```

### Rate Limit Exceeded

When rate limit is exceeded (HTTP 429):

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Try again in 45 seconds.",
    "retry_after": 45
  }
}
```

---

## Error Handling

### HTTP Status Codes

- `200 OK`: Request succeeded
- `201 Created`: Resource created successfully
- `204 No Content`: Request succeeded with no response body
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication failed
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error
- `503 Service Unavailable`: Service temporarily unavailable

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable error message",
    "details": {
      "field": ["Specific validation error"]
    }
  }
}
```

### Common Error Codes

| Code | Description |
|------|-------------|
| `AUTHENTICATION_FAILED` | Invalid or expired API key |
| `INSUFFICIENT_PERMISSIONS` | User lacks required permissions |
| `RESOURCE_NOT_FOUND` | Requested resource doesn't exist |
| `VALIDATION_ERROR` | Request validation failed |
| `INSUFFICIENT_FUNDS` | Wallet balance too low |
| `DOMAIN_UNAVAILABLE` | Domain not available for registration |
| `DOMAIN_LOCKED` | Domain is locked |
| `REGISTRAR_ERROR` | Error from domain registrar |
| `RATE_LIMIT_EXCEEDED` | Too many requests |

### Validation Errors (422)

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "domain": ["The domain field is required."],
      "period": ["The period must be between 1 and 10."]
    }
  }
}
```

---

## Webhooks

### Webhook Events

Subscribe to real-time events via webhooks.

#### Available Events

- `domain.registered`: Domain successfully registered
- `domain.renewed`: Domain renewed
- `domain.transferred`: Domain transfer completed
- `domain.expiring`: Domain expiring soon (30 days)
- `domain.expired`: Domain expired
- `invoice.created`: New invoice created
- `invoice.paid`: Invoice paid
- `wallet.low_balance`: Wallet balance below threshold

### Webhook Configuration

Configure webhooks in **Settings** → **API** → **Webhooks**:

1. Add webhook URL
2. Select events to subscribe
3. Generate webhook secret
4. Save configuration

### Webhook Payload

```json
{
  "event": "domain.registered",
  "timestamp": "2025-01-28T12:00:00Z",
  "data": {
    "domain_id": "domain_uuid",
    "domain": "example.com",
    "registered_at": "2025-01-28T12:00:00Z"
  }
}
```

### Webhook Security

Verify webhook signature using HMAC SHA256:

```python
import hmac
import hashlib

def verify_webhook(payload, signature, secret):
    computed = hmac.new(
        secret.encode(),
        payload.encode(),
        hashlib.sha256
    ).hexdigest()
    return hmac.compare_digest(computed, signature)
```

Check signature in `X-Webhook-Signature` header.

---

## Code Examples

### PHP (Laravel)

```php
use Illuminate\Support\Facades\Http;

$apiKey = 'your_api_key';
$baseUrl = 'https://api.yourdomain.com/api/v1';

// Check domain availability
$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
    'Accept' => 'application/json',
])->get("{$baseUrl}/domains/check", [
    'domain' => 'example.com',
]);

if ($response->successful()) {
    $data = $response->json();
    if ($data['data']['available']) {
        echo "Domain is available!";
    }
}

// Register domain
$response = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
])->post("{$baseUrl}/domains/register", [
    'domain' => 'example.com',
    'period' => 1,
    'nameservers' => [
        'ns1.example.com',
        'ns2.example.com',
    ],
    'auto_renew' => true,
]);

$domain = $response->json();
```

### Python

```python
import requests

API_KEY = 'your_api_key'
BASE_URL = 'https://api.yourdomain.com/api/v1'

headers = {
    'Authorization': f'Bearer {API_KEY}',
    'Accept': 'application/json',
}

# Check domain availability
response = requests.get(
    f'{BASE_URL}/domains/check',
    params={'domain': 'example.com'},
    headers=headers
)

data = response.json()
if data['data']['available']:
    print('Domain is available!')

# Register domain
response = requests.post(
    f'{BASE_URL}/domains/register',
    json={
        'domain': 'example.com',
        'period': 1,
        'nameservers': [
            'ns1.example.com',
            'ns2.example.com'
        ],
        'auto_renew': True
    },
    headers=headers
)

domain = response.json()
```

### JavaScript (Node.js)

```javascript
const axios = require('axios');

const apiKey = 'your_api_key';
const baseUrl = 'https://api.yourdomain.com/api/v1';

const client = axios.create({
  baseURL: baseUrl,
  headers: {
    'Authorization': `Bearer ${apiKey}`,
    'Accept': 'application/json',
  },
});

// Check domain availability
async function checkDomain(domain) {
  const response = await client.get('/domains/check', {
    params: { domain },
  });
  return response.data;
}

// Register domain
async function registerDomain(domain, period = 1) {
  const response = await client.post('/domains/register', {
    domain,
    period,
    nameservers: [
      'ns1.example.com',
      'ns2.example.com',
    ],
    auto_renew: true,
  });
  return response.data;
}

// Usage
(async () => {
  const available = await checkDomain('example.com');
  if (available.data.available) {
    const result = await registerDomain('example.com');
    console.log('Domain registered:', result.data.domain);
  }
})();
```

### cURL

```bash
# Check domain availability
curl -X GET "https://api.yourdomain.com/api/v1/domains/check?domain=example.com" \
  -H "Authorization: Bearer your_api_key" \
  -H "Accept: application/json"

# Register domain
curl -X POST "https://api.yourdomain.com/api/v1/domains/register" \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "domain": "example.com",
    "period": 1,
    "nameservers": ["ns1.example.com", "ns2.example.com"],
    "auto_renew": true
  }'

# Get wallet balance
curl -X GET "https://api.yourdomain.com/api/v1/wallet/balance" \
  -H "Authorization: Bearer your_api_key"

# Add DNS record
curl -X POST "https://api.yourdomain.com/api/v1/domains/domain_uuid/dns" \
  -H "Authorization: Bearer your_api_key" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "A",
    "name": "www",
    "value": "192.0.2.1",
    "ttl": 3600
  }'
```

---

## Best Practices

1. **Store API keys securely**: Never commit API keys to version control
2. **Use environment variables**: Store keys in `.env` files
3. **Implement retry logic**: Handle transient errors with exponential backoff
4. **Cache responses**: Cache domain availability checks
5. **Handle rate limits**: Implement rate limit backoff
6. **Verify webhooks**: Always verify webhook signatures
7. **Log API calls**: Keep audit trail of API usage
8. **Use HTTPS**: Always use HTTPS in production

---

**Last Updated**: January 2025  
**API Version**: 1.0

For support, contact: api-support@domaindesk.com
