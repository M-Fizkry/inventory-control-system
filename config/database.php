<?php
class Database {
    private $db_file = "db/inventory.sqlite";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // Create db directory if it doesn't exist
            if (!file_exists(dirname($this->db_file))) {
                mkdir(dirname($this->db_file), 0777, true);
            }

            $this->conn = new PDO("sqlite:" . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Enable foreign keys
            $this->conn->exec('PRAGMA foreign_keys = ON');
            
            // Create tables if they don't exist
            $this->initializeTables();
            
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }

        return $this->conn;
    }

    private function initializeTables() {
        $queries = [
            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT NOT NULL,
                last_login DATETIME,
                status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // User access rights
            "CREATE TABLE IF NOT EXISTS user_access (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                menu_id TEXT NOT NULL,
                can_view INTEGER DEFAULT 0,
                can_edit INTEGER DEFAULT 0,
                can_delete INTEGER DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",

            // Items table
            "CREATE TABLE IF NOT EXISTS items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ('raw', 'wip', 'finished')),
                min_stock REAL NOT NULL DEFAULT 0,
                max_stock REAL NOT NULL DEFAULT 0,
                current_stock REAL NOT NULL DEFAULT 0,
                unit TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Bill of Materials (BOM)
            "CREATE TABLE IF NOT EXISTS bom (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                finished_item_id INTEGER NOT NULL,
                component_item_id INTEGER NOT NULL,
                quantity REAL NOT NULL,
                unit TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (finished_item_id) REFERENCES items(id) ON DELETE CASCADE,
                FOREIGN KEY (component_item_id) REFERENCES items(id) ON DELETE CASCADE
            )",

            // Production Plans
            "CREATE TABLE IF NOT EXISTS production_plans (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                plan_type TEXT NOT NULL CHECK(plan_type IN ('1', '2', '3')),
                plan_code TEXT NOT NULL,
                description TEXT,
                status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'active', 'completed', 'cancelled')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Production Items
            "CREATE TABLE IF NOT EXISTS production_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                plan_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                quantity REAL NOT NULL,
                status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'in_progress', 'completed', 'cancelled')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (plan_id) REFERENCES production_plans(id) ON DELETE CASCADE,
                FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
            )",

            // Settings
            "CREATE TABLE IF NOT EXISTS settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT UNIQUE NOT NULL,
                setting_value TEXT,
                setting_type TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",

            // Language strings
            "CREATE TABLE IF NOT EXISTS language_strings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                language_code TEXT NOT NULL,
                string_key TEXT NOT NULL,
                string_value TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(language_code, string_key)
            )",

            // Insert default admin user if not exists
            "INSERT OR IGNORE INTO users (username, password, role) VALUES 
            ('admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN.jf9G.5/MYnGHGRHEei', 'admin')",

            // Insert default settings if not exists
            "INSERT OR IGNORE INTO settings (setting_key, setting_value, setting_type) VALUES 
            ('system_name', 'Inventory Control System', 'string'),
            ('system_logo', 'logo.png', 'file'),
            ('default_language', 'en', 'string')"
        ];

        foreach ($queries as $query) {
            $this->conn->exec($query);
        }
    }
}
?>
