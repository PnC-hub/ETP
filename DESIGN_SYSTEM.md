# RiduciSpese - Design System

## Color Palette

### Primary Colors
```css
--primary: #0d9488        /* Teal - Main brand color */
--primary-dark: #0f766e   /* Darker teal - Hover states */
--primary-light: #5eead4  /* Light teal - Accents */
```

### Secondary Colors
```css
--secondary: #475569      /* Slate - Secondary text */
--background: #f8fafc     /* Very light blue-gray - Page background */
--surface: #ffffff        /* White - Card surfaces */
```

### Semantic Colors
```css
--text: #1e293b          /* Dark slate - Primary text */
--text-light: #64748b    /* Medium slate - Secondary text */
--danger: #dc2626        /* Red - Expenses, errors */
--success: #16a34a       /* Green - Income, success */
--warning: #f59e0b       /* Amber - Warnings, budget alerts */
--border: #e2e8f0        /* Light gray - Borders */
```

### Usage Examples
- **Buttons**: Primary uses `--primary`, hover uses `--primary-dark`
- **Text**: Headings use `--text`, descriptions use `--text-light`
- **Income**: Green `--success` with `+€` prefix
- **Expenses**: Red `--danger` with `-€` prefix
- **Cards**: White `--surface` with `--border` and `--shadow`

---

## Typography

### Font Stack
```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
```
System fonts for optimal performance and native feel.

### Font Sizes
- **Hero Title**: 48px (32px mobile) - Landing page headline
- **Page Title**: 32px - Dashboard, Settings
- **Section Title**: 24px - Card titles, modal headers
- **Body Large**: 18px - Hero subtitle, CTA text
- **Body**: 15px - Default text, forms, buttons
- **Body Small**: 14px - Labels, meta info
- **Tiny**: 13px - Transaction metadata

### Font Weights
- **800**: Extra bold - Hero, large numbers
- **700**: Bold - Headings, card titles
- **600**: Semibold - Labels, buttons
- **500**: Medium - Nav links
- **400**: Regular - Body text (default)

### Line Heights
- Headings: `1.2`
- Body text: `1.6`
- Compact: `1.4` (for cards, tight layouts)

---

## Spacing Scale

```css
--spacing-xs: 4px
--spacing-sm: 8px
--spacing-md: 12px
--spacing-lg: 16px
--spacing-xl: 24px
--spacing-2xl: 32px
--spacing-3xl: 40px
--spacing-4xl: 60px
```

### Usage
- **Card padding**: 24px (`--spacing-xl`)
- **Section margins**: 32px (`--spacing-2xl`)
- **Button padding**: 12px 24px (vertical/horizontal)
- **Form gaps**: 16px (`--spacing-lg`)
- **Grid gaps**: 20-24px

---

## Border Radius

```css
--radius: 12px        /* Default - Cards, buttons */
--radius-lg: 16px     /* Large - Hero section, modals */
--radius-sm: 8px      /* Small - Inputs, badges */
--radius-full: 50%    /* Circle - Icons, avatars */
```

---

## Shadows

```css
--shadow: rgba(15, 118, 110, 0.08)       /* Light - Default card shadow */
--shadow-hover: rgba(15, 118, 110, 0.16) /* Medium - Hover states */
```

### Shadow Levels
1. **Card Default**: `0 1px 3px var(--shadow)`
2. **Card Hover**: `0 4px 12px var(--shadow-hover)`
3. **Button Hover**: `0 4px 12px var(--shadow-hover)` + `translateY(-1px)`

---

## Components

### 1. Buttons

#### Primary Button
```html
<button class="btn btn-primary">Aggiungi</button>
```
- Background: `--primary` (#0d9488)
- Text: White
- Hover: `--primary-dark` with lift effect
- Padding: 12px 24px
- Border radius: 12px

#### Secondary Button
```html
<button class="btn btn-secondary">Annulla</button>
```
- Background: `--background`
- Border: 1px solid `--border`
- Hover: Border becomes `--primary`

#### Danger Button
```html
<button class="btn btn-danger">Elimina Account</button>
```
- Background: `--danger` (#dc2626)
- Text: White

#### Button Sizes
- Default: 12px 24px, 15px font
- Small: 8px 16px, 14px font
- Block: `width: 100%`

### 2. Cards

```html
<div class="card">
    <div class="card-title">💰 Titolo Card</div>
    <!-- Content -->
</div>
```
- Background: White
- Border radius: 16px
- Padding: 24px
- Shadow: Subtle, increases on hover
- Hover: Lift effect with deeper shadow

### 3. Forms

#### Input Field
```html
<div class="form-group">
    <label>Email</label>
    <input type="email" placeholder="tua@email.com">
</div>
```
- Border: 1px solid `--border`
- Padding: 12px
- Border radius: 12px
- Focus: Border becomes `--primary` with glow

#### Select Dropdown
```html
<select class="filter-select">
    <option>Tutte</option>
</select>
```
Same styling as input

#### Checkbox
```html
<div class="checkbox-group">
    <input type="checkbox" id="terms">
    <label for="terms">Accetto i termini</label>
</div>
```
- Size: 20px × 20px
- Accent color: `--primary`

### 4. Transaction Card

```html
<div class="transaction-item">
    <div class="transaction-left">
        <div class="transaction-icon cat-food">🍔</div>
        <div class="transaction-info">
            <h4>Spesa al supermercato</h4>
            <div class="transaction-meta">30/01/2025 • Cibo</div>
        </div>
    </div>
    <div class="transaction-amount expense">-€45.50</div>
    <div class="transaction-actions">
        <button class="icon-btn">🗑️</button>
    </div>
</div>
```
- Flexbox layout
- Icon with category-based background color
- Amount colored by type (green/red)
- Hover: Border becomes `--primary`

### 5. Stat Card

```html
<div class="card stat-card">
    <div class="stat-icon income">💰</div>
    <div class="stat-content">
        <h3>€2,450.00</h3>
        <p>Entrate totali</p>
    </div>
</div>
```
- Large number display (32px, weight 800)
- Icon with colored background
- Compact layout

### 6. Pricing Card

```html
<div class="pricing-card card featured">
    <span class="pricing-badge">Più Popolare</span>
    <div class="pricing-header">
        <h3>Pro</h3>
        <div class="pricing-price">4.99€<span>/mese</span></div>
    </div>
    <ul class="pricing-features">
        <li>Transazioni illimitate</li>
        <li>Categorie personalizzate</li>
    </ul>
    <button class="btn btn-primary btn-block">Inizia Pro</button>
</div>
```
- Featured card has `--primary` border and slight scale up
- Large price display (48px)
- Checkmark bullets (✓) in green

### 7. Navigation Bar

```html
<nav class="navbar">
    <div class="navbar-content">
        <a href="#" class="navbar-brand">💰 RiduciSpese</a>
        <div class="navbar-menu">
            <a href="#" class="nav-link">Home</a>
            <button class="btn btn-primary btn-sm">Accedi</button>
        </div>
    </div>
</nav>
```
- Sticky positioning
- White background with subtle shadow
- 64px height
- Brand logo in `--primary`

### 8. Alerts

```html
<div class="alert alert-warning">
    <strong>⚠️ Limite raggiunto!</strong><br>
    Hai usato 50/50 transazioni del piano Free.
</div>
```
- **Error**: Red background, red text
- **Success**: Green background, green text
- **Warning**: Amber background, amber text
- Border and icon match alert type

### 9. Empty States

```html
<div class="empty-state">
    <div class="empty-state-icon">📭</div>
    <h3>Nessuna transazione</h3>
    <p>Inizia aggiungendo la tua prima transazione sopra.</p>
</div>
```
- Large emoji icon (64px, 30% opacity)
- Centered text
- Call-to-action message

### 10. Modal

```html
<div class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Titolo Modale</h2>
            <button class="modal-close">×</button>
        </div>
        <!-- Content -->
    </div>
</div>
```
- Full-screen overlay (rgba black 50%)
- Centered content card
- Max-height 90vh with scroll

---

## Category Colors

Each transaction category has a unique color:

```css
.cat-food { background: rgba(245, 158, 11, 0.15); }         /* Amber */
.cat-transport { background: rgba(59, 130, 246, 0.15); }    /* Blue */
.cat-shopping { background: rgba(236, 72, 153, 0.15); }     /* Pink */
.cat-entertainment { background: rgba(168, 85, 247, 0.15); } /* Purple */
.cat-health { background: rgba(239, 68, 68, 0.15); }        /* Red */
.cat-salary { background: rgba(22, 163, 74, 0.15); }        /* Green */
.cat-other { background: rgba(100, 116, 139, 0.15); }       /* Gray */
```

Icons:
- 🍔 Cibo
- 🚗 Trasporti
- 🛍️ Shopping
- 🎬 Intrattenimento
- 💊 Salute
- 💼 Stipendio
- 📦 Altro

---

## Responsive Breakpoints

```css
/* Mobile-first approach */
@media (max-width: 768px) {
    /* Stack layouts */
    /* Reduce font sizes */
    /* Full-width buttons */
}
```

### Mobile Adjustments
- Hero title: 48px → 32px
- Hero text: 20px → 16px
- Stats grid: Multi-column → Single column
- Transaction items: Horizontal → Vertical stacked
- Form grid: Multi-column → Single column
- Nav links: Reduced padding
- Pricing featured: No scale effect

---

## Animations & Transitions

### Standard Transition
```css
transition: all 0.2s ease;
```

### Hover Effects
1. **Buttons**: Background color + lift (`translateY(-1px)`)
2. **Cards**: Shadow deepens
3. **Links**: Background color changes
4. **Transaction items**: Border color changes

### Loading Spinner
```css
.spinner {
    border: 3px solid var(--border);
    border-top-color: var(--primary);
    animation: spin 0.8s linear infinite;
}
```

---

## Accessibility

### Focus States
All interactive elements have visible focus states:
```css
input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
}
```

### Color Contrast
- Text on white: WCAG AA compliant
- Button text: White on dark backgrounds
- Link colors: Sufficient contrast

### Keyboard Navigation
- All buttons and links focusable
- Logical tab order
- Enter/Space to activate

### Screen Readers
- Semantic HTML5 elements
- Alt text for icons (via emoji)
- ARIA labels where needed
- Meaningful button text

---

## Performance

### CSS Optimization
- CSS Variables for theming
- Single font stack (no external fonts)
- Minimal animations (GPU-accelerated)
- Efficient selectors

### Image Strategy
- Emoji icons (no image files)
- No heavy images on landing page
- SVG for future icons

### Loading Strategy
1. Critical CSS inline (if needed)
2. Chart.js loaded async
3. Lazy load transaction images (future)

---

## Brand Voice

### Tone
- **Friendly**: Informal "tu" form
- **Clear**: No jargon, simple language
- **Positive**: Focus on savings, not spending
- **Trustworthy**: Emphasize privacy and security

### Messaging
- "Tieni sotto controllo le tue spese quotidiane"
- "Traccia ogni euro, risparmia di più"
- "Semplice, veloce, privato"
- "Inizia Gratis" (not "Prova Gratis")

### Error Messages
- Helpful, not technical
- Suggest solutions
- Positive language

---

**Design System Version**: 1.0
**Last Updated**: 2025-01-30
