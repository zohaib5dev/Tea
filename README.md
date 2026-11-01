# Aio

A social app with a Laravel 12 backend at the repository root and a standalone Vue 3 frontend in `frontend/`.

## Requirements

- PHP 8.2+
- Composer
- Node.js 20+
- SQLite (or another Laravel-supported database)

## Local development

1. Install and initialize the Laravel backend:

   ```powershell
   composer install
   Copy-Item .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan storage:link
   ```

   Configure the database, mail, session, and broadcasting settings in `.env` as needed.

2. Start the backend from the repository root:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

3. In a separate terminal, install and start the Vue frontend:

   ```powershell
   Set-Location frontend
   npm install
   npm run dev -- --port 5173
   ```

   Open the Vite URL shown in the terminal. The Vite server proxies `/api` and `/storage` to `http://127.0.0.1:8000`. Set `LARAVEL_URL` before starting Vite if the backend uses another address.

## Production build

Build the Vue app from `frontend/`:

```powershell
Set-Location frontend
npm install
npm run build
```

The compiled assets and Vite manifest are written to `public/frontend/`. Laravel serves the compiled Vue app at `/`; the Laravel backend and Vue assets can therefore be deployed together while remaining in separate source directories.

## API

The JSON API is rooted at `/api` and uses Laravel's session authentication and CSRF protection. Fetch `/api/csrf-cookie` before login or registration. The frontend does this automatically.

- Authentication: `GET /csrf-cookie`, `POST /register`, `POST /login`, `GET /user`, `POST /logout`
- Feed and posts: `GET /feed`, `POST /posts`, `PATCH|DELETE /posts/{post}`, `POST /posts/{post}/like`
- Comments: `GET|POST /posts/{post}/comments`, `DELETE /comments/{comment}`
- Profiles: `GET /users/{user}`, `PATCH /profile`, `POST /profile/photo`
- Friends: `GET /friends`, `POST|DELETE /friends/{user}`, `POST /friendships/{friendship}/accept|decline`
- Messaging: `GET|POST /conversations`, `GET /conversations/{conversation}`, `GET|POST /conversations/{conversation}/messages`, `POST /conversations/{conversation}/read`
- Message removal: `DELETE /messages/{message}` (everyone) or `DELETE /messages/{message}/me` (your view)
- Call signaling: `POST /conversations/{conversation}/call`

All mutation endpoints validate input and enforce the existing post visibility, friendship, ownership, and conversation-participant rules.

## Tests

```sh
php artisan test
```
