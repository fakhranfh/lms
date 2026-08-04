# Course Restructure — Forum

## Definition

Forum is the discussion space for a Course, made up of threads created by users. Each thread can receive comments from other participants, and each comment can be liked.

## Structure

### 1. Thread
Created by a user, consists of a title, a description, and a comment count.

- `forum_id` (FK -> forum, see [course-restructure-overview.md](course-restructure-overview.md))
- `user_id` (thread creator)
- `title` (string, required)
- `description` (text, required)
- `comments_count` (integer, derived/cached from the number of comments on the thread)
- `created_at` / `updated_at`

### 2. Comment
Belongs to a thread, can be liked by other users.

- `thread_id`
- `user_id` (comment author)
- `body` (text, required)
- `likes_count` (integer, derived/cached from the number of likes)
- `created_at` / `updated_at`

### 3. Comment Like
Records which user liked which comment (one like per user per comment).

- `comment_id`
- `user_id`
- `created_at`

## Schema Summary (proposal)

```
forum_threads
  - id
  - forum_id
  - user_id
  - title (string)
  - description (text)
  - comments_count (integer, default 0)
  - created_at / updated_at

forum_comments
  - id
  - thread_id
  - user_id
  - body (text)
  - likes_count (integer, default 0)
  - created_at / updated_at

forum_comment_likes
  - id
  - comment_id
  - user_id
  - created_at
  - unique (comment_id, user_id)
```

`comments_count` on `forum_threads` and `likes_count` on `forum_comments` are denormalized counters, kept in sync when a comment/like is created or deleted.

## Notes

This document is a structural plan/documentation only. No migration/model/Livewire component has been implemented yet.
