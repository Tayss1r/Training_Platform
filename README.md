# Training Platform

A comprehensive training and course management platform built with Symfony 7.2. This application provides a complete solution for managing courses, sessions, users, and categories with an administrative interface.

## Features

- **Course Management**: Create, edit, and manage training courses
- **Session Management**: Schedule and organize training sessions
- **User Management**: Handle user registration, authentication, and role-based access
- **Category System**: Organize courses by categories
- **Admin Dashboard**: Administrative interface for platform management
- **Email Verification**: Secure user registration with email confirmation
- **Reports & Settings**: Platform analytics and configuration options

## Requirements

- PHP >= 8.2
- Composer
- MySQL database
- Node.js (for asset management)

## Installation

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd training_platform
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Configure environment**:
   Copy [`.env`](.env) to `.env.local` and configure your database settings:
   ```bash
   cp .env .env.local
   ```
   Edit `.env.local` with your database credentials.

4. **Set up the database**:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Install assets**:
   ```bash
   php bin/console assets:install
   php bin/console importmap:install
   ```

6. **Generate sample data** (optional):
   ```bash
   php bin/console app:generate-sessions --count=5
   ```

## Development

### Starting the development server

```bash
symfony server:start
```

Or using PHP's built-in server:
```bash
php -S localhost:8000 -t public/
```

### Running tests

Using the provided [PHPUnit configuration](phpunit.xml.dist):

```bash
# Run all tests
php bin/phpunit

# Run specific test suite
php bin/phpunit tests/
```

### Code generation

This project uses Symfony Maker Bundle for rapid development:

```bash
# Generate a new controller
php bin/console make:controller

# Generate a new entity
php bin/console make:entity

# Generate CRUD operations
php bin/console make:crud

# Generate form classes
php bin/console make:form

# Generate user management
php bin/console make:user
```

### Database operations

```bash
# Create a new migration
php bin/console make:migration

# Execute migrations
php bin/console doctrine:migrations:migrate

# Load fixtures (if available)
php bin/console doctrine:fixtures:load
```

## Project Structure

```
├── src/
│   ├── Command/           # Console commands
│   ├── Controller/        # Controllers (including AdminController)
│   ├── Entity/           # Doctrine entities (Course, Session, User, Category)
│   ├── Form/             # Form types (UserType, CourseType, etc.)
│   ├── Security/         # Security components (EmailVerifier)
│   └── Service/          # Business logic services
├── templates/            # Twig templates
├── tests/               # PHPUnit tests
├── migrations/          # Database migrations
├── config/              # Configuration files
├── public/              # Web-accessible files
└── assets/              # Frontend assets
```

## Key Components

### Entities
- **User**: User management with role-based access
- **Course**: Training course information
- **Session**: Training session scheduling
- **Category**: Course categorization

### Controllers
- [`AdminController`](src/Controller/AdminController.php): Administrative interface
- Form controllers for user registration and management

### Services
- [`EmailVerifier`](src/Security/EmailVerifier.php): Email verification functionality
- Settings and reporting services

### Commands
- [`GenerateSessionsCommand`](src/Command/GenerateSessionsCommand.php): Generate sample session data

## Configuration

### Environment Files
- [`.env`](.env): Default configuration
- [`.env.dev`](.env.dev): Development environment
- [`.env.test`](.env.test): Testing environment

## Security

The application implements:
- Role-based access control (ROLE_ADMIN, etc.)
- Email verification for user registration
- CSRF protection
- Password hashing

## Testing

Run the test suite with:
```bash
php bin/phpunit
```

Tests are configured in [`phpunit.xml.dist`](phpunit.xml.dist) and located in the `tests/` directory.

## Contributing

1. Create a feature branch
2. Make your changes
3. Run tests: `php bin/phpunit`
4. Submit a pull request


### Core Team
- **Project Lead**: [tayss1r] - ferhitayssir@rades.r-iset.tn
- **Web developer**: [malekhlifa] - khalifamalek@rades.r-iset.tn
- **Web developer**: [AhmedMelliti1] - mellitiahmed@rades.r-iset.tn
- **Web developer**: [Ahmed-mekki-2] - mekkiahmed@rades.r-iset.tn

## License

This project is proprietary software. See the license field in [`composer.json`](composer.json) for details.