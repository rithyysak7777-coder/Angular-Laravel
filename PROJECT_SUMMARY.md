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

## 4. Primary API Endpoints

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





### 5 . Admin And User 

-- Admin : admin@gmail.com , Password : password123

-- Customer : customer@gmail.com , Password : password123