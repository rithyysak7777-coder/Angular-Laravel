# Fearless Atelier - Quick Architecture & Setup Summary

> For the comprehensive documentation with full architecture breakdown, flows, and API reference, refer to **`README.md`**.

---

## 1. Default Seeded Credentials

The database seeder (`DatabaseSeeder.php`) seeds **strictly 2 accounts** (1 Admin, 1 Customer) and **zero fake orders**:

| Role | User Name | Email Address | Password | Telephone | Access Level |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Admin** | `Admin` | `admin@gmail.com` | `password123` | `+855 12 000 001` | Full Master Console (`/admin`): Product CRUD, Photo Uploads, Category Management, Customer Directory, Order Status Management |
| **Customer** | `User` | `customer@gmail.com` | `password123` | `+855 98 765 432` | Storefront (`/`): Catalog Browsing, Add to Bag, Checkout, VIP Account Portal (`/account`), Order Tracking, 4h Order Cancellation, Profile & Avatar Upload |

> **Alternative Admin Fallback**: `admin@fearlesssoul.com` / `password123`

---

## 2. Quick Setup & Run Commands

### Step 1: Clone Repository
```bash
git clone <your-repository-url>
cd FinalProject
```

### Step 2: Backend Setup (Laravel 13 & PostgreSQL)
```bash
cd Backend/ecommerce_website
composer install
cp .env.example .env
# Ensure .env has: DB_CONNECTION=pgsql, DB_DATABASE=laravel_final, DB_USERNAME=postgres, DB_PASSWORD=your_password
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```
* **Backend API Base**: `http://localhost:8000/api`

### Step 3: Frontend Setup (Angular 21)
Open a new terminal:
```bash
cd Frontend/ecommerce_website
npm install
npm start
```
* **Storefront**: `http://localhost:4200`
* **Admin Console**: `http://localhost:4200/admin` (or `/admin/login`)
* **VIP Customer Account**: `http://localhost:4200/account`

---

## 3. How to Use & Test (Customer & Admin Test Scenarios)

### Customer Test Scenarios
1. **Add to Bag & Stock Limit**:
   - Go to `http://localhost:4200` -> Click **+ ADD TO BAG** on any product.
   - Bag drawer slides out showing price, size, and live in-stock badge (`15 in stock`).
   - Stepper caps at available stock and displays warning banner.
2. **Checkout, Vouchers & Payment**:
   - Click **SECURE CHECKOUT →** -> Log in with `customer@gmail.com` / `password123`.
   - Apply voucher `FEARLESS20` (20% off) or `VIP10` (15% off).
   - Select **ABA PAYWAY (KHQR)** (interactive QR code), **CREDIT CARD** (card visual), or **COD**.
   - Click **PLACE ORDER NOW**.
3. **Stock Cut Verification**:
   - Return to storefront or check PostgreSQL: Purchased quantity is immediately deducted from inventory stock.
4. **4-Hour Order Cancellation & Profile**:
   - Go to `/account` -> View placed order with live 4-hour countdown timer.
   - Click **CANCEL ORDER** to cancel.
   - Click **EDIT PROFILE PAGE** to upload custom avatar photo and update telephone.

### Admin Test Scenarios
1. **Role Security**:
   - Try accessing `/admin` as a customer -> Blocked by `adminGuard`.
   - Go to `/admin/login` -> Sign in with `admin@gmail.com` / `password123` -> Master Console unlocked.
2. **Inventory Management**:
   - Go to `/admin/products` -> Edit stock from 15 to 25 -> Storefront immediately shows 25 in stock!
   - Click **+ Add Product** -> Fill title, price, stock, select image file -> Saves product to PostgreSQL.
3. **Fulfillment Workflow**:
   - Go to `/admin/orders` -> Update customer order status from `PENDING` to `PROCESSING` or `DELIVERED`.
4. **Customer Directory & Roles**:
   - Go to `/admin/customers` -> Verify `ADMIN` and `CUSTOMER` badges, test deleting or adding a user.

---

## 4. Laravel Security Architecture (Phases 1 - 4)

1. **Phase 1: 1-Day Session Persistence (1440 Minutes)**
   - Database Session Driver (`SESSION_DRIVER=database`) persists session records in the PostgreSQL `sessions` table.
   - Session lifetime is configured to `1440` minutes (24 hours) with `SESSION_EXPIRE_ON_CLOSE=false`.
   - API tokens & Sanctum expiration set to `1440` minutes (`config/sanctum.php` & `TokenService.php`).
2. **Phase 2: Route Anti-Spam / Refresh Limiting (10 Hits = 1-Minute Ban)**
   - Registered `RateLimiter::for('anti-spam')` in `bootstrap/app.php` allowing 10 hits/minute by IP.
   - Excessive requests receive an HTTP 429 Too Many Requests response with `retry_after_seconds`.
   - Attached to sensitive authentication endpoints (`throttle:anti-spam`).
3. **Phase 3: Login Brute-Force Protection (5 Failed Attempts = 1-Minute Ban)**
   - Tracks failed attempts by email and IP (`RateLimiter::tooManyAttempts($throttleKey, 5)`).
   - Failed attempts increment hit counter with 60-second decay and return HTTP 401 with remaining attempts.
   - Exceeding 5 attempts returns an HTTP 429 lockout response with exact remaining seconds countdown.
   - Successful login clears lockout counter and regenerates session ID to prevent session fixation.
4. **Phase 4: Session Cleanup & Re-login Handling on Logout**
   - On logout, clears API token in database (`api_token = null` and token revocation).
   - Flushes web guard (`Auth::guard('web')->logout()`).
   - Invalidates session (`$request->session()->invalidate()`) and regenerates CSRF token.
   - Protected routes (`admin`, `auth.api`) strictly reject unauthenticated access with HTTP 401, requiring fresh login.

---

## 5. Primary API Endpoints

| Resource | Method | Path | Description |
| :--- | :--- | :--- | :--- |
| **Products** | `GET` | `/api/products` | Fetch catalog with real-time stock & photos |
| | `POST` | `/api/products` | Add product with image upload |
| | `PUT`/`POST` | `/api/products/{id}` | Update product & stock levels |
| | `DELETE` | `/api/products/{id}` | Delete product |
| **Categories** | `GET` | `/api/categories` | Fetch category list |
| | `POST` | `/api/categories` | Add new category |
| **Orders** | `GET` | `/api/orders` | Fetch orders list |
| | `POST` | `/api/orders` | Place order (validates & cuts stock in PostgreSQL) |
| | `PATCH` | `/api/orders/{id}/status` | Update fulfillment state |
| | `POST` | `/api/orders/{id}/cancel` | Cancel order (1-4h window) |
| **Cart** | `GET` | `/api/cart` | Retrieve user cart items |
| | `POST` | `/api/cart` | Sync item to cart in PostgreSQL |
| **Users** | `GET` | `/api/users` | List customers and admins with roles |
| | `POST` | `/api/auth/login` | Authenticate customer or admin |
| | `POST` | `/api/auth/register` | Register new customer account |





### 5. Admin And User Credentials

* **Admin**: `admin@gmail.com` | Password: `password123`
* **Customer**: `customer@gmail.com` | Password: `password123`

---

## 6. Resolved Key Issues & Behavioral Fixes

1. **Session Persistence, Logout Cleanup & Deletion Re-login Lifecycle**:
   - **1-Day Session Persistence**: Active sessions and API tokens are configured with a 1440-minute (24-hour / 1-day) lifetime in PostgreSQL (`sessions` table) and `.env`.
   - **Logout Session Deletion**: When an administrator or user logs out, `POST /api/auth/logout` deletes active sessions from the PostgreSQL `sessions` table, invalidates session cookies, nullifies `users.api_token`, and flushes the authentication guard.
   - **Strict Session Deletion Handling**: If a session row is deleted from the PostgreSQL `sessions` table or token is revoked/cleared, `AdminMiddleware` and `adminGuard` immediately reject route access with HTTP 401 and redirect to `/admin/login`. Logging in again generates a fresh 1-day session row in the database and restores access.
   - **Customer Cascade Deletion**: In `UserController::destroy`, cascade deletion cleans up related records (cart items, orders, order items, payments) and permanently deletes the user from PostgreSQL.

2. **Login Error Fix (`Attempt to read property "name" on string`)**:
   - Safely guarded role resolution across `AuthController.php`, `TokenService.php`, and `User.php`.
   - Handles `role` whether it is an Eloquent relation object, string, or null.

3. **Cart Persistence & Live Stock Deduction**:
   - Authentication required: Unauthenticated visitors can only browse and inspect details. Adding to cart (`POST /api/cart`) and ordering (`POST /api/orders`) strictly reject with HTTP 401 until the patron logs in.
   - In `CartService.ts`, unauthenticated guests start with 0 items; clicking "+ ADD TO BAG" opens the login modal.
   - When an order is placed, stock is atomically deducted in PostgreSQL (`stock_quantity`) and ordered items are cleared from `cart_items`.

4. **1-Day Session Persistence & Deletion Handling**:
   - `SESSION_DOMAIN=localhost` configured in `.env` so cookies are visible across all ports (`4200` and `8000`) in Chrome DevTools under `http://localhost:4200`.
   - If session cookie is deleted in DevTools or session row is deleted in PostgreSQL `sessions` table, `TokenService.php` rejects with HTTP 401, immediately kicking the user back to the login screen.
   - Logging in again restores a new 1-day session row in PostgreSQL and a fresh session cookie.

5. **DataSeeder Optimization**:
   - Seeds strictly 2 users (1 Admin: `admin@gmail.com`, 1 Customer: `customer@gmail.com`).
   - Resets PostgreSQL sequence (`ALTER SEQUENCE users_id_seq RESTART WITH 1`) so Admin is ID 1 and Customer is ID 2.
   - Zero fake orders and zero cart items seeded; all carts and orders start completely clean (0 records).

Noted : 
-- Admin : admin@gmail.com , Password : password123

-- Customer : customer@gmail.com , Password : password123