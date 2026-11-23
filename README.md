# Hanerhecious Diner — Restaurant Ordering System

A web-based restaurant ordering system built with PHP, MySQL, HTML, CSS, and JavaScript.

## Features

- Customer registration and login
- Email verification
- Browse the restaurant menu
- Add items to the shopping cart
- Apply discount coupons
- Checkout with Cash on Delivery
- View order history and order details
- Track order status
- Submit support requests and view replies
- Admin dashboard for managing orders
- Menu management
- Coupon management
- Customer account listing
- Customer support management
- Privacy policy and help center

## Technologies Used

- PHP
- MySQL
- HTML5
- CSS3
- JavaScript
- XAMPP
- Visual Studio Code

## Installation

1. Install XAMPP and start Apache and MySQL.
2. Copy the project folder into the XAMPP `htdocs` directory.
3. Open phpMyAdmin at `http://localhost/phpmyadmin/`.
4. Create or select the database `restaurant_order_system`.
5. Import the provided `database.sql` file.
6. Check the database settings in `config.php`.
7. Open the website at `http://localhost/RestaurantOrderSystem1/`.

## Default Database Configuration

The local development configuration may use:

- Host: `127.0.0.1`
- Database: `restaurant_order_system`
- Username: `root`
- Password: empty (default XAMPP setup)

Update these settings if your MySQL configuration is different.

## Notes

- Email verification may require additional email configuration.
- The system uses Cash on Delivery for orders.
- Configure HTTPS and secure database credentials before deploying publicly.
- Do not publish passwords, private credentials, or real customer information.
- Email verification requires a separately configured `mail_config.php` file.
- Keep email credentials outside the public repository.

## Project Purpose

This project was developed as a restaurant ordering system to demonstrate customer ordering, order tracking, coupon handling, and administrator management using PHP and MySQL.