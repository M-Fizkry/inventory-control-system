<?php
class Language {
    private static $strings = [];
    private static $defaultLang = 'en';
    private static $initialized = false;
    
    public static function init($lang = null) {
        if (self::$initialized) {
            return;
        }

        $lang = $lang ?? CURRENT_LANG;
        self::loadDefaultStrings($lang);
        self::$initialized = true;
    }

    public static function loadFromDatabase($db) {
        if (!$db) return;
        
        try {
            // Load language strings from database
            $query = "SELECT string_key, string_value FROM language_strings WHERE language_code = :lang";
            $stmt = $db->prepare($query);
            $stmt->execute([':lang' => CURRENT_LANG]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                self::$strings[$row['string_key']] = $row['string_value'];
            }
        } catch (Exception $e) {
            error_log("Error loading language strings: " . $e->getMessage());
        }
    }
    
    private static function loadDefaultStrings($lang) {
        global $default_strings;
        if (isset($default_strings[$lang])) {
            self::$strings = $default_strings[$lang];
        }
    }
    
    public static function get($key, $params = []) {
        $text = self::$strings[$key] ?? $key;
        
        // Replace parameters if any
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $text = str_replace(":$param", $value, $text);
            }
        }
        
        return $text;
    }
    
    public static function setLanguage($lang) {
        if (in_array($lang, array_keys($GLOBALS['available_languages']))) {
            $_SESSION['language'] = $lang;
            return true;
        }
        return false;
    }
}

// Default language strings
$default_strings = [
    'en' => [
        // General
        'welcome' => 'Welcome to Inventory Control System',
        'login' => 'Login',
        'logout' => 'Logout',
        'username' => 'Username',
        'password' => 'Password',
        'submit' => 'Submit',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'confirm' => 'Confirm',
        'back' => 'Back',
        
        // Menu
        'dashboard' => 'Dashboard',
        'bom' => 'Bill of Materials',
        'production' => 'Production Plan',
        'users' => 'User Management',
        'settings' => 'Settings',
        
        // Dashboard
        'current_stock' => 'Current Stock',
        'min_stock' => 'Minimum Stock',
        'max_stock' => 'Maximum Stock',
        'stock_status' => 'Stock Status',
        
        // Items
        'raw_material' => 'Raw Material',
        'wip' => 'Work in Progress',
        'finished_goods' => 'Finished Goods',
        'item_code' => 'Item Code',
        'item_name' => 'Item Name',
        'item_type' => 'Item Type',
        'quantity' => 'Quantity',
        'unit' => 'Unit',
        
        // Production
        'plan_type' => 'Plan Type',
        'plan_code' => 'Plan Code',
        'plan_status' => 'Status',
        'production_date' => 'Production Date',
        
        // Messages
        'login_success' => 'Login successful',
        'login_failed' => 'Invalid username or password',
        'save_success' => 'Data saved successfully',
        'save_failed' => 'Failed to save data',
        'delete_confirm' => 'Are you sure you want to delete this item?',
        'delete_success' => 'Data deleted successfully',
        'delete_failed' => 'Failed to delete data',
        
        // Errors
        'access_denied' => 'Access Denied',
        'not_found' => 'Page Not Found',
        'system_error' => 'System Error'
    ],
    
    'id' => [
        // General
        'welcome' => 'Selamat Datang di Sistem Kontrol Inventaris',
        'login' => 'Masuk',
        'logout' => 'Keluar',
        'username' => 'Nama Pengguna',
        'password' => 'Kata Sandi',
        'submit' => 'Kirim',
        'cancel' => 'Batal',
        'save' => 'Simpan',
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'confirm' => 'Konfirmasi',
        'back' => 'Kembali',
        
        // Menu
        'dashboard' => 'Dasbor',
        'bom' => 'Bill of Materials',
        'production' => 'Rencana Produksi',
        'users' => 'Manajemen Pengguna',
        'settings' => 'Pengaturan',
        
        // Dashboard
        'current_stock' => 'Stok Saat Ini',
        'min_stock' => 'Stok Minimum',
        'max_stock' => 'Stok Maksimum',
        'stock_status' => 'Status Stok',
        
        // Items
        'raw_material' => 'Bahan Baku',
        'wip' => 'Barang Setengah Jadi',
        'finished_goods' => 'Barang Jadi',
        'item_code' => 'Kode Barang',
        'item_name' => 'Nama Barang',
        'item_type' => 'Jenis Barang',
        'quantity' => 'Jumlah',
        'unit' => 'Satuan',
        
        // Production
        'plan_type' => 'Tipe Rencana',
        'plan_code' => 'Kode Rencana',
        'plan_status' => 'Status',
        'production_date' => 'Tanggal Produksi',
        
        // Messages
        'login_success' => 'Berhasil masuk',
        'login_failed' => 'Nama pengguna atau kata sandi salah',
        'save_success' => 'Data berhasil disimpan',
        'save_failed' => 'Gagal menyimpan data',
        'delete_confirm' => 'Anda yakin ingin menghapus item ini?',
        'delete_success' => 'Data berhasil dihapus',
        'delete_failed' => 'Gagal menghapus data',
        
        // Errors
        'access_denied' => 'Akses Ditolak',
        'not_found' => 'Halaman Tidak Ditemukan',
        'system_error' => 'Error Sistem'
    ]
];

// Function to initialize language strings in database
function initializeLanguageStrings() {
    global $db, $default_strings;
    
    try {
        $db->beginTransaction();
        
        $query = "INSERT INTO language_strings (language_code, string_key, string_value) 
                  VALUES (:lang, :key, :value) 
                  ON DUPLICATE KEY UPDATE string_value = :value";
        
        $stmt = $db->prepare($query);
        
        foreach ($default_strings as $lang => $strings) {
            foreach ($strings as $key => $value) {
                $stmt->execute([
                    ':lang' => $lang,
                    ':key' => $key,
                    ':value' => $value
                ]);
            }
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error initializing language strings: " . $e->getMessage());
        return false;
    }
}

// Initialize language system with default strings
Language::init();
?>
