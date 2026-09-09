# Notification

> A reliable asynchronous notification delivery platform built with Laravel.

Notification 是一套以 **Laravel** 為核心開發的非同步通知派送平台。

系統提供統一 REST API，讓 E-Commerce、CRM、Booking System 等外部服務建立通知，並透過 Queue Worker 非同步處理 Email 與 Webhook 派送。

本專案重點是實作 Backend 系統常見的可靠性問題，包括：

- API Key Authentication
- Asynchronous Processing
- Idempotency
- Queue / Worker
- Retry & Exponential Backoff
- Delivery Tracking
- Attempt Tracking
- Failure Handling
- Rate Limiting
- Structured Logging
- AWS Deployment

---

## Architecture

```text
Client Application
        |
        | REST API
        | Bearer API Key
        v
+-----------------------+
|    Laravel Backend    |
+-----------+-----------+
            |
            +--------------------+
            |                    |
            v                    v
         MySQL                  Queue
                                  |
                                  v
                           SendNotificationJob
                                  |
                                  v
                            Queue Worker
                                  |
                                  v
                       Notification Delivery
                                  |
                         +--------+--------+
                         |                 |
                         v                 v
                       Email            Webhook
                         |                 |
                         v                 v
                      Provider        HTTP Provider
```

Notification lifecycle：

```text
Notification
     |
     v
Delivery
     |
     v
Attempt
     |
     +------ Success ------> Sent
     |
     +------ Retryable ----> Retry
     |
     +------ Permanent ----> Failed
```

---

## Core Flow

建立通知：

```text
Client
  |
  | POST /api/notifications
  v
API Key Authentication
  |
  v
Idempotency Check
  |
  v
Database Transaction
  |
  +-- Notification
  |
  +-- Delivery
  |
  +-- Idempotency Key
  |
  v
COMMIT
  |
  v
Queue
  |
  v
Worker
  |
  v
Provider
```

API Request 接受成功不代表通知已經成功派送。

實際 Delivery 由 Queue Worker 非同步處理。

---

## Idempotency

Notification API 支援：

```http
Idempotency-Key: order-ORD-001-paid
```

用來避免 Client 因 Timeout 或 Retry 重複建立 Notification。

```text
Same Key + Same Request
        |
        v
Return Existing Notification

Same Key + Different Request
        |
        v
409 Conflict
```

唯一範圍：

```text
project_id + idempotency_key
```

Request Payload 會經過 normalization 後計算 SHA-256 Hash，用來判斷相同 Idempotency Key 是否代表相同 Request。

---

## Tech Stack

### Backend

- PHP
- Laravel
- MySQL
- Redis
- Laravel Queue

### Frontend

- React
- TypeScript

### Infrastructure

Target AWS deployment：

- Amazon EC2
- Amazon RDS MySQL
- Amazon SQS
- Amazon CloudWatch
- Amazon SES

---

## Database

Core domain tables：

```text
projects
api_keys
notification_templates
notifications
notification_deliveries
notification_attempts
idempotency_keys
```

主要關係：

```text
Project
 |
 +-- API Keys
 |
 +-- Notification Templates
 |
 +-- Notifications
 |       |
 |       +-- Deliveries
 |               |
 |               +-- Attempts
 |
 +-- Idempotency Keys
```

---

## API

### Project Management

```http
GET    /api/projects
POST   /api/projects
GET    /api/projects/{uuid}
PUT    /api/projects/{uuid}
PATCH  /api/projects/{uuid}
DELETE /api/projects/{uuid}
```

### API Key Management

```http
GET    /api/projects/{project}/api-keys
POST   /api/projects/{project}/api-keys
GET    /api/projects/{project}/api-keys/{apiKey}
DELETE /api/projects/{project}/api-keys/{apiKey}
```

### Notification

```http
POST /api/notifications
```

Example：

```json
{
    "event_type": "order.paid",
    "channel": "email",
    "recipient": "customer@example.com",
    "data": {
        "customer_name": "王小明",
        "order_no": "ORD-001",
        "amount": 1280
    }
}
```

Authentication：

```http
Authorization: Bearer <API_KEY>
```

---

## Project Structure

```text
Notification/
├── backend/
│   └── Laravel REST API
│
├── frontend/
│   └── React Administration Dashboard
│
├── docs/
│   ├── architecture.md
│   └── database-design.md
│
├── PROJECT.md
└── README.md
```

---

## Reliability Design

The service is designed around several reliability principles.

### Persist Before Processing

```text
Database Transaction
        |
        v
COMMIT
        |
        v
Queue
```

Queue jobs are dispatched only after persistent state has been successfully committed.

### At-Least-Once Processing

Queue jobs may execute more than once because of worker crashes, network failures, or queue retries.

The system therefore does not assume exactly-once execution.

### Idempotent Requests

Client retries are protected using：

```text
Idempotency-Key
+
Request SHA-256
+
Database UNIQUE Constraint
```

### Failure Visibility

Every notification is designed to be traceable through：

```text
Notification
     |
     v
Delivery
     |
     v
Attempt #1
     |
     +-- Failed
     |
     v
Attempt #2
     |
     +-- Success
```

---

## Documentation

Detailed technical documentation is available under：

```text
docs/
```

Including：

- System Architecture
- Database Design
- API Design
- Retry Strategy
- Idempotency Design
- AWS Architecture

Project development rules and scope：

```text
PROJECT.md
```

---

## Roadmap

```text
Notification Core
        ↓
Idempotency
        ↓
Queue / Worker
        ↓
Delivery Attempt
        ↓
Provider Abstraction
        ↓
Retry / Exponential Backoff
        ↓
Webhook
        ↓
Rate Limiting
        ↓
Structured Logging
        ↓
Automated Testing
        ↓
AWS Deployment
        ↓
React Dashboard
```

---

## Goals

This project focuses on demonstrating backend engineering practices around：

- API Design
- Database Transaction
- Concurrency
- Idempotency
- Asynchronous Processing
- Queue Architecture
- Failure Recovery
- Observability
- Security
- Cloud Deployment

The goal is to build a small but production-oriented backend system rather than a feature-heavy CRUD application.
