# Architecture

## 1. Overview

Notification Delivery Service 是一套非同步通知派送系統。

外部業務系統，例如：

- E-Commerce
- CRM
- ERP
- Booking System
- Internal Services

可以透過統一 REST API 建立 Notification Request。

Notification Service 負責：

- API Authentication
- Request Validation
- Idempotency
- Notification Persistence
- Queue Dispatch
- Asynchronous Processing
- Template Rendering
- Provider Delivery
- Retry
- Delivery Tracking
- Attempt Tracking
- Failure Handling
- Rate Limiting
- Logging
- Monitoring

核心設計目標：

- Reliable
- Asynchronous
- Idempotent
- Observable
- Retryable
- Extensible

---

## 2. High-Level Architecture

Local / Development architecture：

```text
                   Client Application
                          |
                          |
                          | HTTPS / REST API
                          v
                +----------------------+
                |   Laravel Backend    |
                |                      |
                | Notification API     |
                +----------+-----------+
                           |
          +----------------+----------------+
          |                |                |
          v                v                v
       MySQL             Redis          Rate Limiter
          |                |
          |                |
          |                | Queue
          |                v
          |        +-------------------+
          |        |   Queue Worker    |
          |        +---------+---------+
          |                  |
          |                  v
          |        +-------------------+
          |        | Channel / Provider|
          |        +---------+---------+
          |                  |
          |        +---------+---------+
          |        |                   |
          v        v                   v
      Delivery    Email             Webhook
      Records     Provider           Provider
                  |                   |
                  v                   v
                SMTP / SES        Mock Receiver
```

系統主要分成以下幾個部分：

- Laravel Backend：提供 API 與核心 Business Logic
- MySQL：保存 Notification、Delivery、Attempt 等持久化資料
- Redis：Queue、Rate Limit、Cache
- Queue Worker：非同步處理通知派送
- Email Provider：Email 通知實際派送
- Webhook Provider：Webhook 通知實際派送
- Mock Receiver：模擬外部 Webhook 系統

---

## 3. Core Components

### 3.1 Client Application

Client Application 是呼叫 Notification Delivery Service 的外部業務系統。

例如：

```text
Order Service
CRM
ERP
Booking System
```

Client 不需要直接串接 AWS SES、SMTP 或其他 Provider。

Client 只需要呼叫：

```http
POST /api/notifications
```

Notification Delivery Service 負責後續：

```text
Persist
↓
Queue
↓
Worker
↓
Provider
↓
Retry / Success / Failure
```

---

### 3.2 Laravel Backend

Laravel Backend 路徑：

```text
backend/
```

主要負責：

- API Routing
- API Key Authentication
- Request Validation
- Idempotency Check
- Notification Creation
- Delivery Creation
- Queue Dispatch
- Notification Query
- Retry API
- Template Management

HTTP Request 不應直接等待外部 Provider 完成通知派送。

正確流程：

```text
HTTP Request
    |
    v
Laravel Backend
    |
    v
Persist Notification
    |
    v
Dispatch Queue
    |
    v
202 Accepted
```

實際通知由 Queue Worker 非同步處理。

---

### 3.3 MySQL

MySQL 保存系統持久化狀態。

核心資料表：

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
 |      |
 |      +-- Deliveries
 |             |
 |             +-- Attempts
 |
 +-- Idempotency Keys
```

詳細欄位設計：

```text
docs/database-design.md
```

---

### 3.4 Redis

Local / Development  先使用 Redis。

主要用途：

```text
Queue

Rate Limiting

Cache
```

Notification API 建立通知後，會將 Job 放進 Redis Queue。

Queue Worker 再從 Redis 取得 Job。

AWS Production 後續可以將 Queue 改為：

```text
Amazon SQS
```

---

### 3.5 Queue Worker

Queue Worker 負責實際通知派送。

主要流程：

```text
Receive Job
    |
    v
Load Notification
    |
    v
Load Delivery
    |
    v
Create Attempt
    |
    v
Select Channel
    |
    v
Select Provider
    |
    v
Send Notification
    |
    +----------------+
    |                |
 Success           Failure
    |                |
    v                v
 Mark Sent      Classify Error
                     |
              +------+------+
              |             |
          Retryable      Permanent
              |             |
              v             v
            Retry         Failed
```

Queue Worker 必須處理：

- Provider Timeout
- Provider Temporary Failure
- Permanent Failure
- Retry
- Status Update
- Attempt Tracking

---

## 4. Notification Domain Model

核心 Domain 分成三層：

```text
Notification
    |
    v
Delivery
    |
    v
Attempt
```

---

### 4.1 Notification

Notification 代表：

> 一次 Business-level 的通知需求。

例如：

```text
訂單 ORD-10001 付款完成，
需要通知 customer@example.com
```

資料存放：

```text
notifications
```

Notification 建立成功只代表系統接受通知需求。

不代表 Provider 已經成功派送。

---

### 4.2 Delivery

Delivery 代表：

> Notification 透過哪一個 Provider 進行實際派送。

例如：

```text
Notification #10001
    |
    v
AWS SES
```

資料存放：

```text
notification_deliveries
```

例如：

```text
provider = ses
status = pending
attempt_count = 0
```

未來可以支援：

```text
SES

SMTP

SendGrid

Webhook

Twilio
```

---

### 4.3 Attempt

Attempt 代表：

> Delivery 實際呼叫 Provider 的一次操作。

例如：

```text
Delivery #20001
 |
 +-- Attempt 1 -> HTTP 503
 |
 +-- Attempt 2 -> Timeout
 |
 +-- Attempt 3 -> Success
```

資料存放：

```text
notification_attempts
```

這樣可以完整保留每一次 Retry 的歷史。

---

## 5. Notification Creation Flow

以電商付款成功 Email 為例。

Client Request：

```http
POST /api/notifications
Authorization: Bearer nfs_xxxxxxxxx
Idempotency-Key: order-ORD-10001-paid
```

Body：

```json
{
    "event_type": "order.paid",
    "channel": "email",
    "template": "order_paid",
    "recipient": "customer@example.com",
    "data": {
        "order_no": "ORD-10001",
        "amount": 1280
    }
}
```

處理流程：

```text
Client
  |
  v
API Route
  |
  v
API Key Authentication
  |
  v
Request Validation
  |
  v
Idempotency Check
  |
  v
Database Transaction
  |
  +-- Create Notification
  |
  +-- Create Delivery
  |
  +-- Create Idempotency Key
  |
  v
COMMIT
  |
  v
Dispatch Queue Job
  |
  v
Return 202 Accepted
```

API Response：

```json
{
    "id": "noti_xxxxxxxxx",
    "status": "queued"
}
```

HTTP Status：

```text
202 Accepted
```

202 表示：

> Request 已成功接受並排入非同步處理。

不代表通知已經成功送達。

---

## 6. Transaction and Queue Flow

Notification 建立流程必須使用 Database Transaction。

例如：

```text
BEGIN

Create notifications

Create notification_deliveries

Create idempotency_keys

COMMIT
```

只有 Database Commit 成功之後才能 Dispatch Queue Job。

正確：

```text
Database
    |
    v
COMMIT
    |
    v
Queue
```

錯誤：

```text
Queue
    |
    v
Worker 開始執行
    |
    v
但 Database 尚未 Commit
```

Laravel 可以使用：

```php
DB::afterCommit(...)
```

或 Queue Job 的 after commit 機制。

---

## 7. Delivery and Retry Flow

Worker 開始派送前，建立一筆：

```text
notification_attempts
```

例如：

```text
attempt_no = 1
status = processing
```

接著呼叫 Provider。

成功：

```text
Provider
   |
   v
Success
   |
   v
Attempt = success
   |
   v
Delivery = sent
   |
   v
Notification = sent
```

失敗：

```text
Provider
   |
   v
Failure
   |
   v
Classify Error
```

---

### 7.1 Retryable Error

例如：

```text
Connection Timeout

HTTP 408

HTTP 429

HTTP 500

HTTP 502

HTTP 503

HTTP 504
```

流程：

```text
Attempt 1
   |
   | 503
   v
Failed
   |
   v
Wait 30 seconds
   |
   v
Attempt 2
```

初始 Retry Policy：

```text
Attempt 1

30 seconds

Attempt 2

120 seconds

Attempt 3

600 seconds

Attempt 4

Final Failure
```

每次 Retry 都必須新增一筆：

```text
notification_attempts
```

不能覆蓋上一筆 Attempt。

---

### 7.2 Non-Retryable Error

例如：

```text
HTTP 400

HTTP 401

HTTP 403

HTTP 404

Invalid Email

Invalid Payload
```

流程：

```text
Attempt Failed
    |
    v
Permanent Error
    |
    v
Delivery = failed
    |
    v
Notification = failed
```

Permanent Error 不應重複 Retry。

---

## 8. Idempotency

Client 建立 Notification 時可以提供：

```http
Idempotency-Key: order-ORD-10001-paid
```

唯一範圍：

```text
project_id + idempotency_key
```

第一次：

```text
Request
   |
   v
Idempotency Key Not Found
   |
   v
Create Notification #10001
```

第二次相同 Request：

```text
Request
   |
   v
Idempotency Key Found
   |
   v
Return Notification #10001
```

不建立：

```text
Notification #10002
```

這可以避免：

```text
Client Timeout
↓
Client Retry
↓
Duplicate Email
```

Database 必須建立 Unique Constraint：

```text
project_id + idempotency_key
```

不能只靠程式先查詢再 Insert。

---

## 9. Provider Architecture

Business Logic 不應直接依賴 AWS SES 或 HTTP Client。

Provider 必須抽象化。

Email：

```text
EmailProvider
     |
     +-- SesProvider
```

Webhook：

```text
WebhookProvider
     |
     +-- HttpWebhookProvider
```

Channel：

```text
NotificationChannel
        |
        +-- EmailChannel
        |
        +-- WebhookChannel
```

概念流程：

```text
Notification
    |
    v
Channel
    |
    v
Provider
```

這樣未來可以加入：

```text
SendGrid

SMS

LINE

Slack
```

而不需要重寫 Notification 核心流程。

---

## 10. Mock Receiver

Mock Receiver 路徑：

```text
mock-receiver/
```

用途是模擬外部 Webhook Endpoint。

測試：

```text
Success

Client Error

Rate Limit

Server Error

Timeout
```

建議提供：

```text
POST /success

POST /bad-request

POST /rate-limit

POST /server-error

POST /timeout
```

例如：

```text
/success
→ HTTP 200

/bad-request
→ HTTP 400

/rate-limit
→ HTTP 429

/server-error
→ HTTP 503

/timeout
→ Delay response
```

這可以讓 Retry、Timeout、Failure Handling 有穩定可重現的測試環境。

---

## 11. AWS Target Architecture

正式部署 AWS 後，預計架構：

```text
                         Internet
                            |
                            v
                        Route 53
                            |
                            v
                           ALB
                            |
                            v
                  +-------------------+
                  |   Laravel API     |
                  | EC2 / ECS Fargate |
                  +---------+---------+
                            |
             +--------------+--------------+
             |              |              |
             v              v              v
          RDS MySQL     ElastiCache        SQS
                                             |
                                             v
                                        Queue Worker
                                             |
                                  +----------+----------+
                                  |                     |
                                  v                     v
                                SES                  Webhook
                                  |
                                  v
                              Recipient
```

主要 AWS Service：

```text
Route 53
= DNS

ALB
= HTTP / HTTPS Entry Point

EC2 / ECS
= Laravel API / Worker

RDS MySQL
= Persistent Database

ElastiCache Redis
= Cache / Rate Limit

SQS
= Production Queue

SES
= Email Provider

CloudWatch
= Logs / Metrics / Monitoring
```

Local：

```text
Redis Queue
```

AWS：

```text
SQS Queue
```

Laravel Application 不應因 Queue Driver 改變而重寫 Business Logic。

---

## 12. Reliability Principles

### Asynchronous Processing

Provider 呼叫不能阻塞 Notification Creation API。

```text
API
↓
Queue
↓
Worker
↓
Provider
```

---

### Persist Before Processing

Notification 必須先寫入 Database，再開始非同步處理。

```text
Persist
↓
Commit
↓
Queue
```

---

### At-Least-Once Processing

Queue 系統可能因為：

```text
Worker Crash

Network Error

Visibility Timeout

Process Restart
```

讓同一 Job 被執行超過一次。

因此系統不能假設：

```text
Exactly Once
```

應設計為：

```text
At-Least-Once
+
Idempotent Processing
```

---

### Observability

系統必須能回答：

```text
Notification 是否建立成功？

目前是 queued、processing、sent 還是 failed？

使用哪個 Provider？

總共嘗試幾次？

每一次失敗原因是什麼？

是否有 Retry？

最後什麼時間成功？

如果失敗，最後失敗原因是什麼？
```

資料來源：

```text
notifications

notification_deliveries

notification_attempts

application logs
```

---

## 13. Status Lifecycle

Notification：

```text
pending
   |
   v
queued
   |
   v
processing
   |
   +-------> sent
   |
   +-------> failed
```

如果發生 Retryable Error：

```text
processing
   |
   v
queued
   |
   v
processing
```

MVP 不需要增加過多 Status，例如：

```text
retrying

waiting_retry

provider_pending
```

保持狀態機簡單。

---

## 14. End-to-End Flow

完整流程：

```text
Client Application
       |
       | POST /api/notifications
       |
       v
API Key Authentication
       |
       v
Request Validation
       |
       v
Idempotency Check
       |
       v
Database Transaction
       |
       +-- notifications
       |
       +-- notification_deliveries
       |
       +-- idempotency_keys
       |
       v
COMMIT
       |
       v
Dispatch Queue Job
       |
       v
202 Accepted
       |
       |
       +---------------- HTTP Request End
       
       
Queue Worker
       |
       v
Load Notification
       |
       v
Create notification_attempt
       |
       v
Select Channel
       |
       v
Select Provider
       |
       v
Send Notification
       |
       +----------------------------+
       |                            |
     Success                      Failure
       |                            |
       v                            v
Attempt = success             Attempt = failed
       |                            |
       v                            v
Delivery = sent               Error Classification
       |                            |
       v                     +------+------+
Notification = sent          |             |
                          Retryable      Permanent
                              |             |
                              v             v
                            Queue        Delivery failed
                              |             |
                              v             v
                         Next Attempt   Notification failed
```

---

## 15. Architecture Decision Summary

| Decision | Reason |
|---|---|
| Async Processing | 避免外部 Provider latency 阻塞 API |
| Queue Worker | 解耦 API 與實際 Notification Delivery |
| Notification / Delivery / Attempt 分離 | 完整保存通知與 Retry History |
| Idempotency Key | 避免 Client Retry 產生 Duplicate Notification |
| Database Transaction | 避免 Notification 建立出現 Partial State |
| Dispatch After Commit | 避免 Worker 查不到尚未 Commit 的資料 |
| Retry Classification | Permanent Error 不做無意義 Retry |
| Provider Abstraction | 降低 AWS SES 等 Vendor Coupling |
| Redis Queue Local | 快速開發與測試 |
| SQS Queue AWS | Production Async Processing |
| Mock Receiver | 穩定重現 Webhook Failure Scenario |
| Structured Records | 提升 Debugging 與 Observability |

---

## 16. MVP Architecture Scope

第一階段：

```text
Laravel API

MySQL

Redis

Queue Worker

Email Channel

Email Provider

Notification

Delivery

Attempt

Basic Retry
```

第二階段：

```text
Idempotency

Rate Limiting

Webhook Channel

Mock Receiver

Exponential Backoff

Structured Logging

Integration Tests
```

第三階段：

```text
AWS Deployment

Route 53

ALB

EC2 / ECS

RDS

ElastiCache

SQS

SES

CloudWatch
```

Frontend 不應阻塞 Backend MVP。

Backend 核心流程：

```text
API
↓
Database
↓
Queue
↓
Worker
↓
Provider
↓
Attempt / Retry / Result
```

完成之後再進入主要 Frontend 開發。