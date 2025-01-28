<?php
namespace Database\Versions;

use Database\Migration;

class Migration_20250123153445_initial_schema extends Migration {
    public function up() {
        // Create users table
        $this->pdo->exec("CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
)");

        // Create hotel_owners table
        $this->pdo->exec("CREATE TABLE hotel_owners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    company_name VARCHAR(255),
    contact_phone VARCHAR(50),
    tax_number VARCHAR(100),
    business_address TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    documents_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

        // Create hotels table
        $this->pdo->exec("CREATE TABLE hotels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    owner_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address VARCHAR(255),
    city VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    star_rating INT CHECK (star_rating BETWEEN 1 AND 5),
    base_price DECIMAL(10,2),
    check_in_time TIME DEFAULT '14:00:00',
    check_out_time TIME DEFAULT '11:00:00',
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (owner_id) REFERENCES hotel_owners(id) ON DELETE CASCADE
)");

        // Create hotel_images table
        $this->pdo->exec("CREATE TABLE hotel_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
)");

        // Create amenities table
        $this->pdo->exec("CREATE TABLE amenities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50),
    category VARCHAR(50)
)");

        // Create hotel_amenities table
        $this->pdo->exec("CREATE TABLE hotel_amenities (
    hotel_id INT NOT NULL,
    amenity_id INT NOT NULL,
    PRIMARY KEY (hotel_id, amenity_id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
)");

        // Create room_types table
        $this->pdo->exec("CREATE TABLE room_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    bed_type VARCHAR(50),
    room_size INT,
    amenities JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
)");

        // Create room_type_images table
        $this->pdo->exec("CREATE TABLE room_type_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_type_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
)");

        // Create rooms table
        $this->pdo->exec("CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    room_type_id INT NOT NULL,
    room_number VARCHAR(50) NOT NULL,
    floor VARCHAR(10),
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room_number (hotel_id, room_number),
    deleted_at TIMESTAMP NULL DEFAULT NULL
)");

        // Create bookings table
        $this->pdo->exec("CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    room_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'refunded', 'failed') DEFAULT 'pending',
    trx_no VARCHAR(100),
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
)");

        // Create reviews table
        $this->pdo->exec("CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
)");

        // Create price_history table
        $this->pdo->exec("CREATE TABLE price_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_type_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    effective_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_type_id) REFERENCES room_types(id) ON DELETE CASCADE
)");

        $this->pdo->exec("CREATE INDEX idx_hotel_location ON hotels(city, country)");
        $this->pdo->exec("CREATE INDEX idx_hotel_price ON room_types(base_price)");
        $this->pdo->exec("CREATE INDEX idx_booking_dates ON bookings(check_in, check_out)");
        $this->pdo->exec("CREATE INDEX idx_user_bookings ON bookings(user_id, status)");
        $this->pdo->exec("CREATE INDEX idx_hotel_bookings ON bookings(hotel_id, status)");
        $this->pdo->exec("CREATE INDEX idx_room_availability ON rooms(hotel_id, status)");
        $this->pdo->exec("CREATE INDEX idx_hotel_owner ON hotels(owner_id)");
        $this->pdo->exec("CREATE INDEX idx_room_type ON rooms(room_type_id)");
        $this->pdo->exec("CREATE INDEX idx_booking_payment ON bookings(payment_status)");
        $this->pdo->exec("CREATE INDEX idx_review_rating ON reviews(rating)");
    }

    public function down() {
        // Drop tables in reverse order to handle foreign key constraints
        $this->pdo->exec("DROP TABLE IF EXISTS price_history");
        $this->pdo->exec("DROP TABLE IF EXISTS reviews");
        $this->pdo->exec("DROP TABLE IF EXISTS bookings");
        $this->pdo->exec("DROP TABLE IF EXISTS rooms");
        $this->pdo->exec("DROP TABLE IF EXISTS room_type_images");
        $this->pdo->exec("DROP TABLE IF EXISTS room_types");
        $this->pdo->exec("DROP TABLE IF EXISTS hotel_amenities");
        $this->pdo->exec("DROP TABLE IF EXISTS amenities");
        $this->pdo->exec("DROP TABLE IF EXISTS hotel_images");
        $this->pdo->exec("DROP TABLE IF EXISTS hotels");
        $this->pdo->exec("DROP TABLE IF EXISTS hotel_owners");
        $this->pdo->exec("DROP TABLE IF EXISTS users");
    }
}
