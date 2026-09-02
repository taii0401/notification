# Notification Delivery Service

## 1. Project Overview

Notification Delivery Service 是一套以 Backend Engineering 為核心的非同步通知派送平台。

系統提供統一 API 給其他業務系統使用，例如：

- E-Commerce
- CRM
- ERP
- Booking System
- Internal Services

業務系統不需要直接處理 Email、Webhook 等通知 Provider，而是將 Notification Request 送進本系統。

Notification Delivery Service 負責：

- API Authentication
- Notification Validation
- Idempotency
- Queue Dispatch
- Asynchronous Processing
- Template Rendering
- Provider Delivery
- Retry
- Exponential Backoff
- Delivery Tracking
- Attempt Tracking
- Failure Handling
- Rate Limiting
- Observability

---

# 2. Project Goal

本專案主要目的不是製作一般 CRUD 系統，而是展示 Senior Backend Engineer 所需要的系統設計與工程能力。

核心展示內容：

- RESTful API Design
- Queue Architecture
- Asynchronous Processing
- Idempotency
- Retry Strategy
- Exponential Backoff
- Rate Limiting
- Database Design
- Failure Handling
- Provider Abstraction
- Structured Logging
- Automated Testing
- Docker Development Environment
- AWS Deployment
- Monitoring

---

# 3. Primary Use Case

第一個完整 Use Case：

## E-Commerce Order Paid

當電商訂單付款完成：

```text
Order Service
    |
    | POST /api/notifications
    |
    v
Notification API
    |
    v
Database
    |
    v
Queue
    |
    v
Worker
    |
    v
Email Provider
    |
    v
Customer