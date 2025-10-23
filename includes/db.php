<?php
class Database {
    private $pdo;
    
    public function __construct() {
        $dbPath = __DIR__ . '/../database/database.sqlite';
        
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        try {
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $this->pdo->exec("PRAGMA foreign_keys = ON");
            
            $this->createTables();
            $this->insertDefaultData();
        } catch (PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    private function createTables() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS Bus_Company (
                id TEXT PRIMARY KEY,
                name TEXT UNIQUE NOT NULL,
                logo_path TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            
            "CREATE TABLE IF NOT EXISTS User (
                id TEXT PRIMARY KEY,
                full_name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                role TEXT NOT NULL CHECK(role IN ('user', 'company', 'admin')),
                password TEXT NOT NULL,
                company_id TEXT NULL,
                balance INTEGER DEFAULT 800,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES Bus_Company(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS Trips (
                id TEXT PRIMARY KEY,
                company_id TEXT NOT NULL,
                departure_city TEXT NOT NULL,
                destination_city TEXT NOT NULL,
                departure_time DATETIME NOT NULL,
                arrival_time DATETIME NOT NULL,
                price INTEGER NOT NULL,
                capacity INTEGER NOT NULL,
                created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES Bus_Company(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS Tickets (
                id TEXT PRIMARY KEY,
                trip_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                status TEXT DEFAULT 'active' CHECK(status IN ('active','canceled','expired')),
                total_price INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (trip_id) REFERENCES Trips(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS Booked_Seats (
                id TEXT PRIMARY KEY,
                ticket_id TEXT NOT NULL,
                seat_number INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES Tickets(id) ON DELETE CASCADE
            )",
            
            "CREATE TABLE IF NOT EXISTS Coupons (
                id TEXT PRIMARY KEY,
                code TEXT NOT NULL,
                discount REAL NOT NULL,
                company_id TEXT NULL,
                usage_limit INTEGER NOT NULL,
                expire_date DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES Bus_Company(id) ON DELETE SET NULL
            )",
            
            "CREATE TABLE IF NOT EXISTS User_Coupons (
                id TEXT PRIMARY KEY,
                coupon_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (coupon_id) REFERENCES Coupons(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES User(id) ON DELETE CASCADE
            )"
        ];
        
        foreach ($tables as $table) {
            $this->pdo->exec($table);
        }
    }
    
    private function insertDefaultData() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM Bus_Company");
        if ($stmt->fetch()['count'] == 0) {
            $companies = [
                "Metro Turizm",
                "Pamukkale Turizm", 
                "Ulusoy",
                "Kamil Koç",
                "Varan Turizm"
            ];
            
            foreach ($companies as $company) {
                $companyId = $this->generateUUID();
                $stmt = $this->pdo->prepare("INSERT INTO Bus_Company (id, name) VALUES (?, ?)");
                $stmt->execute([$companyId, $company]);
            }
        }
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM User");
        if ($stmt->fetch()['count'] == 0) {
            $stmt = $this->pdo->query("SELECT id FROM Bus_Company LIMIT 1");
            $firstCompany = $stmt->fetch();
            
            $users = [
                ['Admin', 'admin@admin.com', 'admin', password_hash('123456', PASSWORD_DEFAULT), null, 1000],
                ['Company Admin', 'company@company.com', 'company', password_hash('123456', PASSWORD_DEFAULT), $firstCompany['id'], 500],
                ['Test User', 'user@user.com', 'user', password_hash('123456', PASSWORD_DEFAULT), null, 800]
            ];
            
            foreach ($users as $user) {
                $userId = $this->generateUUID();
                $stmt = $this->pdo->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, balance) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $user[0], $user[1], $user[2], $user[3], $user[4], $user[5]]);
            }
        }
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM Trips");
        if ($stmt->fetch()['count'] == 0) {
            $stmt = $this->pdo->query("SELECT id FROM Bus_Company");
            $companies = $stmt->fetchAll();
            
            $cities = ['İstanbul', 'Ankara', 'İzmir', 'Antalya', 'Bursa'];
            
            for ($day = 0; $day < 7; $day++) {
                $departureTime = date('Y-m-d H:i:s', strtotime("+$day days +8 hours"));
                $arrivalTime = date('Y-m-d H:i:s', strtotime("+$day days +14 hours"));
                
                foreach ($cities as $from) {
                    foreach ($cities as $to) {
                        if ($from !== $to) {
                            $tripId = $this->generateUUID();
                            $companyId = $companies[array_rand($companies)]['id'];
                            $price = rand(100, 300);
                            
                            $stmt = $this->pdo->prepare("INSERT INTO Trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$tripId, $companyId, $from, $to, $departureTime, $arrivalTime, $price, 40]);
                        }
                    }
                }
            }
        }
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM Coupons");
        if ($stmt->fetch()['count'] == 0) {
            $stmt = $this->pdo->query("SELECT id FROM Bus_Company LIMIT 2");
            $companies = $stmt->fetchAll();
            
            $coupons = [
                ['INDIRIM10', 0.10, null, 100, '2025-12-31 23:59:59'],
                ['INDIRIM20', 0.20, $companies[0]['id'], 50, '2025-12-31 23:59:59'],
                ['YENIYIL25', 0.25, null, 25, '2025-01-31 23:59:59']
            ];
            
            foreach ($coupons as $coupon) {
                $couponId = $this->generateUUID();
                $stmt = $this->pdo->prepare("INSERT INTO Coupons (id, code, discount, company_id, usage_limit, expire_date) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$couponId, $coupon[0], $coupon[1], $coupon[2], $coupon[3], $coupon[4]]);
            }
        }
        
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM Tickets");
        if ($stmt->fetch()['count'] == 0) {
            $stmt = $this->pdo->query("SELECT id FROM User WHERE role = 'user' LIMIT 1");
            $testUser = $stmt->fetch();
            
            $stmt = $this->pdo->query("SELECT id, price FROM Trips LIMIT 1");
            $testTrip = $stmt->fetch();
            
            if ($testUser && $testTrip) {
                $ticketId = $this->generateUUID();
                $stmt = $this->pdo->prepare("INSERT INTO Tickets (id, trip_id, user_id, total_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$ticketId, $testTrip['id'], $testUser['id'], $testTrip['price']]);
                
                $seatId = $this->generateUUID();
                $stmt = $this->pdo->prepare("INSERT INTO Booked_Seats (id, ticket_id, seat_number) VALUES (?, ?, ?)");
                $stmt->execute([$seatId, $ticketId, 15]);
            }
        }
    }
}

$db = new Database();
$pdo = $db->getConnection();
?>
