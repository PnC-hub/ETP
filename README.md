# RiduciSpese

**Tieni sotto controllo le tue spese quotidiane**

Un'applicazione web moderna per il tracciamento delle spese personali con categorizzazione automatica, budget mensili e analytics avanzati.

## Features

- **Tracking Spese**: Registra entrate e uscite in pochi secondi
- **Categorizzazione Smart**: Categorie automatiche per capire dove vanno i tuoi soldi
- **Budget Mensili**: Imposta limiti per categoria e ricevi avvisi
- **Report e Grafici**: Visualizza trend e pattern delle tue spese
- **Import/Export CSV**: Importa dal tuo estratto conto bancario
- **Privacy Garantita**: I tuoi dati restano tuoi, crittografia end-to-end

## Tech Stack

### Frontend
- **Vanilla JavaScript SPA**: Nessun framework pesante, performance ottimali
- **Modern CSS**: CSS Variables, Flexbox, Grid
- **Chart.js**: Grafici interattivi
- **Mobile-first design**: Responsive su tutti i dispositivi

### Backend
- **PHP 8.1+**: API RESTful moderna
- **MySQL 8.0+**: Database relazionale robusto
- **JWT Authentication**: Token-based auth sicuro
- **Stripe Integration**: Pagamenti e abbonamenti

## Pricing

- **Free**: 50 transazioni/mese
- **Pro**: €4.99/mese - Transazioni illimitate + features avanzate
- **Business**: €9.99/mese - Multi-utente + API access

## Installation

### Requirements
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Composer (for dependencies, if any)

### Setup

1. **Clone repository**
```bash
git clone https://github.com/yourusername/ETP.git
cd ETP
```

2. **Configure database**
```bash
# Create database
mysql -u root -p
CREATE DATABASE geniusmile_production;
USE geniusmile_production;
SOURCE migrations/001_initial_schema.sql;
```

3. **Configure API**
```bash
# Edit api/config.php with your settings
nano api/config.php
```

4. **Setup web server**

**Apache**: The `.htaccess` file is already configured.

**Nginx**: Copy `nginx.conf.example` to your Nginx sites configuration.

5. **Configure Stripe** (for payments)
- Get your Stripe API keys from https://dashboard.stripe.com/apikeys
- Create products and price IDs
- Update `api/config.php` with your keys

6. **Test the application**
```bash
# Start PHP built-in server for testing
php -S localhost:8000
```

Visit `http://localhost:8000` in your browser.

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user

### Transactions (Protected)
- `POST /api/transactions/create` - Create transaction
- `GET /api/transactions/read` - Get all transactions
- `PUT /api/transactions/update` - Update transaction
- `DELETE /api/transactions/delete` - Delete transaction
- `GET /api/transactions/export` - Export to CSV

### User (Protected)
- `GET /api/user/status` - Get user status and subscription info

### Payments (Protected)
- `POST /api/payments/create-checkout` - Create Stripe checkout session
- `POST /api/payments/portal` - Access Stripe customer portal

### Webhooks
- `POST /api/webhooks/stripe` - Handle Stripe webhooks

## Database Schema

### Tables
- `afts5498_etp_users` - User accounts
- `afts5498_etp_transactions` - Financial transactions
- `afts5498_etp_categories` - Transaction categories
- `afts5498_etp_budgets` - User budget limits

## Development

### Local Testing
```bash
# Use config.local.php for local development
cp api/config.php api/config.local.php
# Edit config.local.php with local settings
```

### API Testing
```bash
php test_api.php
```

## Deployment

### Production Checklist
1. Set `ENVIRONMENT` to `'production'` in `api/config.php`
2. Update Stripe keys to live keys
3. Configure SSL certificate (Let's Encrypt)
4. Enable HTTPS redirect in `.htaccess` or nginx config
5. Set proper file permissions (644 for files, 755 for directories)
6. Disable error display: `ini_set('display_errors', 0);`
7. Setup automatic backups for database
8. Configure Stripe webhooks endpoint

### Server Configuration

**Recommended**:
- PHP 8.1+
- MySQL 8.0+
- 1GB RAM minimum
- SSL certificate (Let's Encrypt)
- Backup system

## Security

- JWT-based authentication
- Password hashing with bcrypt
- SQL injection protection via prepared statements
- XSS protection headers
- CSRF token validation (for forms)
- Rate limiting on API endpoints
- Secure session handling

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## License

Proprietary - All rights reserved

## Support

For support, email: support@riducispese.it

## Roadmap

- [ ] AI-powered categorization
- [ ] Bank account integration (Open Banking API)
- [ ] Mobile app (React Native)
- [ ] Advanced analytics dashboard
- [ ] Multi-currency support
- [ ] Receipt scanning with OCR
- [ ] Recurring transaction automation
- [ ] Budget recommendations based on AI

---

**RiduciSpese** - Made with ❤️ in Italy
