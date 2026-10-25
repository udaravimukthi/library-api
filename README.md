# Library API

A small REST API for a library: manage books and members, borrow and return books,
and enforce the lending rules (loan limit, overdue block, fines).

Built with Laravel 13, PHP 8.4 and SQLite.

---

## Setup

Requirements: **PHP 8.4+** (with the `pdo_sqlite` extension) and Composer.

```bash
git clone https://github.com/udaravimukthi/library-api.git
cd library-api
composer install
cp .env.example .env            # Windows CMD: copy .env.example .env
php artisan key:generate
php artisan migrate --seed      # answer "yes" to create database/database.sqlite
php artisan serve
```

The API is now at `http://127.0.0.1:8000/api`.

### Run the tests

```bash
php artisan test
```

Tests use an in-memory SQLite database, so they never touch your local data.

### Seeded data

`--seed` creates data you can try straight away:

| Type | id | Details |
|---|---|---|
| Book | 1 | *Clean Code*: 3 copies, 1 currently on loan |
| Book | 2 | *Refactoring*: 1 copy |
| Book | 3–12 | random books |
| Member | 1 | `demo@example.com`: has one returned loan |
| Member | 2 | `late@example.com`: has a book 5 days overdue, so **cannot borrow** |
| Member | 3–5 | random members |

---

## Business rules

| Rule | Value |
|---|---|
| Loan period | 14 days |
| Max active loans per member | 3 |
| Member with an overdue loan | cannot borrow until it is returned |
| Book with 0 available copies | cannot be borrowed |
| Fine | 50 LKR per **started** day late (3 days and 1 minute = 4 days) |

---

## Endpoints

Send `Accept: application/json` with every request.

| Method | URL | Description | Success |
|---|---|---|---|
| GET | `/api/books?author=&per_page=` | List books (paginated, optional author filter, max 100 per page) | 200 |
| POST | `/api/books` | Add a book | 201 |
| GET | `/api/books/{id}` | Show a book | 200 |
| POST | `/api/members` | Register a member | 201 |
| GET | `/api/members/{id}` | Show a member | 200 |
| GET | `/api/members/{id}/loans?status=active\|returned` | A member's loan history | 200 |
| POST | `/api/loans` | Borrow a book | 201 |
| POST | `/api/loans/{id}/return` | Return a book | 200 |

### Examples

**Add a book**

```http
POST /api/books
{ "title": "Refactoring", "author": "Martin Fowler", "isbn": "9780134757599", "total_copies": 2 }
```

- `isbn`: ISBN-10 or ISBN-13, digits only (no dashes), must be unique.
- `total_copies`: between 1 and 1000. `available_copies` starts equal to it.

**Register a member**

```http
POST /api/members
{ "name": "Nimal", "email": "Nimal@Example.com" }
```

The email is trimmed and lowercased before validation, so `Nimal@Example.com` and
`nimal@example.com` count as the same member.

**Borrow a book**

```http
POST /api/loans
{ "member_id": 1, "book_id": 2 }
```

```json
{
  "data": {
    "id": 3,
    "book": { "id": 2, "title": "Refactoring", "...": "..." },
    "member_id": 1,
    "borrowed_at": "2026-10-26T10:00:00+00:00",
    "due_at": "2026-11-09T10:00:00+00:00",
    "returned_at": null,
    "status": "active",
    "days_overdue": 0,
    "fine": 0
  }
}
```

`status` is `active`, `overdue` or `returned`. `days_overdue` and `fine` are calculated
when you request them (see design decisions).

---

## Errors

All errors are JSON.

| Status | When | Body |
|---|---|---|
| 404 | Unknown book, member or loan id | `{ "message": "..." }` |
| 422 | Validation failed | `{ "message": "...", "errors": { "field": ["..."] } }` |
| 422 | A lending rule was broken | `{ "message": "...", "error": "<code>" }` |

Lending rule error codes. These are stable, so a client can check `error` without parsing the message:

| `error` | Meaning |
|---|---|
| `member_has_overdue_loans` | Member must return their overdue book(s) first |
| `loan_limit_reached` | Member already has 3 active loans |
| `book_unavailable` | No copies of this book are available |
| `loan_already_returned` | This loan was already returned |

---

## Design decisions

- **Business rules live in `app/Services/LoanService.php`.** Controllers only handle HTTP.
  The rules can be reused from a console command or queue job and are tested in one place.
- **Row locking.** `borrow()` and `return()` run inside a database transaction and lock
  the rows they change (`lockForUpdate()`). Without this, two people borrowing the last
  copy at the same moment could both pass the "copies > 0" check.
- **No stored `status` or `fine` columns.** Both depend on the current date, so they are
  calculated from `due_at` / `returned_at`. A `null` `returned_at` means "still borrowed",
  so the data can't get out of sync.
- **Database constraints back up validation.** `isbn` and `email` are unique in the database,
  and loans use foreign keys with `restrictOnDelete()` so loan history can't be orphaned.
- **Index on `(member_id, returned_at)`.** "Active loans of a member" is the most frequent query.
- **Form Requests and API Resources.** Validation and the JSON shape are kept out of the
  controllers, and the response stays stable even if columns change.
- **Eager loading** (`with('book')`) on loan lists avoids N+1 queries.