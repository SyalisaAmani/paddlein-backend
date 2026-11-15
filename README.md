
# Paddlein Backend

Backend API for **Paddlein**, a padel court booking application built with Laravel 13.

Paddlein provides court management, user authentication, booking reservations, availability checking, and manual payment management.

## Tech Stack

- PHP 8.4
- Laravel 13
- MySQL
- Laravel Sanctum
- RESTful API

## Features

### Authentication
- Customer registration and login
- Token-based authentication using Laravel Sanctum
- Role-based access control (Admin and Customer)

### Court Management
- View available courts
- View court details
- Admin can create, update, and deactivate courts

### Booking Management
- Create court reservations
- Check court availability by date
- Prevent overlapping reservations
- View booking details
- Cancel bookings according to cancellation policy

### Payment Management
- Manual payment status management by admin
- Payment status tracking
- Paid cancellation requires manual refund handling

## Booking Rules

- Each booking has a duration of 60 minutes.
- Operating hours: 09:00–21:00 WIB.
- Booking slots start from 09:00 until 20:00.
- Customers can only access their own bookings.
- Customers can cancel more than 2 hours before the booking time.
- Admins can manage and cancel all bookings.
- Inactive courts cannot be booked.

## Installation

Clone the repository:

```bash
git clone https://github.com/SyalisaAmani/paddlein-backend.git
cd paddlein-backend
```

Install dependencies:

```bash
composer install
```

Create environment configuration:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure your MySQL database in `.env`, then run:

```bash
php artisan migrate --seed
```

Start the development server:

```bash
php artisan serve
```

API base URL:

```text
http://127.0.0.1:8000/api
```

## Testing

Run the test suite:

```bash
php artisan test
```

## Project Status

Backend core development completed.

- Laravel API
- Authentication and authorization
- Court management
- Booking and cancellation
- Manual payment management
- Feature tests

## Author

**Syalisa Amani Fatiha**

GitHub: [@SyalisaAmani](https://github.com/SyalisaAmani)
