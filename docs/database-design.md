# Database Design

## 1. Overview

Notification Delivery Service 是一套負責非同步派送通知的服務。

系統主要負責：

- 接收 Notification API Request
- API Key 驗證
- Notification Template 管理
- Email / Webhook 通知派送
- Queue 非同步處理
- Retry
- Delivery Tracking
- Attempt Tracking
- Idempotency
- Failure Handling

---

# 2. Tables

系統主要包含以下資料表：

1. projects
2. api_keys
3. notification_templates
4. notifications
5. notification_deliveries
6. notification_attempts
7. idempotency_keys

Laravel Queue 額外使用：

- jobs
- job_batches
- failed_jobs

---

# 3. projects

代表使用 Notification Delivery Service 的專案或系統。

例如：

- E-Commerce
- CRM
- ERP
- Booking System

## Columns

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint unsigned | NO | Primary Key |
| uuid | char(36) | NO | 對外使用 UUID |
| name | varchar(100) | NO | Project Name |
| slug | varchar(100) | NO | Project Slug |
| status | varchar(20) | NO | active / inactive |
| created_at | timestamp | YES | Created Time |
| updated_at | timestamp | YES | Updated Time |

## Index

- PRIMARY KEY (id)
- UNIQUE (uuid)
- UNIQUE (slug)
- INDEX (status)

---

# 4. api_keys

Project 呼叫 Notification API 時使用的 API Key。

API Key 不儲存明文，只儲存 Hash。

## Columns

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint unsigned | NO | Primary Key |
| project_id | bigint unsigned | NO | Project ID |
| name | varchar(100) | NO | API Key 名稱 |
| key_prefix | varchar(20) | NO | 顯示用 Prefix |
| key_hash | varchar(255) | NO | API Key Hash |
| status | varchar(20) | NO | active / inactive |
| last_used_at | timestamp | YES | 最後使用時間 |
| expires_at | timestamp | YES | 過期時間 |
| created_at | timestamp | YES | Created Time |
| updated_at | timestamp | YES | Updated Time |

## Foreign Key

project_id → projects.id

## Index

- INDEX (project_id)
- UNIQUE (key_hash)
- INDEX (status)
- INDEX (expires_at)

---

# 5. notification_templates

通知模板。

不同 Channel 可以擁有不同 Template。

例如：

- order_paid / email
- order_paid / webhook

## Columns

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint unsigned | NO | Primary Key |
| project_id | bigint unsigned | NO | Project ID |
| code | varchar(100) | NO | Template Code |
| channel | varchar(30) | NO | email / webhook |
| name | varchar(100) | NO | Template Name |
| subject | varchar(255) | YES | Email Subject |
| content | longtext | NO | Template Content |
| status | varchar(20) | NO | active / inactive |
| created_at | timestamp | YES | Created Time |
| updated_at | timestamp | YES | Updated Time |

## Foreign Key

project_id → projects.id

## Index

- INDEX (project_id)
- INDEX (channel)
- INDEX (status)
- UNIQUE (project_id, code, channel)

---

# 6. notifications

Notification Request 的核心資料。

代表一次邏輯上的通知。

Notification 建立成功不代表已經派送成功。

## Columns

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint unsigned | NO | Primary Key |
| uuid | char(36) | NO | External Notification ID |
| project_id | bigint unsigned | NO | Project ID |
| template_id | bigint unsigned | YES | Template ID |
| event_type | varchar(100) | NO | Event Type |
| channel | varchar(30) | NO | email / webhook |
| recipient | varchar(500) | NO | 收件者 |
| payload | json | YES | Template Variables |
| metadata | json | YES | 額外 Metadata |
| status | varchar(30) | NO | pending / queued / processing / sent / failed |
| scheduled_at | timestamp | YES | 排定派送時間 |
| processed_at | timestamp | YES | Worker Processing Time |
| sent_at | timestamp | YES | Successfully Sent Time |
| failed_at | timestamp | YES | Final Failure Time |
| created_at | timestamp | YES | Created Time |
| updated_at | timestamp | YES | Updated Time |

## Foreign Key

project_id → projects.id

template_id → notification_templates.id

## Index

- UNIQUE (uuid)
- INDEX (project_id)
- INDEX (template_id)
- INDEX (event_type)
- INDEX (channel)
- INDEX (status)
- INDEX (scheduled_at)
- INDEX (project_id, status)
- INDEX (project_id, created_at)

---

# 7. notification_deliveries

記錄 Notification 實際派送狀態。

Notification 是 Logical Request。

Delivery 是 Physical Delivery。

未來可以支援 Provider Fallback。

例如：

SES Failed
→ SendGrid Success

## Columns

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint unsigned | NO | Primary Key |
| notification_id | bigint unsigned | NO | Notification ID |
| provider | varchar(50) | NO | ses / smtp / webhook |
| status | varchar(30) | NO | pending / processing / sent / failed |
| attempt_count | unsigned integer | NO | Retry Count |
| provider_message_id | varchar(255) | YES | Provider Message ID |
| last_error | text | YES | Latest Error |
| sent_at | timestamp | YES | Successfully Sent Time |
| failed_at | timestamp | YES | Failed Time |
| created_at | timestamp | YES | Created Time |
| updated_at | timestamp | YES | Updated Time |

## Foreign Key

notification_id → notifications.id

## Index

- INDEX (notification_id)
- INDEX (provider)
- INDEX (status)
- INDEX (notification_id, status)

---

# 8. notification_attempts

記錄每一次實際的 Delivery Attempt。

例如：

Attempt 1 → Timeout
Attempt 2 → HTTP 503
Attempt 3 → Success

## Columns

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint unsigned | NO | Primary Key |
| delivery_id | bigint unsigned | NO | Delivery ID |
| attempt_no | unsigned integer | NO | Attempt Number |
| status | varchar(30) | NO | processing / success / failed |
| request_payload | json | YES | Request Snapshot |
| response_code | integer | YES | HTTP / Provider Response Code |
| response_body | text | YES | Response |
| error_type | varchar(100) | YES | Error Category |
| error_message | text | YES | Error Message |
| started_at | timestamp | YES | Start Time |
| finished_at | timestamp | YES | Finish Time |
| created_at | timestamp | YES | Created Time |
| updated_at | timestamp | YES | Updated Time |

## Foreign Key

delivery_id → notification_deliveries.id

## Index

- INDEX (delivery_id)
- INDEX (status)
- UNIQUE (delivery_id, attempt_no)

---

# 9. idempotency_keys

防止 Client 因 Timeout 或 Retry 重複建立 Notification。

Client Request：

Idempotency-Key: order-1001-paid

相同 Project + Idempotency Key 只能建立一次 Notification。

## Columns

| Column | Type | Nullable | Description |
|---|---|---|---|
| id | bigint unsigned | NO | Primary Key |
| project_id | bigint unsigned | NO | Project ID |
| idempotency_key | varchar(255) | NO | Client Idempotency Key |
| request_hash | varchar(64) | NO | Request SHA-256 |
| notification_id | bigint unsigned | YES | Notification ID |
| expires_at | timestamp | YES | Expiration Time |
| created_at | timestamp | YES | Created Time |
| updated_at | timestamp | YES | Updated Time |

## Foreign Key

project_id → projects.id

notification_id → notifications.id

## Index

- UNIQUE (project_id, idempotency_key)
- INDEX (notification_id)
- INDEX (expires_at)

---

# 10. Relationships

projects
│
├── api_keys
│
├── notification_templates
│
├── notifications
│     │
│     └── notification_deliveries
│             │
│             └── notification_attempts
│
└── idempotency_keys
      │
      └── notification

---

# 11. Notification Status

Notification：

- pending
- queued
- processing
- sent
- failed

Delivery：

- pending
- processing
- sent
- failed

Attempt：

- processing
- success
- failed

---

# 12. Design Decisions

## UUID

Internal Relation 使用 bigint Primary Key。

External API 使用 UUID。

避免：

- 暴露 DB Increment ID
- Client 依賴 Database Implementation

---

## JSON Payload

Notification Payload 使用 JSON。

例如：

```json
{
    "order_no": "ORD-10001",
    "customer_name": "John",
    "amount": 1280
}