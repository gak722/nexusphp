# NexusPHP Documentation

## Introduction

Welcome to NexusPHP! NexusPHP is a modern, dependency-free PHP framework designed for high performance, strict typing, and developer happiness. Built natively for PHP 8.2+, it draws inspiration from the elegance of frameworks like Laravel while maintaining a strictly zero-dependency architecture.

Our philosophy is simple: provide a robust, enterprise-grade toolkit without the overhead of massive dependency trees. We believe that a framework should be transparent, lightning-fast, and enjoyable to use. Whether you are building a high-throughput JSON API, a robust command-line application, or a full-stack web application, NexusPHP offers a beautiful, fluent API to help you build powerful applications with confidence.

## Key Features

- **Zero Dependencies**: NexusPHP is completely self-contained out of the box. No third-party packages, no bloat—just pure, heavily optimized PHP code.
- **Modern PHP 8.2+**: Built from the ground up for modern PHP, taking full advantage of strict typing, attributes, enumerations, and readonly properties.
- **Powerful Dependency Injection**: A lightweight, PSR-11 compatible Service Container with support for auto-wiring, transient, scoped, and singleton lifetimes.
- **Eloquent-Style ORM**: A flexible, high-performance ActiveRecord-style Model engine featuring dynamic properties, dirty state tracking, and comprehensive relationship support (HasOne, HasMany, BelongsTo, BelongsToMany).
- **Advanced Routing**: An intuitive routing engine that makes defining application endpoints a breeze, featuring route groups, middleware pipelines, and RESTful resource routing.
- **Robust Security**: Built-in, zero-configuration protection with CSRF middleware, dynamic Security Headers (CSP), robust password hashing, rate limiting, and a dual-guard Authentication system supporting both Stateful Sessions and Stateless JWTs.
- **Comprehensive Validation**: A deeply integrated validation engine that supports complex nested dot-notation, rule registries, and declarative validation using PHP 8 Attributes.
- **Rich Support Utilities**: A vast array of helper classes to make development easier, including fluent Collections, comprehensive String and Array manipulation, and intuitive HTTP and Mail clients.

## Documentation Index

The following sections will guide you through all aspects of building applications with NexusPHP. These guides reflect the exact capabilities of the current framework implementation:

- **Getting Started**
  - [Installation & Configuration](installation.md)
  - [Directory Structure](directory-structure.md)
- **Architecture Concepts**
  - [Request Lifecycle](lifecycle.md)
  - [Service Container](container.md)
- **The Basics**
  - [Routing](routing.md)
  - [Middleware](middleware.md)
  - [Controllers](controllers.md)
  - [Requests & Responses](requests-responses.md)
  - [Views](views.md)
- **Database & ORM**
  - [Database Connections](database.md)
  - [Query Builder](query-builder.md)
  - [Entity Query Builder](entity-query-builder.md)
  - [Migrations & Seeding](migrations.md)
  - [Models & Relationships](orm.md)
- **Security**
  - [Authentication (Session & JWT)](authentication.md)
  - [CSRF Protection](csrf.md)
  - [Encryption & Hashing](encryption.md)
  - [Rate Limiting](rate-limiting.md)
- **Digging Deeper**
  - [Validation & Data Binding](validation.md)
  - [Collections](collections.md)
  - [HTTP Client](http-client.md)
  - [Mail](mail.md)
  - [Queues & Jobs](queues.md)
  - [Cache](cache.md)
  - [Events & Broadcasting](events.md)
  - [Nexus Console (CLI)](console.md)
