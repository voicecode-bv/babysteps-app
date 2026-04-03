# Babysteps Backend API Specification

This document describes the API contract that the backend must implement. The mobile app (`babysteps/api-client` package) communicates with this API via HTTP using Laravel Sanctum tokens.

## Configuration

The mobile app expects these environment variables:

| Variable | Default | Description |
|---|---|---|
| `API_BASE_URL` | `https://babysteps-api.test/api/v1` | Base URL for all API endpoints |
| `API_TIMEOUT` | `15` | HTTP timeout in seconds |

All endpoints below are relative to `API_BASE_URL`.

---

## Authentication

The API uses **Laravel Sanctum** for token-based authentication. The mobile app stores the token in the device's native keychain via NativePHP SecureStorage.

### POST /auth/login

Authenticate a user and return an API token.

**Request:**

```json
{
    "email": "user@example.com",
    "password": "secret123",
    "device_name": "babysteps-mobile"
}
```

**Response 200:**

```json
{
    "token": "1|abc123def456...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "username": "johndoe",
        "email": "user@example.com",
        "avatar": "https://cdn.babysteps.app/avatars/1.jpg",
        "bio": "Parent of two",
        "locale": "nl",
        "email_verified_at": "2026-04-03T12:00:00Z",
        "created_at": "2026-04-03T12:00:00Z",
        "updated_at": "2026-04-03T12:00:00Z"
    }
}
```

**Response 401/422:**

```json
{
    "message": "The provided credentials are incorrect."
}
```

**Backend implementation notes:**
- Use `$user->createToken('babysteps-mobile')->plainTextToken`
- Validate: `email` required|email, `password` required|string, `device_name` required|string
- Rate limit: apply `throttle:5,1` (5 attempts per minute)

---

### POST /auth/register

Create a new user account and return an API token.

**Request:**

```json
{
    "name": "John Doe",
    "username": "johndoe",
    "email": "user@example.com",
    "password": "secret123",
    "password_confirmation": "secret123",
    "device_name": "babysteps-mobile"
}
```

**Response 201:**

```json
{
    "token": "1|abc123def456...",
    "user": { }
}
```

Same user object structure as login response.

**Response 422 (Validation):**

```json
{
    "message": "The email has already been taken.",
    "errors": {
        "email": ["The email has already been taken."],
        "username": ["The username has already been taken."]
    }
}
```

**Backend validation rules:**

| Field | Rules |
|---|---|
| `name` | required, string, max:255 |
| `username` | required, string, max:255, unique:users |
| `email` | required, email, max:255, unique:users |
| `password` | required, string, min:8, confirmed |
| `device_name` | required, string |

---

### GET /auth/me

Validate the current token and return the authenticated user's data. Used on app startup to verify the stored token is still valid.

**Headers:**

```
Authorization: Bearer {token}
```

**Response 200:**

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "username": "johndoe",
        "email": "user@example.com",
        "avatar": "https://cdn.babysteps.app/avatars/1.jpg",
        "bio": "Parent of two",
        "locale": "nl",
        "email_verified_at": "2026-04-03T12:00:00Z",
        "created_at": "2026-04-03T12:00:00Z",
        "updated_at": "2026-04-03T12:00:00Z"
    }
}
```

**Response 401 (Invalid/expired token):**

```json
{
    "message": "Unauthenticated."
}
```

**Backend implementation notes:**
- Use `auth:sanctum` middleware
- Return `$request->user()`
- The mobile app clears the stored token on any non-200 response

---

### POST /auth/logout

Revoke the current API token.

**Headers:**

```
Authorization: Bearer {token}
```

**Response 204:** (No content)

**Backend implementation notes:**
- Use `$request->user()->currentAccessToken()->delete()`
- The mobile app also clears the token from SecureStorage regardless of response

---

## User Object

The user object is returned by all auth endpoints and must include these fields:

```json
{
    "id": 1,
    "name": "John Doe",
    "username": "johndoe",
    "email": "user@example.com",
    "avatar": "https://cdn.babysteps.app/avatars/1.jpg",
    "bio": "Parent of two",
    "locale": "nl",
    "email_verified_at": "2026-04-03T12:00:00Z",
    "created_at": "2026-04-03T12:00:00Z",
    "updated_at": "2026-04-03T12:00:00Z"
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `id` | integer | yes | Primary key |
| `name` | string | yes | Full display name |
| `username` | string | yes | Unique username |
| `email` | string | yes | Email address |
| `avatar` | string\|null | yes | URL to avatar image |
| `bio` | string\|null | yes | Short bio/description |
| `locale` | string | yes | Preferred language (`en`, `nl`). Max 5 chars. Default `en` |
| `email_verified_at` | string\|null | yes | ISO 8601 timestamp |
| `created_at` | string | yes | ISO 8601 timestamp |
| `updated_at` | string | yes | ISO 8601 timestamp |

The mobile app uses `email` as the unique key to sync user data to the local SQLite database. The `locale` field determines the app's display language.

**Important:** Do NOT include `password` or `remember_token` in API responses.

---

## Database Schema (Backend)

The backend needs these tables. The mobile app expects the API responses to match these structures.

### users

```
id              bigint          PRIMARY KEY
name            varchar(255)    NOT NULL
username        varchar(255)    NOT NULL, UNIQUE
email           varchar(255)    NOT NULL, UNIQUE
password        varchar(255)    NOT NULL
avatar          varchar(255)    NULL
bio             text            NULL
locale          varchar(5)      NOT NULL, DEFAULT 'en'
email_verified_at timestamp     NULL
remember_token  varchar(100)    NULL
created_at      timestamp       NOT NULL
updated_at      timestamp       NOT NULL
```

### posts

```
id              bigint          PRIMARY KEY
user_id         bigint          FOREIGN KEY (users.id) ON DELETE CASCADE
media_url       varchar(255)    NOT NULL
media_type      varchar(255)    NOT NULL, DEFAULT 'image'
caption         text            NULL
location        varchar(255)    NULL
created_at      timestamp       NOT NULL
updated_at      timestamp       NOT NULL
```

`media_type` values: `image`, `video`

### comments

```
id              bigint          PRIMARY KEY
user_id         bigint          FOREIGN KEY (users.id) ON DELETE CASCADE
post_id         bigint          FOREIGN KEY (posts.id) ON DELETE CASCADE
body            text            NOT NULL
created_at      timestamp       NOT NULL
updated_at      timestamp       NOT NULL
```

### likes

```
id              bigint          PRIMARY KEY
user_id         bigint          FOREIGN KEY (users.id) ON DELETE CASCADE
post_id         bigint          FOREIGN KEY (posts.id) ON DELETE CASCADE
created_at      timestamp       NOT NULL
updated_at      timestamp       NOT NULL

UNIQUE (user_id, post_id)
```

---

## Posts

All post endpoints require `Authorization: Bearer {token}` header.

### GET /feed

Paginated feed of posts for the authenticated user's circles.

**Query parameters:**

| Param | Type | Default | Description |
|---|---|---|---|
| `page` | integer | 1 | Page number |

**Response 200:**

```json
{
    "data": [
        {
            "id": 1,
            "media_url": "https://cdn.babysteps.app/posts/1.jpg",
            "media_type": "image",
            "caption": "First steps!",
            "location": "Amsterdam",
            "created_at": "2026-04-03T12:00:00Z",
            "updated_at": "2026-04-03T12:00:00Z",
            "user": {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "avatar": "https://cdn.babysteps.app/avatars/1.jpg"
            },
            "likes_count": 5,
            "comments_count": 3
        }
    ],
    "links": {
        "first": "https://api.babysteps.app/feed?page=1",
        "last": "https://api.babysteps.app/feed?page=5",
        "prev": null,
        "next": "https://api.babysteps.app/feed?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "per_page": 10,
        "to": 10,
        "total": 42
    }
}
```

**Backend implementation notes:**
- Eager load: `user` relationship (only `id`, `name`, `username`, `avatar`)
- Include: `likes_count`, `comments_count` via `withCount`
- Order: `created_at DESC` (latest first)
- Paginate: 10 items per page (use Laravel's `paginate(10)`)
- Scope to posts visible to the authenticated user (based on circles/sharing permissions)

---

### GET /posts/{id}

Get a single post with all comments and likes.

**Response 200:**

```json
{
    "id": 1,
    "media_url": "https://cdn.babysteps.app/posts/1.jpg",
    "media_type": "image",
    "caption": "First steps!",
    "location": "Amsterdam",
    "created_at": "2026-04-03T12:00:00Z",
    "updated_at": "2026-04-03T12:00:00Z",
    "user": {
        "id": 1,
        "name": "John Doe",
        "username": "johndoe",
        "avatar": "https://cdn.babysteps.app/avatars/1.jpg"
    },
    "comments": [
        {
            "id": 1,
            "body": "So cute!",
            "created_at": "2026-04-03T12:05:00Z",
            "updated_at": "2026-04-03T12:05:00Z",
            "user": {
                "id": 2,
                "name": "Jane Smith",
                "username": "janesmith",
                "avatar": "https://cdn.babysteps.app/avatars/2.jpg"
            }
        }
    ],
    "likes": [
        {
            "id": 1,
            "user_id": 2,
            "post_id": 1,
            "created_at": "2026-04-03T12:05:00Z"
        }
    ],
    "likes_count": 5,
    "comments_count": 3
}
```

**Response 404:**

```json
{
    "message": "Post not found."
}
```

**Backend implementation notes:**
- Eager load: `user`, `comments.user`, `likes`
- Include: `likes_count`, `comments_count` via `loadCount`
- Authorize: user must have access to this post (circle membership)
- Order comments by `created_at ASC` (oldest first)

---

### POST /posts

Create a new post with media upload.

**Request:** `multipart/form-data`

| Field | Type | Required | Rules |
|---|---|---|---|
| `media` | file | yes | mimes:jpg,jpeg,png,gif,mp4,mov / max:51200 (50MB) |
| `caption` | string | no | max:2200 |
| `location` | string | no | max:255 |

**Response 201:**

```json
{
    "id": 15,
    "media_url": "https://cdn.babysteps.app/posts/15.jpg",
    "media_type": "image",
    "caption": "First steps!",
    "location": "Amsterdam",
    "created_at": "2026-04-03T12:00:00Z",
    "updated_at": "2026-04-03T12:00:00Z",
    "user": {
        "id": 1,
        "name": "John Doe",
        "username": "johndoe",
        "avatar": "https://cdn.babysteps.app/avatars/1.jpg"
    },
    "likes_count": 0,
    "comments_count": 0
}
```

**Response 422:**

```json
{
    "message": "The media field is required.",
    "errors": {
        "media": ["The media field is required."]
    }
}
```

**Backend implementation notes:**
- Store media file in a persistent storage (S3, local disk)
- Set `media_type` based on the uploaded file's MIME type (`image` for jpg/png/gif, `video` for mp4/mov)
- Generate `media_url` as a public-accessible URL
- Associate with `$request->user()->id`
- Validate file size server-side (max 50MB)

---

### DELETE /posts/{id}

Delete a post. Only the post owner can delete their own posts.

**Response 204:** (No content)

**Response 403:**

```json
{
    "message": "You are not authorized to delete this post."
}
```

**Response 404:**

```json
{
    "message": "Post not found."
}
```

**Backend implementation notes:**
- Use a Policy: `$user->id === $post->user_id`
- Cascade: deleting a post should also delete all associated comments and likes (handled by DB foreign keys)
- Also delete the media file from storage

---

## Comments

All comment endpoints require `Authorization: Bearer {token}` header.

### POST /posts/{id}/comments

Add a comment to a post.

**Request:**

```json
{
    "body": "So cute! Love those first steps!"
}
```

| Field | Type | Required | Rules |
|---|---|---|---|
| `body` | string | yes | max:1000 |

**Response 201:**

```json
{
    "id": 42,
    "body": "So cute! Love those first steps!",
    "created_at": "2026-04-03T12:10:00Z",
    "updated_at": "2026-04-03T12:10:00Z",
    "user": {
        "id": 2,
        "name": "Jane Smith",
        "username": "janesmith",
        "avatar": "https://cdn.babysteps.app/avatars/2.jpg"
    }
}
```

**Response 422:**

```json
{
    "message": "The body field is required.",
    "errors": {
        "body": ["The body field is required."]
    }
}
```

**Backend implementation notes:**
- Associate with `$request->user()->id` and the post's `id`
- User must have access to the post (circle membership) to comment
- Consider sending a notification to the post owner

---

### DELETE /comments/{id}

Delete a comment. Only the comment author or the post owner can delete a comment.

**Response 204:** (No content)

**Response 403:**

```json
{
    "message": "You are not authorized to delete this comment."
}
```

**Backend implementation notes:**
- Policy: `$user->id === $comment->user_id || $user->id === $comment->post->user_id`

---

## Likes

All like endpoints require `Authorization: Bearer {token}` header.

### POST /posts/{id}/like

Like a post. Idempotent - liking an already-liked post returns success.

**Response 201:**

```json
{
    "liked": true,
    "likes_count": 6
}
```

**Backend implementation notes:**
- Use `firstOrCreate` to prevent duplicate likes (unique constraint on `user_id` + `post_id`)
- Return the updated `likes_count` for the post
- Consider sending a notification to the post owner

---

### DELETE /posts/{id}/like

Unlike a post. Idempotent - unliking a post you haven't liked returns success.

**Response 200:**

```json
{
    "liked": false,
    "likes_count": 5
}
```

**Backend implementation notes:**
- Delete the like where `user_id` = authenticated user and `post_id` = given post
- Return updated `likes_count`

---

## All Endpoints Summary

### Public (no auth required)

| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/login` | Authenticate and receive token |
| POST | `/auth/register` | Create account and receive token |

### Authenticated (Bearer token required)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/auth/me` | Validate token and get user data |
| POST | `/auth/logout` | Revoke current token |
| GET | `/feed?page=1` | Paginated feed |
| GET | `/posts/{id}` | Single post with comments and likes |
| POST | `/posts` | Create post (multipart/form-data) |
| DELETE | `/posts/{id}` | Delete own post |
| POST | `/posts/{id}/like` | Like a post |
| DELETE | `/posts/{id}/like` | Unlike a post |
| POST | `/posts/{id}/comments` | Add comment to post |
| DELETE | `/comments/{id}` | Delete comment |

---

## Mobile App Auth Flow

```
App Start
    │
    ├── Token in SecureStorage?
    │       │
    │       ├── Yes → GET /auth/me
    │       │           │
    │       │           ├── 200 → Sync user → Show feed
    │       │           └── 401 → Clear token → Show login
    │       │
    │       └── No → Show login
    │
Login/Register Form Submit
    │
    ├── POST /auth/login or /auth/register
    │       │
    │       ├── Success → Store token in SecureStorage
    │       │              → Sync user to local SQLite
    │       │              → Auth::login(user)
    │       │              → Redirect to feed
    │       │
    │       └── Failure → Show errors on form
    │
Logout
    │
    ├── POST /auth/logout (revoke token on backend)
    ├── Clear token from SecureStorage
    ├── Auth::logout()
    ├── Invalidate session
    └── Redirect to login
```

---

## Local SQLite Database

The mobile app maintains a local SQLite database with a `users` table. This stores **only the authenticated user's data** synced from the API. It does NOT store posts, comments, or likes locally.

The local user record is created/updated via `User::updateOrCreate()` using `email` as the unique key. The password field is set to `'api-managed'` since authentication is handled by the backend API.

---

## Supported Languages

| Code | Language |
|---|---|
| `en` | English (default) |
| `nl` | Dutch |

The user's preferred language is stored in the `locale` field. The mobile app sets `app()->setLocale()` based on this field via the `SetLocale` middleware.

All backend API error messages should ideally be returned in the user's preferred language, or at minimum in English.
