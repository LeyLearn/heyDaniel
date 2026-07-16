# HeyDaniel — UI/UX Design Document

**Scope:** Full-site inventory of the current UI/UX, derived from the codebase.
**Nature:** HeyDaniel is a multi-platform e-commerce platform. The PHP/MySQL backend under `Server/` is a shared API consumed by both this web frontend and a separate mobile app — endpoints branch on a `device_type` parameter (`Web`, `iOS`, `Android`) and read session vs. JSON body accordingly.

---

## 1. Information Architecture

```
Home (index.php)
├── Store (product catalog)              Interface/Sheets/Store.php
│   └── Item (product detail)            Interface/Sheets/Item.php
├── Cart                                  Interface/Sheets/Cart.php
│   └── Checkout                          Interface/Sheets/Checkout.php
│       └── Confirmation                  Interface/Sheets/Confirmation.php
├── Saved / Wishlist                      Interface/Sheets/Saved.php
├── Profile (auth-gated)                  Interface/Sheets/Profile.php
│   └── Credential (login/register)       Interface/Sheets/Credential.php
```

Category taxonomy is 3 levels deep: **Main Category → Sub Category → Third Category**, backed by `Server/Secure/Store/Filter/{MainCategory,SubCategory,ThirdCategory,Filter}.php`.

8 main categories exist today (with art in `Assets/Categories/`): Baby, Beauty, Care, Electronics, Groceries, Households, Kitchen, Pets.

---

## 2. Page Shell & Navigation

Every page shares a common shell:

- **Header** (sticky) — logo (links home) · search bar (icon toggles search/close) · account · wishlist · cart
- **Sub-header** (sticky, accent-red) — location selector (city/state/zip, opens a right-slide sidebar) · dynamic delivery-eligibility banner · running order subtotal
- **Location sidebar** — ZIP input (regex-validated `\d{5}(-\d{4})?`), "use my location" (IP geolocation via `ipapi.co`), history of previously-entered ZIPs, continue button
- **Skeleton loading overlay** — shown at `z-index: 9999` until initial data fetch resolves
- **Footer** — currently an empty placeholder, no content yet

**Core flow:** Home → Store (browse/filter/search) → Item (detail) → Add to Cart → Cart → Checkout (address, payment, tip) → Finalize → Confirmation.

**Auth flow:** Profile is auth-gated — if `$_SESSION['user_id']` is unset, it swaps in the Credential component instead of the dashboard. Credential supports email/password and Google Sign-In (OAuth2 ID token flow), with inline show/hide password, forgot-password link, and toggling between login/register forms via fade transitions.

---

## 3. Visual Design System

### Color tokens (as currently defined in CSS)

| Token | Value | Used for |
|---|---|---|
| `--primary-color` | `#02163C` | Header, primary UI ink (navy) |
| `--bg-color` | `#f2f3f2` | Page background |
| `--accent-red` | `#8d1111` | Sub-header bar, key CTAs |
| `--primary` (button) | `#7a0d0d` | Primary buttons |
| `--primary-light` | `#8B1111` | Primary button hover |
| `--primary-accent` | `#D4A5A5` | Tertiary/soft accents |
| `--secondary` | `#013A52` | Secondary buttons (navy) |
| `--secondary-light` | `#064863` | Secondary hover |
| `--text-dark` / `--text-light` | `#333` / `#fff` | Body text on light/dark |
| `--text-muted` | `rgba(2,22,60,0.7)` | Secondary copy |
| `--border-light` | `rgba(2,22,60,0.5)` | Hairline borders |

**Observation:** the header's palette (`Header.css`) and the button palette (`Button.css`) each define their own near-duplicate maroon/navy tokens independently rather than sharing one root token set — worth flagging if you ever consolidate into a single theme file.

### Typography

System font stack (`-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif`), weight 600 as the default body weight. Type scale is small and dense (mobile-commerce style):

| Token | Size |
|---|---|
| `--title` | 1.3rem |
| `--subtitle` | 0.95rem |
| `--regular-text` | 0.85rem |
| `--mini-title` | 0.7rem |
| `--information` | 0.75rem |
| `--fine-print` | 0.65rem |

### Responsive breakpoints

Mobile ≤480px · Tablet 481–768px · Desktop 769–1024px · Laptop/Large 1025px+ — applied consistently across `Header.css`, `Button.css`, `Card.css`, `Icon.css`.

### Component library present in CSS

- **Buttons** (`Button.css`, 740 lines) — primary/secondary/text variants, add-to-cart, quantity stepper, save/wishlist, star-rating review buttons, checkout/payment, filter chips, order-history actions (view/reorder/download invoice), auth buttons
- **Cards** (`Card.css`, 437 lines) — base/elevated/flat/outlined containers, product card (image/title/brand/price/rating), modal/dialog, flex Row/Col grid system
- **Icons** (`Icon.css`, 311 lines) — sizes XS→2XL, semantic color variants, badges, spin/bounce/pulse states — backed by 31 SVGs in `Assets/Icons/`

### Gaps in the current styling system

These CSS files exist as empty placeholders (0–1 lines): `Category.css`, `Filter.css`, `Input.css`, `Loading.css`, `Pictures.css`, `Text.css`, `Ad.css`, and every page-level sheet except `Credential.css` (`Cart.css`, `Saved.css`, `Store.css`, `Item.css`, `Checkout.css`, `Confirmation.css`, `Profile.css`). Structurally they're wired up (imported in `Head.php`) but have no rules yet — meaning Store, Item, Cart, Checkout, Confirmation, Saved, and Profile currently render with only the shared Header/Button/Card/Icon styles and no page-specific layout CSS.

---

## 4. Client-Side Interaction Patterns

`Client/Component.js` (data/actions) + `Client/Interaction.js` (jQuery event wiring):

- **Device/location:** `DeviceCheck()` on load determines device recognition + delivery eligibility; `DeviceLog()` persists a ZIP to the device and updates the delivery banner
- **Cart:** `addProduct()`, `decrementProduct()`, `clearCart()`, `cartIcon()`, `cartItem()` — all AJAX POST to `Server/index.php` with an `action` discriminator; cart badge count updates without page reload
- **Auth:** `register()`, `login()`, `googleLogin()`, `logout()` — inline loading states on submit buttons, error banners on failure, full page reload on success (no SPA-style state hydration)
- **Search:** input toggles search/clear icon; no visible autocomplete/suggestions wiring yet
- **Known bug in the wiring:** in `Interaction.js`, the `.nav-wishlist` header icon currently calls `logout()` instead of navigating to `Saved.php` — likely leftover/placeholder code, not intentional behavior

---

## 5. Backend Feature Surface (drives what UI must support)

| Area | Files | Implies UI for... |
|---|---|---|
| Cart | `Server/Secure/Cart/*` | add/remove/decrement, live subtotal, cart badge |
| Checkout | `Server/Secure/Checkout/*` | address form, payment method, tip, tax, same-day eligibility |
| Store/Filter | `Server/Secure/Store/*` | grid + filter sidebar/chips across 3-level category tree, "similar products" |
| Saved | `Server/Secure/Saved/*` | wishlist toggle + count + list view |
| Reviews | `Server/Secure/Reviews/*` | 1–5 star rating, title/expectation/body fields, paginated list |
| User | `Server/Secure/User/*` | register (name/email/password strength rules), login, Google SSO, logout |
| Orders | `Server/Secure/Order/OrderHistory.php` | order history list with status/items/totals |
| Search | `Server/Secure/Engine/Search.php`, `ItemPush.php`, `RecentlyViewed.php` | search results, recommendations, recently-viewed rail |
| Reset password | `Server/Secure/ResetPassWord/*` | email-verify → change-password flow |
| Device | `Server/Secure/Device/*` | first-visit ZIP capture, delivery-eligibility gating |

Product data model (as consumed by the client): `product_id, name, brand, oz, price, total_price, picture, ratings, review_count, is_on_sale, is_bogo, is_saved, quantity`.

---

## 6. Open Questions for Next Steps

This document describes what exists today. It surfaces a few things worth a decision before going further:

1. **Empty page-level stylesheets** — Store/Item/Cart/Checkout/Confirmation/Saved/Profile have no page-specific CSS yet. Is layout for these pages still in progress, or intentionally deferred?
2. **Wishlist nav bug** — `.nav-wishlist` triggers logout instead of routing to Saved.php.
3. **Footer and Profile section component** are empty placeholders — no content defined yet.
4. **Two divergent color-token sets** (Header.css vs Button.css) — fine as-is, but worth knowing if a shared theme file is ever wanted.

Let me know if you'd like me to go further in any direction — e.g. wireframe the pages that currently have no CSS, propose a unified design-token file, or sketch flows for the incomplete pieces (Footer, Profile dashboard, wishlist nav).
