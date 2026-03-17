# PixelCRM

Aplikasi manajemen pelanggan dan produk digital berbasis PHP + MySQL tanpa framework dengan pendekatan **Simple & Fast**.

## 🚀 Features

### ✅ **Authentication System**
- **Login System**: Username/password authentication dengan modern UI
- **Register System**: User registration dengan real-time validation
- **Session Management**: Secure session handling dengan auto-timeout
- **Password Security**: Hash dengan PHP built-in `password_hash()`
- **Protected Routes**: Centralized authentication di header.php (DRY)
- **Public Pages**: Login/register dapat diakses tanpa auth
- **Logout**: Secure logout dengan session destroy

### ✅ **Dashboard Analytics**
- **Real-time Statistics**: Total produk, pelanggan, transaksi, pendapatan
- **Interactive Charts**: Produk terlaris, transaksi terbaru
- **Quick Actions**: Shortcut ke semua modul utama
- **Alert System**: Notifikasi transaksi pending
- **User Welcome**: Display username yang sedang login

### ✅ **Modul Produk (CRUD Complete)**
- **Create**: Form tambah produk dengan validasi lengkap
- **Read**: Daftar produk dengan pagination dan responsive table
- **Update**: Edit produk dengan form validation
- **Delete**: Hapus produk dengan proteksi relational data
- **Meta Pixel Integration**: Facebook Pixel ID, Conversion API token, Event tracking
- **WhatsApp Integration**: Link langsung ke admin WA
- **OneSender Support**: Multiple account management

### ✅ **Modul Pelanggan (CRUD Complete) - NEW!**
- **Create**: Form tambah pelanggan dengan live preview nomor WA
- **Read**: Daftar pelanggan dengan search & pagination yang efisien
- **Update**: Edit pelanggan dengan statistik transaksi
- **Delete**: Hapus pelanggan beserta semua transaksi terkait
- **Bulk Import**: Import banyak pelanggan sekaligus via CSV dengan live preview
- **Search & Filter**: Pencarian nama/nomor WA dengan hasil real-time
- **Histori Transaksi**: Riwayat lengkap pembelian per pelanggan dengan filter status
- **WhatsApp Integration**: Direct link ke chat WA pelanggan
- **Phone Normalization**: Auto-format nomor Indonesia (62xxx)
- **Statistics**: Total transaksi, pembelian, status pelanggan (Baru/Aktif/Lama)

### ✅ **Simple & Fast Architecture**
- **No Framework**: Pure PHP untuk performa maksimal
- **DRY Principle**: Single source of truth untuk semua functions
- **Centralized Auth**: Authentication check di satu tempat (header.php)
- **Modular Structure**: Setiap modul terpisah dan reusable
- **Speed Priority**: Minimal abstraksi, maksimal performance
- **Clean Code**: Short, readable, maintainable

### ✅ **Modern UI/UX**
- **Authentication Pages**: Modern gradient design untuk login/register
- **Real-time Validation**: Password strength dan form validation
- **Responsive Design**: Mobile-first dengan Bootstrap 5
- **Professional Sidebar**: Collapsible navigation dengan user info
- **Modern Cards**: Shadow effects, hover animations
- **Color Scheme**: Consistent gradient theme
- **Interactive Elements**: Loading states, smooth transitions
- **Live Preview**: Real-time preview untuk nomor WA dan CSV data

### ✅ **Security Features**
- **Password Hashing**: Secure password storage dengan `password_hash()`
- **Input Sanitization**: XSS protection dengan htmlspecialchars
- **SQL Injection Prevention**: Prepared statements
- **Session Security**: Secure session configuration
- **Output Buffering**: Prevent headers already sent errors
- **Error Handling**: Graceful error handling dan logging

## 📁 Struktur Folder

```
crm2/
├── includes/                   # ✅ Core System
│   ├── init.php               # All-in-one initialization (DRY approach)
│   ├── header.php             # HTML header + centralized auth
│   ├── sidebar.php            # Responsive navigation + user info
│   ├── footer.php             # HTML footer template
│   └── phpqrcode/             # 🆕 QR Code Library
│       ├── qrlib.php          # Main QR generation library
│       ├── qrconst.php        # QR constants and configurations
│       ├── qrtools.php        # QR utility functions
│       └── qrimage.php        # QR image generation helpers
├── assets/
│   ├── css/
│   │   ├── style.css          # Main stylesheet
│   │   └── layout-override.css # Layout fixes (DRY CSS)
│   └── qr/                    # 🆕 Generated QR Codes Storage
│       ├── qris_1.png         # Auto-generated QRIS QR codes
│       ├── qris_2.png         # Organized by rekening ID
│       └── test.png           # Test QR for validation
├── modules/
│   ├── produk/                # ✅ CRUD Produk (COMPLETE)
│   │   ├── functions.php      # Simple product functions
│   │   ├── index.php          # Product listing dengan pagination
│   │   ├── create.php         # Add product form
│   │   ├── edit.php           # Edit product form
│   │   └── delete.php         # Delete product (with validation)
│   ├── pelanggan/             # ✅ CRUD Pelanggan (COMPLETE)
│   │   ├── functions.php      # Customer management functions
│   │   ├── index.php          # Customer listing dengan search & pagination
│   │   ├── create.php         # Add customer form dengan live preview
│   │   ├── edit.php           # Edit customer form dengan statistik
│   │   ├── delete.php         # Delete customer dengan cascade delete
│   │   ├── bulk.php           # Bulk import CSV dengan live preview
│   │   └── histori.php        # Customer transaction history
│   ├── bundling/              # ✅ CRUD Bundling (COMPLETE)
│   │   ├── functions.php      # Bundling management functions dengan optimasi
│   │   ├── index.php          # Bundling overview dengan grouped display
│   │   ├── create.php         # Multiple bundling creator dengan live preview
│   │   ├── edit.php           # Comprehensive bundling manager per produk
│   │   └── delete.php         # Smart delete dengan redirect ke edit page
│   ├── transaksi/             # ✅ CRUD Transaksi (COMPLETE)
│   │   ├── functions.php      # Transaction management functions
│   │   ├── index.php          # Transaction listing dengan filter & pagination
│   │   ├── create.php         # Create transaction form dengan product selection
│   │   ├── edit.php           # Edit transaction form
│   │   ├── detail.php         # Transaction detail view dengan actions
│   │   ├── delete.php         # Delete individual transaction
│   │   ├── bulk.php           # Bulk import transactions dari CSV
│   │   ├── bulk_delete_old.php # Hapus transaksi pending > 3 bulan
│   │   └── bulk_delete_process.php # AJAX handler untuk bulk delete
│   ├── laporan/               # ✅ Sistem Laporan (COMPLETE)
│   │   ├── functions.php      # Report generation functions
│   │   ├── analitik.php       # Dashboard analitik dengan charts
│   │   └── detail.php         # Detail report viewer
│   ├── rekening/              # ✅ CRUD Rekening (COMPLETE) - NEW!
│   │   ├── functions.php      # Bank account + QRIS management dengan QR generation
│   │   ├── index.php          # Rekening listing dengan QR modal & download
│   │   ├── create.php         # Add rekening form dengan live preview & QR gen
│   │   ├── edit.php           # Edit rekening form dengan QR re-generation
│   │   └── delete.php         # Delete rekening dengan QR cleanup
│   └── template/              # 🔄 Template Pesan (Planned)
│       └── [files...]         # Message template management
├── install_qr.php             # 🆕 QR Library Auto Installer (run once)
├── login.php                  # ✅ Login page dengan modern UI
├── register.php               # ✅ Register page dengan validation
├── logout.php                 # ✅ Logout handler
├── index.php                  # ✅ Dashboard utama (protected)
└── README.md                  # Dokumentasi lengkap
```

## 🛠 Instalasi

### 1. Prerequisites
```bash
- PHP 8.0+ (recommended)
- MySQL 5.7+ atau MariaDB 10.3+
- Web server (Apache/Nginx/Laragon)
```

### 2. Setup Database
1. **Import SQL Schema**:
   ```sql
   -- Gunakan file SQL schema yang disediakan
   -- Database name: crm2 (atau sesuaikan di init.php)
   -- Table users sudah include untuk authentication
   -- Table pelanggan sudah include untuk customer management
   ```

2. **Update Database Config**:
   ```php
   // File: includes/init.php (line 18-22)
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'crm2');          // Ganti sesuai database Anda
   define('DB_USER', 'root');          // Ganti username
   define('DB_PASS', '');              // Ganti password
   ```

### 3. Setup Aplikasi
1. **Clone/Download** source code ke web directory
2. **Update BASE_URL** (opsional, auto-detect):
   ```php
   // File: includes/init.php (line 16)
   // AUTO-DETECT, tidak perlu diubah untuk kebanyakan kasus
   ```

3. **Set Permissions** (jika perlu):
   ```bash
   chmod 755 crm2/
   chmod 644 crm2/includes/*.php
   ```

### 4. First Time Setup
1. **Akses aplikasi**: `http://localhost/crm2/`
2. **Register akun admin**: Klik "Daftar Sekarang"
3. **Login**: Gunakan akun yang sudah dibuat
4. **Mulai menggunakan**: Dashboard, CRUD produk, dan manajemen pelanggan

## 🎯 Pendekatan "Simple & Fast"

### **Philosophy**
- **Speed over Security**: Prioritas performa, keamanan basic tapi solid
- **DRY over Complex**: Single source of truth, tidak ada duplikasi
- **Simple over Elegant**: Code pendek, terbaca, maintainable
- **Performance over Abstraction**: Minimal layers, maksimal speed

### **Technical Approach**

#### **1. All-in-One Init System**
```php
// includes/init.php - Single file untuk semua kebutuhan
- Database connection functions
- Helper functions  
- Configuration constants
- Session management
- Authentication functions
- Error handling with output buffering
```

#### **2. Centralized Authentication**
```php
// includes/header.php - DRY auth approach
$public_pages = ['login.php', 'register.php'];
if (!in_array($current_file, $public_pages)) {
    requireAuth(); // Single place untuk semua auth check
}
```

#### **3. Simple Functions (No Classes)**
```php
// Contoh usage - Produk
$products = fetchAll("SELECT * FROM produk");           // Get data
$result = execute("INSERT INTO produk (...) VALUES (?)", $data);  // Insert

// Contoh usage - Pelanggan
$customers = getAllPelanggan($page, $limit, $search);   // Get customers
$customer = getPelangganById($id);                      // Get single customer
$result = createPelanggan($data);                       // Create customer
$histori = getHistoriPembelian($customer_id);           // Get purchase history
```

#### **4. Clean Include Pattern**
```php
// === LOGIC SECTION (No Output) ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';
// ... all PHP logic here ...

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php';  // Auto-handles auth
require_once __DIR__ . '/../../includes/sidebar.php';
// ... HTML output here ...
require_once __DIR__ . '/../../includes/footer.php';
```

## 📚 API Documentation

### **Authentication Functions**
```php
// Login/Register
loginUser($username, $password)       // Authenticate user
registerUser($username, $password)    // Register new user
logoutUser()                          // Logout current user
requireAuth()                         // Redirect if not authenticated
hashPassword($password)               // Hash password securely
verifyPassword($password, $hash)      // Verify password

// Session helpers
isLoggedIn()                          // Check if user is logged in
```

### **Database Functions**
```php
// Basic operations
fetchAll($sql, $params = [])           // Get multiple rows
fetchRow($sql, $params = [])           // Get single row  
execute($sql, $params = [])            // Insert/Update/Delete
query($sql, $params = [])              // Raw query

// Helper functions
clean($input)                          // Sanitize input (XSS protection)
formatCurrency($amount)                // Format: Rp 1.500.000
formatDate($date, $format = 'd/m/Y')   // Format tanggal
validatePhone($phone)                  // Validate Indonesia phone
statusBadge($status)                   // Generate status badge
whatsappLink($phone, $message = '')    // Generate WA link
```

### **Pelanggan Functions**
```php
// CRUD Operations
getAllPelanggan($page, $limit, $search)    // Get customers with pagination & search
getPelangganById($id)                      // Get customer by ID
createPelanggan($data)                     // Create new customer
updatePelanggan($id, $data)                // Update customer
deletePelangganForce($id)                  // Delete customer + transactions

// Advanced Features
getHistoriPembelian($customer_id)          // Get purchase history
getStatistikPelanggan($customer_id)        // Get customer statistics
normalizePhoneNumber($phone)               // Normalize to 62xxx format
parseCSVData($csv_data)                    // Parse CSV for bulk import
bulkCreatePelanggan($array)                // Bulk create customers

// UI Helpers
generatePagination($current, $total, $url) // Generate pagination HTML
displaySessionMessage()                     // Display flash messages
```

### **Form Helpers**
```php
post($key, $default = null)            // Get POST data safely
get($key, $default = null)             // Get GET data safely  
isPost()                               // Check if POST request
setMessage($msg, $type = 'success')    // Set flash message
getMessage()                           // Get and clear flash message
redirect($url)                         // Redirect dengan output buffering
```

## 🎨 UI Components

### **Authentication Pages**
- **Modern Design**: Gradient backgrounds dengan card layout
- **Real-time Validation**: Password strength checker
- **Responsive**: Mobile-friendly dengan proper spacing
- **Interactive**: Smooth animations dan transitions

### **Responsive Sidebar**
- **Desktop**: Fixed sidebar, auto-margin main content
- **Mobile**: Hidden sidebar with hamburger menu
- **User Info**: Display current username dengan logout button
- **Features**: Active states, smooth animations, touch-friendly

### **Modern Cards**
```html
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0"><i class="fas fa-icon me-2"></i>Title</h5>
    </div>
    <div class="card-body">
        <!-- Content -->
    </div>
</div>
```

### **Live Preview Components**
- **Phone Number Preview**: Real-time normalization 0812xxx → 6212xxx
- **CSV Data Preview**: Live table preview saat mengetik data CSV
- **Status Badges**: Dynamic color-coded status indicators
- **Search Results**: Real-time search dengan highlight

## 🔧 Development Guidelines

### **1. Adding New Module**
```php
// 1. Create folder: modules/nama_modul/
// 2. Create functions.php:
function getAllNamaModul($page = 1, $limit = 10, $search = '') {
    $offset = ($page - 1) * $limit;
    // Add search logic if needed
    return fetchAll("SELECT * FROM table ORDER BY id DESC LIMIT $limit OFFSET $offset");
}

// 3. Create CRUD files following the pattern:
// - index.php (listing) - Auto-protected oleh header.php
// - create.php (add form) - Auto-protected  
// - edit.php (edit form) - Auto-protected
// - delete.php (delete action) - Auto-protected
```

### **2. Adding Public Pages**
```php
// File: includes/header.php
$public_pages = ['login.php', 'register.php', 'api.php', 'webhook.php'];
// Tambah file yang tidak perlu authentication di array ini
```

### **3. File Structure Pattern**
```php
<?php
// === LOGIC SECTION ===
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/functions.php';

// Handle form submissions, data processing
// ... all PHP logic, no HTML output ...

// === PRESENTATION SECTION ===
require_once __DIR__ . '/../../includes/header.php'; // Auto-auth check
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<!-- HTML content here -->
<div class="main-content">
    <!-- Your content -->
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
```

## 🔐 Security Best Practices

### **1. Authentication Security**
```php
// ✅ Good - Secure password handling
$user = registerUser($username, $password); // Auto-hash password
$login = loginUser($username, $password);   // Auto-verify hash

// ❌ Bad - Plain text passwords
// NEVER store plain text passwords
```

### **2. Database Security**
```php
// ✅ Good - Using prepared statements
$customers = fetchAll("SELECT * FROM pelanggan WHERE nama LIKE ?", ["%$search%"]);

// ❌ Bad - SQL injection risk  
$customers = fetchAll("SELECT * FROM pelanggan WHERE nama LIKE '%$search%'");
```

### **3. Input Security**
```php
// ✅ Good - Input sanitization
$name = clean(post('nama'));
$phone = normalizePhoneNumber(clean(post('nomor_wa')));

// ❌ Bad - XSS risk
$name = $_POST['nama'];
```

## 🚀 Performance Tips

### **1. Authentication Optimization**
- Session-based auth (tidak ada DB query per request)
- Password hashing dengan PHP built-in (optimal)
- Centralized auth check (no duplicate code)

### **2. Database Optimization**
- Gunakan `LIMIT` untuk pagination
- Index pada kolom yang sering di-query (nama, nomor_wa)
- Prepared statements untuk security + performance
- Efficient search queries dengan LIKE optimization

### **3. CSS/JS Optimization**
- CSS di-load dari CDN (Bootstrap, FontAwesome)
- Minimal custom CSS untuk override
- Inline JavaScript untuk interactivity
- Live preview menggunakan vanilla JavaScript (no jQuery)

## 📊 Ready for Production

### **Authentication Checklist**
- [x] ✅ Secure password hashing
- [x] ✅ Session management
- [x] ✅ Input validation
- [x] ✅ XSS protection
- [x] ✅ SQL injection prevention
- [x] ✅ Centralized auth logic

### **Customer Management Checklist**
- [x] ✅ CRUD operations complete
- [x] ✅ Search & pagination
- [x] ✅ Bulk import functionality
- [x] ✅ Transaction history tracking
- [x] ✅ Phone number normalization
- [x] ✅ WhatsApp integration
- [x] ✅ Input validation & sanitization

### **Deployment Checklist**
- [ ] Update database credentials di `init.php`
- [ ] Set proper file permissions
- [ ] Enable error logging di hosting
- [ ] Test authentication flow
- [ ] Create admin user via register
- [ ] Test all CRUD operations (Produk & Pelanggan)
- [ ] Test bulk import functionality
- [ ] Verify responsive design di mobile
- [ ] Test WhatsApp links
- [ ] Verify phone number normalization

### **First User Setup**
1. Deploy aplikasi ke server
2. Akses URL aplikasi (redirect ke login)
3. Klik "Daftar Sekarang"
4. Buat akun admin pertama
5. Login dan mulai menggunakan aplikasi
6. Test modul Produk dan Pelanggan
7. Import data pelanggan jika ada

## 🎯 Usage Examples

### **Mengelola Pelanggan**
```php
// Tambah pelanggan baru
$data = [
    'nama' => 'John Doe',
    'nomor_wa' => '08123456789'  // Auto-normalized ke 628123456789
];
$customer_id = createPelanggan($data);

// Cari pelanggan
$customers = getAllPelanggan($page = 1, $limit = 10, $search = 'john');

// Lihat histori pembelian
$histori = getHistoriPembelian($customer_id);

// Import bulk via CSV
$csv_data = "John Doe,08123456789\nJane Smith,081987654321";
$import_result = bulkCreatePelanggan(parseCSVData($csv_data));
```

### **WhatsApp Integration**
```php
// Generate WhatsApp link
$wa_link = whatsappLink('628123456789', 'Halo, terima kasih sudah berbelanja!');
// Result: https://wa.me/628123456789?text=Halo%2C%20terima%20kasih%20sudah%20berbelanja%21
```

### **Phone Number Normalization**
```php
normalizePhoneNumber('0812-3456-789');    // → 628123456789
normalizePhoneNumber('+62 812 3456 789'); // → 628123456789  
normalizePhoneNumber('812.3456.789');     // → 628123456789
```

## 📞 Support & Contribution

### **Getting Help**
- Check dokumentasi di README ini
- Review code comments di setiap file
- Test di local development environment
- Check authentication flow jika ada masalah login
- Test phone normalization dengan berbagai format

### **Contributing**
1. Fork repository
2. Create feature branch
3. Follow coding standards (simple & fast)
4. Test authentication dan CRUD operations
5. Test customer management features
6. Submit pull request

### **Known Issues & Solutions**
- **Phone validation**: Format Indonesia 62xxx, 8-11 digits setelah 62
- **CSV Import**: Support format "Nama,Nomor WA" dengan auto-detection
- **Search Performance**: Index pada `nama` dan `nomor_wa` untuk performa optimal
- **Memory Usage**: Pagination pada histori transaksi untuk large datasets

---

**Version**: 2.2.0 (With Customer Management)  
**Last Updated**: Desember 2024  
**Status**: Production Ready  
**Approach**: Speed Priority, DRY Principle, Complete Customer Management

🎯 **Built for speed, designed for simplicity, complete with customer management.**

## 🆕 What's New in v2.2.0

### **Customer Management Module**
- ✅ Complete CRUD operations untuk pelanggan
- ✅ Advanced search & pagination
- ✅ Bulk CSV import dengan live preview
- ✅ Transaction history per customer
- ✅ Phone number normalization (Indonesia format)
- ✅ WhatsApp direct integration
- ✅ Customer statistics & status tracking
- ✅ Responsive UI dengan modern design

### **Enhanced Architecture**
- ✅ Function-based approach (no classes) untuk maximum speed
- ✅ Consistent error handling across all modules
- ✅ Improved input sanitization
- ✅ Better pagination system
- ✅ Enhanced UI components

### **Performance Improvements**
- ✅ Optimized database queries
- ✅ Efficient search implementation
- ✅ Minimal JavaScript untuk live features
- ✅ Better memory usage untuk large datasets