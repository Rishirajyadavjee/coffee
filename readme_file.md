# BrewMaster Coffee Website

A complete coffee shop website built with PHP, featuring user authentication, product management, shopping cart, and admin panel.

## Features

- **User Authentication**: Login, register, logout functionality
- **Product Catalog**: Browse 15+ pre-loaded coffee products with categories
- **Shopping Cart**: Add items, update quantities, remove items
- **Checkout System**: Complete payment processing simulation
- **Admin Panel**: Add/delete products, view statistics
- **Gallery**: Interactive coffee gallery with modals
- **Table Booking**: Contact form for table reservations
- **Responsive Design**: Mobile-friendly modern UI/UX

## Installation

### Prerequisites
- XAMPP/WAMP/MAMP or similar PHP development environment
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Steps

1. **Setup Database**
   - Start your MySQL server
   - Create a new database named `coffee_shop`
   - Import the SQL schema from the database file provided

2. **Configure Database Connection**
   - Open `config.php`
   - Update database credentials:
     ```php
     $host = 'localhost';
     $dbname = 'coffee_shop';
     $username = 'your_username';  // Usually 'root' for local
     $password = 'your_password';  // Usually empty for local
     ```

3. **File Structure**
   Place all files in your web server's document root (htdocs for XAMPP):
   ```
   /your-project-folder/
   ├── config.php
   ├── index.php
   ├── login.php
   ├── register.php
   ├── dashboard.php
   ├── products.php
   ├── gallery.php
   ├── cart.php
   ├── checkout.php
   ├── admin.php
   ├── add_to_cart.php
   ├── book_table.php
   ├── logout.php
   └── README.md
   ```

4. **Run the Application**
   - Start your web server
   - Navigate to `http://localhost/your-project-folder`

## Default Admin Account

- **Username**: admin
- **Email**: admin@coffee.com  
- **Password**: admin123

## Key Features Explained

### User Management
- Secure password hashing with PHP's `password_hash()`
- Session-based authentication
- Role-based access (Admin/User)

### Product Management
- 15 pre-loaded products across 5 categories
- Stock management system
- Admin can add/delete products
- Image placeholders with Font Awesome icons

### Shopping Experience
- Add to cart functionality
- Quantity management
- Stock validation
- Tax and shipping calculation
- Simulated payment processing

### Admin Features
- Dashboard with statistics
- Product management (CRUD operations)
- Order tracking
- User management overview

### UI/UX Features
- Modern gradient design
- Responsive layout
- Interactive elements
- Smooth animations
- Modal dialogs
- Form validation

## Database Schema

### Tables
- `users` - User accounts and admin roles
- `products` - Coffee products with categories
- `cart` - Shopping cart items
- `orders` - Completed orders
- `order_items` - Individual order products
- `bookings` - Table reservation requests

## Security Features

- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`
- CSRF protection through session validation
- Secure password hashing
- Input sanitization

## Customization

### Adding New Products
1. Login as admin
2. Go to Admin Panel
3. Use "Add New Product" form
4. Fill in product details and submit

### Styling
- Main styles are embedded in each PHP file
- Uses Font Awesome for icons
- Gradient color scheme based on coffee colors
- Responsive grid layouts

### Configuration
- Modify `config.php` for database settings
- Update tax rates and shipping costs in `checkout.php`
- Customize email templates in booking system

## Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config.php`
   - Ensure MySQL server is running
   - Verify database exists

2. **Session Issues**
   - Check if `session_start()` is called
   - Verify PHP session configuration
   - Clear browser cookies

3. **Permission Errors**
   - Ensure web server has read/write permissions
   - Check file ownership and permissions

## Future Enhancements

- Email notifications for orders
- Real payment gateway integration
- Product image uploads
- Customer reviews and ratings
- Inventory alerts
- Order status tracking
- Multi-language support

## License

This project is open source and available under the MIT License.

## Support

For issues and questions, please check the code comments or contact support.