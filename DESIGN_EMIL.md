# PixelCRM Design Engineering & Motion Architecture (Emil Kowalski Spec)

> Dokumen spesifikasi redesain PixelCRM dengan filosofi craft & design engineering Emil Kowalski: tactile responsiveness, micro-interactions, spring physics, dan feedback cohesion.

---

## 1. Filosofi & Karakter Desain

- **Vibe & Aesthetic**: *Tactile, Snappy, Restrained, and Clean* (terinspirasi Apple, Linear, dan Sonner).
- **Komponen Intuitif**: Setiap elemen klik merespons instan (`:active scale(0.97)`), tanpa lag visual, tanpa animasi lambat berlebihan (>300ms dihilangkan).
- **Motion Principle**:
  - Animasi UI masuk/keluar berada di durasi **150ms - 220ms**.
  - Menggunakan custom easing tajam: `--ease-out: cubic-bezier(0.16, 1, 0.3, 1)` atau `--ease-apple: cubic-bezier(0.32, 0.72, 0, 1)`.
  - **Dilarang memakai `ease-in`** pada elemen UI interaktif (terasa sluggish).
  - **Dilarang animasi dari `scale(0)`** (selalu mulai dari `scale(0.95)` + `opacity: 0`).
  - **Dilarang `transition: all`** (selalu sebutkan properti spesifik: `transform`, `opacity`, `background-color`, `border-color`).
  - Tidak ada animasi pada keyboard trigger (Escape / Enter).
  - Hover micro-interactions dilindungi dengan `@media (hover: hover) and (pointer: fine)`.
  - Menghormati preferensi user lewat `@media (prefers-reduced-motion: reduce)`.

---

## 2. Design Tokens & Core Variables

```css
:root {
  /* Surface & Canvas */
  --bg-app: #F8FAFC;
  --bg-surface: #FFFFFF;
  --bg-subtle: #F1F5F9;
  
  /* Text & Contrast */
  --text-main: #0F172A;
  --text-muted: #64748B;
  --text-subtle: #94A3B8;

  /* Borders & Dividers */
  --border-light: #E2E8F0;
  --border-subtle: #F1F5F9;
  --border-focus: #0F172A;

  /* Brand & Accents */
  --brand-primary: #0F172A;
  --brand-primary-hover: #1E293B;
  --accent-blue: #2563EB;
  
  /* Semantic Badges */
  --status-success-bg: #ECFDF5;
  --status-success-text: #065F46;
  --status-success-border: #A7F3D0;
  
  --status-warning-bg: #FFFBEB;
  --status-warning-text: #92400E;
  --status-warning-border: #FDE68A;

  --status-danger-bg: #FEF2F2;
  --status-danger-text: #991B1B;
  --status-danger-border: #FECACA;

  --status-info-bg: #EFF6FF;
  --status-info-text: #1E40AF;
  --status-info-border: #BFDBFE;

  /* Geometry & Radius (Restrained) */
  --radius-sm: 6px;    /* input tags, badge kecil */
  --radius-md: 8px;    /* buttons, text inputs */
  --radius-lg: 12px;   /* navigation items, dropdowns */
  --radius-xl: 16px;   /* cards, panels, modals */

  /* Motion & Timings (Emil Kowalski framework) */
  --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
  --ease-in-out: cubic-bezier(0.77, 0, 0.175, 1);
  --duration-snappy: 160ms;
  --duration-normal: 200ms;
}
```

---

## 3. Tipografi & Tabular Figures

- **Font Family**: `'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif`
- **Tabular Figures**:
  ```css
  .table-editorial td,
  .font-tabular,
  .metric-number,
  .price-tag,
  .phone-number {
    font-feature-settings: "tnum" 1, "cv05" 1;
    font-variant-numeric: tabular-nums;
  }
  ```

---

## 4. Sistem Notifikasi (PixelToast - Sonner Architecture)

Menggantikan browser `alert()` dan PHP banner statis:
- **Komponen**: Toast container mengambang (bottom-right di desktop, bottom-center di mobile).
- **Stacking Physics**: Kartu toast bertumpuk dengan depth halus (`scale(0.95)`, `translateY(8px)`).
- **Dismiss Motion**: Gesture swipe down/right atau close button dengan durasi exit snappy (140ms ease-out).
- **Fungsi Global**: `PixelToast.show(message, type, options)`, `PixelToast.success()`, `PixelToast.error()`, `PixelToast.promise()`.

---

## 5. Rencana Eksekusi Bertahap

| Tahap | Fokus | Target File | Output / Deliverable |
|---|---|---|---|
| **Tahap 1** | Core Tokens, Base CSS, & Tactile Controls | `assets/css/style.css` | Reset timing, hapus `transition: all`, masukkan button micro-press (`scale 0.97`), tabular figures, tokens. |
| **Tahap 2** | Micro-Toast System & Modal Overhaul | `assets/js/toast.js`, `includes/footer.php`, `assets/css/style.css` | PixelToast mandiri ala Sonner, modal entrance centered (`scale(0.95)` ke `1`), ganti alert JS kasar. |
| **Tahap 3** | Shell Layout & Mobile Navigation | `includes/header.php`, `includes/sidebar.php`, `includes/footer.php` | Sidebar navigasi desktop & mobile bottom nav taktil, tap target 44px, sticky topbar polish. |
| **Tahap 4** | Halaman Internal (Dashboard, Produk, Transaksi) | `index.php`, `modules/produk/index.php`, `modules/transaksi/index.php` | Restrained curvature (16px cards, 8-12px buttons), data grid tabular, status badge lembut. |
| **Tahap 5** | Halaman Publik (Login, Checkout `co.php`, `invoice.php`) | `login.php`, `co.php`, `invoice.php` | Split login layout snappy, checkout friction-free, invoice copy-to-clipboard dengan micro-toast feedback. |
