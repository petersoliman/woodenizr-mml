# Woodenizr - Premium Wood & Woodworking Supplies E-commerce Platform

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/Symfony-5.4-green.svg)](https://symfony.com)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)

## 🏗️ Project Overview

**Woodenizr** is a comprehensive e-commerce platform built with **Symfony 5.4** and **PHP 8.1+** that specializes in premium wood and woodworking supplies. The platform features a multi-vendor marketplace with advanced product management, multi-language support (English/Arabic), and integrated payment processing.

## ✨ Key Features

### 🛒 E-commerce Capabilities
- **Multi-vendor Marketplace**: Support for multiple vendors and store management
- **Advanced Product Management**: Products with variants, attributes, and 360° views
- **Shopping Cart System**: Guest and registered user cart management
- **Order Management**: Complete order lifecycle with status tracking
- **Payment Integration**: Multiple payment gateways (Facebook Conversion API)
- **Coupon System**: Discount and promotional code management
- **Wishlist Functionality**: User favorite products management

### 🌐 Multi-language Support
- **Bilingual Interface**: English and Arabic with RTL/LTR support
- **Localized Content**: Product descriptions, categories, and static content
- **SEO Optimization**: Multi-language SEO with proper meta tags
- **Currency Support**: Multi-currency transactions

### 🎨 User Experience
- **Responsive Design**: Mobile-first approach with modern UI
- **360° Product Views**: Interactive product visualization
- **Advanced Search**: Full-text search with filters and sorting
- **Social Integration**: Facebook, Google OAuth, social sharing
- **Real-time Updates**: Live cart updates and notifications

### 🔧 Technical Features
- **Modular Architecture**: Bundle-based Symfony structure
- **Database Migrations**: Automated schema management
- **Asset Optimization**: Webpack Encore with critical CSS
- **Caching System**: Performance optimization
- **API Ready**: RESTful API endpoints
- **Security**: CSRF protection, input validation, secure authentication

## 🚀 Technology Stack

### Backend
- **PHP 8.1+** - Core programming language
- **Symfony 5.4** - Full-stack framework
- **Doctrine ORM** - Database abstraction layer
- **MariaDB/MySQL** - Database system
- **Composer** - Dependency management

### Frontend
- **Twig** - Template engine
- **SCSS/CSS** - Styling with responsive design
- **JavaScript** - Modern JS with Stimulus controllers
- **Webpack Encore** - Asset compilation and optimization

### External Services
- **Facebook Conversion API** - Marketing and analytics
- **OAuth2** - Social authentication (Facebook, Google)
- **Mailgun/SendGrid** - Email services
- **Google reCAPTCHA** - Security verification

## 📁 Project Structure

```
woodenizr/
├── src/                    # Symfony bundles
│   ├── ECommerceBundle/    # Core e-commerce functionality
│   ├── ProductBundle/      # Product management
│   ├── UserBundle/         # User management
│   ├── VendorBundle/       # Multi-vendor support
│   ├── CMSBundle/          # Content management
│   ├── SeoBundle/          # SEO optimization
│   ├── CurrencyBundle/     # Multi-currency support
│   ├── ShippingBundle/     # Shipping management
│   ├── OnlinePaymentBundle/ # Payment processing
│   ├── MediaBundle/        # Media management
│   ├── ThreeSixtyViewBundle/ # 360° product views
│   └── [Other bundles]     # Additional features
├── templates/              # Twig templates
├── public/                 # Web assets
├── config/                 # Symfony configuration
├── migrations/             # Database migrations
├── translations/           # Multi-language files
└── vendor/                 # Composer dependencies
```

## 🛠️ Installation & Setup

### Prerequisites
- PHP 8.1 or higher
- Composer
- MariaDB/MySQL 10.5+
- Node.js & npm (for asset compilation)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/PerfectNeeds/woodenizr.git
   cd woodenizr
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   cd html
   npm install
   ```

4. **Configure environment**
   ```bash
   cp .env .env.local
   # Edit .env.local with your database and service credentials
   ```

5. **Set up database**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

6. **Compile assets**
   ```bash
   cd html
   npm run build
   ```

7. **Set up web server**
   ```bash
   # For development
   php bin/console server:start
   
   # For production, configure your web server to point to public/
   ```

## 🔧 Configuration

### Environment Variables
```env
# Database
DATABASE_URL="mysql://user:password@localhost/woodenizr"

# External Services
FACEBOOK_APP_ID=your_facebook_app_id
FACEBOOK_APP_SECRET=your_facebook_app_secret
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# Email Services
MAILER_DSN=mailgun://key:domain@default
SENDGRID_KEY=your_sendgrid_key

# Security
RECAPTCHA_SITE_KEY=your_recaptcha_site_key
RECAPTCHA_SECRET_KEY=your_recaptcha_secret_key
```

### Site Settings
The platform includes a dynamic site settings system accessible through the admin panel:
- Website title and description
- Primary colors and branding
- Contact information
- Social media links
- SEO settings

## 📊 Available Commands

### Development Commands
```bash
# Generate sitemap
php bin/console app:generate-sitemap

# Generate Facebook catalog
php bin/console app:generate-facebook-catalog-commerce-csv

# Clear cache
php bin/console cache:clear

# Update database schema
php bin/console doctrine:schema:update --force
```

### Asset Management
```bash
# Watch for changes (development)
npm run watch

# Build for production
npm run build

# Generate critical CSS
npm run critical
```

## 🌐 Multi-language Support

The platform supports English and Arabic with:
- **RTL/LTR Layout**: Automatic direction switching
- **Localized URLs**: `/en/` and `/ar/` prefixes
- **Translated Content**: All user-facing text
- **SEO Optimization**: Language-specific meta tags

## 🔒 Security Features

- **CSRF Protection**: Built-in Symfony security
- **Input Validation**: Comprehensive form validation
- **SQL Injection Prevention**: Doctrine ORM protection
- **XSS Protection**: Twig auto-escaping
- **Secure Authentication**: OAuth2 integration
- **Rate Limiting**: API request throttling

## 📈 Performance Optimization

- **Asset Optimization**: Minified CSS/JS
- **Image Optimization**: WebP support and lazy loading
- **Caching**: Multiple cache layers
- **Database Optimization**: Indexed queries
- **CDN Ready**: Static asset delivery

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is proprietary software. All rights reserved.

## 🆘 Support

For support and questions:
- **Email**: info@ecommerce.com
- **Address**: Alexandria, Egypt
- **Website**: [https://woodenizr.com](https://woodenizr.com)

## 🙏 Acknowledgments

- **PerfectNeeds** - Development team
- **Symfony Community** - Framework and ecosystem
- **Open Source Contributors** - Various libraries and tools

---

**Built with ❤️ by PerfectNeeds**
