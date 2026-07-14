# Database Architecture: AI-Powered LMS (SaaS)

**Database Engine:** PostgreSQL 16+
**Primary Keys:** UUID (v4)
**Data Types Note:** Heavy usage of JSONB for unstructured AI responses and Audit Trails.

---

## 1. Core & Multi-Tenancy (Phase 1)

**`tenants`**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `name` | `VARCHAR(255)` | Not Null | Organization/School name |
| `domain` | `VARCHAR(255)` | Unique, Nullable | For future custom domain routing |
| `created_at` | `TIMESTAMP` | | |
| `updated_at` | `TIMESTAMP` | | |

**`users`**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `tenant_id` | `UUID` | Foreign Key, Index | `CASCADE ON DELETE` |
| `name` | `VARCHAR(255)` | Not Null | |
| `email` | `VARCHAR(255)` | Unique, Not Null | |
| `password` | `VARCHAR(255)` | Not Null | |
| `created_at` | `TIMESTAMP` | | |
| `updated_at` | `TIMESTAMP` | | |

---

## 2. Advanced RBAC (Phase 5)
*(Dynamic Role & Permission mapping, replacing simple ENUM)*

**`roles`**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `tenant_id` | `UUID` | Foreign Key, Index | `CASCADE ON DELETE` (Roles are school-specific) |
| `name` | `VARCHAR(255)` | Not Null | e.g., "Head Instructor" |
| `slug` | `VARCHAR(255)` | Not Null | e.g., "head-instructor" |

**`permissions`**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `name` | `VARCHAR(255)` | Not Null | e.g., "Grade Submissions" |
| `slug` | `VARCHAR(255)` | Unique, Not Null | e.g., "grade-submissions" |

**`permission_role` (Pivot)**
| Column | Type | Constraints |
| :--- | :--- | :--- |
| `role_id` | `UUID` | Foreign Key (`CASCADE ON DELETE`) |
| `permission_id` | `UUID` | Foreign Key (`CASCADE ON DELETE`) |

**`role_user` (Pivot)**
| Column | Type | Constraints |
| :--- | :--- | :--- |
| `user_id` | `UUID` | Foreign Key (`CASCADE ON DELETE`) |
| `role_id` | `UUID` | Foreign Key (`CASCADE ON DELETE`) |

---

## 3. Content Engine (Phase 2)

**`courses`**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `tenant_id` | `UUID` | Foreign Key, Index | `CASCADE ON DELETE` |
| `title` | `VARCHAR(255)` | Not Null | |
| `description` | `TEXT` | Nullable | |
| `created_at` | `TIMESTAMP` | | |
| `updated_at` | `TIMESTAMP` | | |

**`modules`**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `course_id` | `UUID` | Foreign Key, Index | `CASCADE ON DELETE` |
| `title` | `VARCHAR(255)` | Not Null | |
| `order` | `INTEGER` | Default: 0 | Enforces UI sorting |
| `created_at` | `TIMESTAMP` | | |
| `updated_at` | `TIMESTAMP` | | |

**`lessons`**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `module_id` | `UUID` | Foreign Key, Index | `CASCADE ON DELETE` |
| `title` | `VARCHAR(255)` | Not Null | |
| `content` | `TEXT` | Nullable | Text-based lesson material |
| `video_embed_url`| `VARCHAR(255)` | Nullable | Link to external video (YT/Vimeo) |
| `created_at` | `TIMESTAMP` | | |
| `updated_at` | `TIMESTAMP` | | |

**`lesson_user` (Pivot - Progress Tracking)**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `user_id` | `UUID` | Foreign Key | `CASCADE ON DELETE` |
| `lesson_id` | `UUID` | Foreign Key | `CASCADE ON DELETE` |
| `completed_at` | `TIMESTAMP` | Not Null | Temporal analytic data |
*(Composite Unique Key required on `user_id` + `lesson_id` to prevent race condition duplicates)*

---

## 4. Assessment & AI Grader Pipeline (Phase 3 & 4)

**`assignments`**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `lesson_id` | `UUID` | Foreign Key, Index | `CASCADE ON DELETE` |
| `title` | `VARCHAR(255)` | Not Null | |
| `prompt_question`| `TEXT` | Not Null | The question for students / context for LLM |
| `rubric` | `JSONB` | Nullable | Dynamic grading rules for AI injection |
| `max_score` | `INTEGER` | Default: 100 | Ceiling limit for AI score |
| `created_at` | `TIMESTAMP` | | |
| `updated_at` | `TIMESTAMP` | | |

**`submissions` (The Core Transaction Table)**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `assignment_id` | `UUID` | Foreign Key, Index | `CASCADE ON DELETE` |
| `user_id` | `UUID` | Foreign Key, Index | `CASCADE ON DELETE` |
| `student_answer` | `TEXT` | Not Null | Raw input to be sent to Anthropic API |
| `status` | `VARCHAR(50)` | Default: 'pending'| States: pending, processing, graded, failed |
| `ai_score` | `DECIMAL(5,2)`| Nullable | Result from Async Queue Worker |
| `ai_feedback` | `JSONB` | Nullable | Complex structured response from LLM |
| `created_at` | `TIMESTAMP` | | |
| `updated_at` | `TIMESTAMP` | | |

---

## 5. Security & Observability (Phase 6)

**`audit_logs` (Forensic Record)**
| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `UUID` | Primary Key | Default: `uuid_generate_v4()` |
| `tenant_id` | `UUID` | Foreign Key, Index | Data isolation for logs |
| `user_id` | `UUID` | Foreign Key, Index | The actor who made the change |
| `event` | `VARCHAR(50)` | Not Null | ENUM: created, updated, deleted |
| `auditable_type` | `VARCHAR(255)` | Not Null | Polymorphic model class (e.g., Submission) |
| `auditable_id` | `UUID` | Index, Not Null | Polymorphic model ID |
| `old_values` | `JSONB` | Nullable | Pre-modification state |
| `new_values` | `JSONB` | Nullable | Post-modification state |
| `ip_address` | `VARCHAR(45)` | Nullable | Security footprinting |
| `created_at` | `TIMESTAMP` | Not Null | |