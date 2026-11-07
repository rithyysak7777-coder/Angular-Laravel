# Fearless Atelier - Streetwear E-Commerce Platform

A modern, full-stack luxury streetwear e-commerce platform built with an **Angular 21** frontend, a **Laravel 13 REST API** backend, and a **PostgreSQL** database.

---

## 1. Seeded User Accounts & Roles

The database is seeded strictly with **2 official accounts** (1 Administrator and 1 Customer). There are zero fake orders seeded; all orders in the system are created through authentic customer purchases.

| Role | User Name | Email Address | Password | Telephone | Permissions & Access |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Admin** | `Admin` | `admin@gmail.com` | `password123` | `+855 12 000 001` | Full Master Console Access (`/admin`): Product CRUD, Photo Uploads, Category Management, Customer Directory, Order Status Management |
| **Customer** | `User` | `customer@gmail.com` | `password123` | `+855 98 765 432` | Storefront (`/`): Catalog Browsing, Add to Bag, Checkout, VIP Account Portal (`/account`), Order Tracking, 4h Order Cancellation, Profile & Avatar Upload |

> **Alternative Admin Fallback**: `admin@fearlesssoul.com` with password `password123` is also supported by the system.

---

## 2. System Architecture & Tech Stack

```
+---------------------------------------------------------------------------------------+
|                                    FEARLESS ATELIER                                   |
+---------------------------------------------------------------------------------------+
|                                                                                       |
|   FRONTEND (Storefront & Admin)       HTTP JSON REST API       BACKEND (Web Services) |
|   Angular 21 (Standalone)       <============================> Laravel 13 Framework  |
|   Tailwind CSS & Signals                                       PHP 8.2+               |
|   http://localhost:4200                                        http://localhost:8000  |
|                                                                          |            |
|                                                                          v            |
|                                                                 DATABASE ENGINE       |
|                                                                 PostgreSQL            |
|                                                                 Database: laravel_final|
+---------------------------------------------------------------------------------------+
```

### 2.1 Frontend Architecture (Angular 21)
* **Technologies**:
  * **Angular 21** (Standalone Components, modern control flow `@if` / `@for`).
  * **Angular Signals** (`signal`, `computed`, `effect`) for instantaneous reactive state synchronization.
  * **Tailwind CSS** for luxury editorial design, responsive grids, and animated drawers.
  * **TypeScript & RxJS** for strictly-typed API communication.
* **Core Modules**:
  * **Storefront**: Editorial hero showcase, live stock counters, interactive category filter tabs, quick-view product modal.
  * **Cart & Bag Engine**: Auto-opening sliding bag drawer with real-time stock limits (`X in stock`), quantity stepper, voucher discounts (`FEARLESS20`, `VIP10`).
  * **Secure Checkout**: Form validation, ABA Payway QR generation, Credit Card simulation, and Cash on Delivery.
  * **VIP Customer Portal**: Order history with 4-hour live cancellation countdown, profile photo upload, and phone updates.
  * **Admin Console**: Executive dashboard, product inventory management with image uploads, categories taxonomy, and customer directory with role badges (`ADMIN` / `CUSTOMER`).

### 2.2 Backend Architecture (Laravel 13)
* **Technologies**:
  * **Laravel 13 Framework** (PHP 8.2+).
  * **PostgreSQL Database** (`laravel_final`).
  * **Eloquent ORM** with eager loading relationships (`Category -> Products`, `Order -> OrderItems -> Product`, `User -> Role`).
  * **Bcrypt & Laravel Hash** for secure authentication.
* **Core Modules**:
  * **RESTful JSON API**: Endpoints for `/api/products`, `/api/categories`, `/api/orders`, `/api/cart`, `/api/users`, and `/api/auth`.
  * **Inventory Stock Protection**: Validates and immediately deducts stock in PostgreSQL when orders are placed.
  * **Fulfillment Rules**: Enforces order state flow (`pending` -> `processing` -> `delivered` / `cancelled`) and 4-hour cancellation rule.
  * **Media Storage**: Handles image file uploads and serves them via `/images/products/`.

---

## 3. How to Clone and Run (Complete Setup Guide)

### 3.1 Prerequisites
Ensure the following tools are installed on your machine:
* [Git](https://git-scm.com/)
* [PHP 8.2+](https://www.php.net/)
* [Composer](https://getcomposer.org/)
* [PostgreSQL](https://www.postgresql.org/) (running on default port `5432`)
* [Node.js](https://nodejs.org/) (v20+ or v22+) & npm

---

### 3.2 Step 1: Clone the Repository
Open your terminal (PowerShell, Command Prompt, or Bash) and clone the repository:

```bash
git clone <your-repository-url>
cd FinalProject
```

The repository structure contains two main folders:
* `Backend/ecommerce_website`: Laravel 13 Web API & PostgreSQL models
* `Frontend/ecommerce_website`: Angular 21 Standalone web application

---

### 3.3 Step 2: Backend Setup (Laravel 13 & PostgreSQL)

1. **Enter the backend directory**:
   ```bash
   cd Backend/ecommerce_website
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment File**:
   Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
   Open `.env` in your code editor and verify your PostgreSQL credentials:
   ```ini
   APP_NAME="Fearless Atelier"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://localhost:8000

   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=laravel_final
   DB_USERNAME=postgres
   DB_PASSWORD=your_postgres_password
   ```

4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

5. **Create the Database in PostgreSQL**:
   Open `psql` or your database client (e.g. pgAdmin, DBeaver) and create the database:
   ```sql
   CREATE DATABASE laravel_final;
   ```

6. **Run Migrations & Seeders**:
   This creates all required tables and seeds Categories, Products with starting stock, and the 2 default users (Admin & User):
   ```bash
   php artisan migrate:fresh --seed
   ```

7. **Create Public Storage Link**:
   ```bash
   php artisan storage:link
   ```

8. **Start the Laravel API Server**:
   ```bash
   php artisan serve
   ```
   * The backend API server is now running at: `http://localhost:8000`
   * API endpoints are accessible at: `http://localhost:8000/api`

---

### 3.4 Step 3: Frontend Setup (Angular 21)

Open a **new terminal tab or window**:

1. **Enter the frontend directory**:
   ```bash
   cd Frontend/ecommerce_website
   ```

2. **Install Node dependencies**:
   ```bash
   npm install
   ```

3. **Start the Angular Development Server**:
   ```bash
   npm start
   ```
   *(Alternative: `npx ng serve --port 4200`)*

4. **Access the Application**:
   * **Storefront**: [http://localhost:4200](http://localhost:4200)
   * **Admin Console**: [http://localhost:4200/admin](http://localhost:4200/admin)
   * **VIP Customer Portal**: [http://localhost:4200/account](http://localhost:4200/account)
   * **Sign In Page**: [http://localhost:4200/login](http://localhost:4200/login)

---

## 4. How to Use the Application

### 4.1 Customer / Shopper Flow
1. **Browse Catalog**:
   * Navigate to `http://localhost:4200`.
   * Filter garments by clicking category pills: **ALL PIECES**, **TEES**, **HOODIES**, **SHIRTS**, **PANTS**, **CAPS**.
   * Use the search bar in the header to search for specific garments by name or keyword.
2. **Add to Bag**:
   * Hover over any product card and click **+ ADD TO BAG** (or click the card to open the **Quick View Modal** and select your preferred size).
   * The **YOUR BAG** drawer automatically slides out on the right showing:
     - Garment thumbnail and name
     - Selected size (e.g. `SIZE: L`)
     - Unit price
     - Live stock availability badge (e.g. `15 in stock`)
     - Quantity controls (`-` / `+`) capped at available stock
     - Free shipping progress bar (Free shipping unlocked on orders over $100)
3. **Checkout & Order Placement**:
   * Click **SECURE CHECKOUT →** from the bag drawer (if not logged in, you will be prompted to sign in with `customer@gmail.com` / `password123` or register).
   * Fill out the shipping address.
   * Apply voucher codes:
     - `FEARLESS20` for 20% Atelier Discount
     - `VIP10` or `SETEC` for 15% VIP Student Discount
   * Select a payment method:
     - **ABA PAYWAY (KHQR)**: Generates an authentic interactive ABA KHQR code.
     - **CREDIT / DEBIT CARD**: Visual card preview with 256-bit encryption simulation.
     - **CASH ON DELIVERY (COD)**: Pay upon physical delivery.
   * Click **PLACE ORDER NOW**.
   * **Stock Deduction**: The purchased items are immediately deducted from both the PostgreSQL database (`products.stock_quantity`) and the frontend storefront catalog.
4. **Order Tracking & VIP Portal**:
   * After checkout, you are redirected to the **Order Confirmation** page with an order number (e.g. `#ORD-202611-1234`).
   * Click **VIEW MY ORDERS** or navigate to `http://localhost:4200/account` to view your order history.
   * **4-Hour Cancellation Window**: Each pending order displays a live countdown timer (`Can cancel: Active (4h window)`). Click **CANCEL ORDER** to cancel if within 4 hours.
5. **Profile Customization**:
   * In `/account`, click **EDIT PROFILE PAGE** to change your name, phone number, and upload a custom avatar photo directly from your computer.

---

### 4.2 Administrator Flow
1. **Login to Admin Console**:
   * Navigate to `http://localhost:4200/admin/login` (or click **Admin Console** in the footer).
   * Sign in with:
     - **Email**: `admin@gmail.com`
     - **Password**: `password123`
2. **Dashboard Overview** (`/admin/dashboard`):
   * View total revenue metrics, real-time inventory counts, and pending order statistics.
3. **Inventory & Products Management** (`/admin/products`):
   * **Add Product**: Click **+ Add Product**, enter title, price, compare-at price, stock quantity, select category, and upload photos.
   * **Edit Stock & Price**: Click the Edit icon on any product row to adjust available stock or pricing.
   * **Delete Product**: Click the Trash icon to remove discontinued items.
4. **Categories Taxonomy** (`/admin/categories`):
   * Create new streetwear categories (e.g. *Outerwear*, *Denim*, *Knitwear*).
   * Edit or delete existing categories.
5. **Orders Fulfillment** (`/admin/orders`):
   * View all orders placed by customers.
   * Update fulfillment status between `PENDING`, `PROCESSING`, `DELIVERED`, and `CANCELLED`.
6. **Customers Directory** (`/admin/customers`):
   * View all registered accounts with clear role badges (`ADMIN` in dark badge, `CUSTOMER` in light badge).
   * Search and filter customers by name, email, or telephone.
   * Add new patron accounts or delete user credentials with immediate database removal.

---

## 5. Ready-to-Test Scenarios (Customer & Admin Testing Guide)

Use this section as a checklist to test every feature of the application with pre-seeded data and realistic scenarios.

### 5.1 Test Accounts Summary
| Role | Email Address | Password | Intended Test Route |
| :--- | :--- | :--- | :--- |
| **Customer (User)** | `customer@gmail.com` | `password123` | Storefront & Checkout (`http://localhost:4200`), Account Portal (`/account`) |
| **Administrator** | `admin@gmail.com` | `password123` | Master Console (`http://localhost:4200/admin`), Admin Login (`/admin/login`) |

---

### 5.2 Customer & User Test Scenarios

#### Test Case C-1: Add to Bag & Live In-Stock Indicator
1. Go to `http://localhost:4200`.
2. Scroll to the **ORIGINALS SHOWCASE** section.
3. Hover over **UNISEX REGULAR FIT OXFORD SHIRT** (or any piece) and click **+ ADD TO BAG**.
4. **Expected Result**:
   - The **YOUR BAG** drawer slides out automatically from the right side.
   - The item appears with thumbnail, title, price (`$23.00 USD`), and `SIZE: L`.
   - A live stock badge shows `15 in stock`.
   - Click the `+` button until quantity hits 15: The `+` button disables, and a warning badge appears: `15 in stock (Max)` along with an in-app banner: *"Only 15 in stock. Maximum reached."*

#### Test Case C-2: Checkout, Voucher Discounts & ABA KHQR Payment
1. From the open bag drawer, click **SECURE CHECKOUT →** (or navigate to `http://localhost:4200/checkout`).
2. If prompted, log in with `customer@gmail.com` / `password123`.
3. In the checkout form:
   - Verify shipping address fields are filled (Name, Street, City, Country, Phone).
4. **Voucher Testing**:
   - Enter `FEARLESS20` and click apply -> You get a **20% Atelier Discount** deducted from subtotal.
   - Or enter `VIP10` / `SETEC` -> You get a **15% VIP Student Discount**.
5. **Payment Method Testing**:
   - Select **ABA PAYWAY (KHQR)**: An authentic ABA QR code renders on screen with transaction reference.
   - Select **CREDIT / DEBIT CARD**: Visual luxury card simulator displays cardholder name and masked numbers.
   - Select **CASH ON DELIVERY**: Free worldwide shipping applies if subtotal > $100.
6. Click **PLACE ORDER NOW**.
7. **Expected Result**:
   - Order is created and you are redirected to `/order-success/ORD-XXXX-XXXX`.
   - Bag is cleared.

#### Test Case C-3: Verification of Real-Time Stock Cut
1. Right after placing an order in Test Case C-2, return to the storefront at `http://localhost:4200`.
2. Inspect the product you just purchased.
3. **Expected Result**:
   - The stock has decreased by exactly the quantity you bought (e.g. from 15 to 14 units).
   - In PostgreSQL, running `SELECT name, stock_quantity FROM products WHERE name ILIKE '%OXFORD%';` confirms the exact updated stock!

#### Test Case C-4: VIP Account Portal & 4-Hour Order Cancellation
1. Navigate to `http://localhost:4200/account` (or click your user initial in the navbar).
2. Look at the **MY ORDERS** tab.
3. **Expected Result**:
   - Your newly placed order appears with status `PENDING`, items list, total, and tracking number.
   - A gold countdown timer displays: `Can cancel: Active (4h window)`.
4. Click **CANCEL ORDER**:
   - The status updates immediately to `CANCELLED`.
   - The order cancellation is synchronized with the backend database.

#### Test Case C-5: Dedicated Profile Editor & Custom Avatar Upload
1. In `http://localhost:4200/account`, click **EDIT PROFILE PAGE** (top right button).
2. Click **CHANGE AVATAR** and pick any `.png` or `.jpg` photo from your computer.
3. **Expected Result**:
   - The avatar updates immediately with instant local preview.
   - Update your delivery phone number (e.g. `+855 12 999 888`) and click **SAVE CHANGES**.
   - Profile changes persist and sync to the database.

---

### 5.3 Administrator Test Scenarios

#### Test Case A-1: Route Protection & Admin Login
1. Open an incognito browser tab or log out of the customer account.
2. Try visiting `http://localhost:4200/admin`.
3. **Expected Result**:
   - The `adminGuard` blocks unauthorized access and redirects you to `/admin/login`.
4. Log in using Admin credentials:
   - **Email**: `admin@gmail.com`
   - **Password**: `password123`
5. **Expected Result**:
   - Master Console unlocks and opens the **Executive Dashboard** at `http://localhost:4200/admin/dashboard`.

#### Test Case A-2: Inventory Control & Multi-Photo Product Creation
1. In the Admin sidebar, click **Products** (`/admin/products`).
2. **Edit Existing Stock**:
   - Locate any product row and click the **Edit** icon.
   - Change stock from `15` to `25` units and save.
   - Open the storefront at `http://localhost:4200`: The product immediately shows `25 in stock`!
3. **Add New Streetwear Piece**:
   - Click **+ Add Product**.
   - Title: `Cyber Midnight Oversized Tee`
   - Price: `35.00` | Compare At Price: `50.00`
   - Category: `Tees` | Stock: `10`
   - Upload a photo from your computer.
   - Click **Register Product**.
   - **Expected Result**: Product is saved to PostgreSQL, photo is stored, and product is visible on the storefront.

#### Test Case A-3: Order Fulfillment Workflow
1. In the Admin sidebar, click **Orders** (`/admin/orders`).
2. Locate the customer's pending order from Test Case C-2.
3. Click the fulfillment status dropdown and switch it from `PENDING` to `PROCESSING`, then `DELIVERED`.
4. Check the customer's account portal at `/account`: The order status badge reflects the updated status.

#### Test Case A-4: Customer Directory & Role Identification
1. In the Admin sidebar, click **Customers** (`/admin/customers`).
2. **Expected Result**:
   - All registered accounts are listed.
   - Administrator accounts display a dark **ADMIN** badge.
   - Customer accounts display a light **CUSTOMER** badge.
   - Test deleting an account: Click the Delete icon -> Confirm modal appears -> The patron is removed from the database immediately.

---

## 6. Complete API Reference

All backend API routes are registered in `routes/api.php`:

### 5.1 Products API
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/products` | Fetch catalog with stock quantity, category, and images |
| `GET` | `/api/products/{id}` | Fetch single product details |
| `POST` | `/api/products` | Create product (multipart with image upload) |
| `PUT`/`POST` | `/api/products/{id}` | Update product details and stock |
| `DELETE` | `/api/products/{id}` | Remove product from inventory |
| `POST` | `/api/products/upload-image` | Upload image file returning public URL |

### 5.2 Categories API
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/categories` | List all active categories |
| `POST` | `/api/categories` | Create a new category |
| `PUT` | `/api/categories/{id}` | Update category name and slug |
| `DELETE` | `/api/categories/{id}` | Delete category |

### 5.3 Orders API
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/orders` | Fetch orders list (supports `?user_id=`) |
| `GET` | `/api/orders/{id}` | Fetch single order details |
| `POST` | `/api/orders` | Submit order & cut stock out in PostgreSQL |
| `PATCH` | `/api/orders/{id}/status` | Update fulfillment state (`pending`, `processing`, etc.) |
| `POST` | `/api/orders/{id}/cancel` | Cancel order within 4-hour window |

### 5.4 Cart Synchronization API
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/cart` | Retrieve user's cart items from PostgreSQL |
| `POST` | `/api/cart` | Add item to cart (accepts `product_id`, `qty`/`quantity`, `selected_size`) |
| `PUT` | `/api/cart/{id}` | Update cart item quantity |
| `DELETE` | `/api/cart/{id}` | Remove item from cart |
| `DELETE` | `/api/cart/clear` | Clear all items from cart |

### 5.5 Users & Authentication API
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/api/auth/login` | Authenticate customer or admin with hashed password |
| `POST` | `/api/auth/register` | Register new customer with fullName, email, and password |
| `GET` | `/api/users` | List all registered patrons and admins |
| `POST` | `/api/users` | Create customer or admin account |
| `PUT` | `/api/users/{id}` | Update profile, phone, or role |
| `DELETE` | `/api/users/{id}` | Delete user record from PostgreSQL |

---

## 6. Troubleshooting & Tips

* **Stock Issues**: Stock is decremented in real-time in both PostgreSQL (`products.stock_quantity`) and frontend signals. If stock reaches `0`, the card displays `OUT OF STOCK` and disables adding to cart.
* **Database Reset**: To start fresh with clean tables and default seeds:
  ```bash
  cd Backend/ecommerce_website
  php artisan migrate:fresh --seed
  ```
* **Port Conflicts**:
  - If port `8000` is busy, specify another port: `php artisan serve --port 8080` (and update `environment.ts` in frontend).
  - If port `4200` is busy, run: `npx ng serve --port 4300`.
