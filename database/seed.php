<?php
require_once 'config/db_connect.php';

try {
    $pdo->beginTransaction();

    // Insert sample users
    $users = [
        ['john_doe', 'john.doe@example.com', '12345678', 'John', 'Doe', '+1234567890', 0],
        ['admin_user', 'admin@example.com', '12345678', 'Admin', 'User', '+1987654321', 1],
        ['sarah_smith', 'sarah.smith@example.com', 'sarah123', 'Sarah', 'Smith', '+1122334455', 0],
        ['hotel_owner1', 'owner1@hotels.com', 'owner123', 'James', 'Wilson', '+1234567891', 0],
        ['hotel_owner2', 'owner2@hotels.com', 'owner456', 'Maria', 'Garcia', '+1234567892', 0],
        ['emma_jones', 'emma.jones@example.com', 'emma123', 'Emma', 'Jones', '+1234567893', 0],
        ['michael_brown', 'michael.b@example.com', 'michael123', 'Michael', 'Brown', '+1234567894', 0],
        ['lisa_wong', 'lisa.wong@example.com', 'lisa123', 'Lisa', 'Wong', '+1234567895', 0],
        ['hotel_owner3', 'owner3@hotels.com', 'owner789', 'Robert', 'Chen', '+1234567896', 0],
        ['hotel_owner4', 'owner4@hotels.com', 'owner101', 'Anna', 'Kowalski', '+1234567897', 0]
    ];

    $userIds = []; // Track user IDs
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, is_admin) 
                          VALUES (:username, :email, :password, :first_name, :last_name, :phone, :is_admin)");

    foreach ($users as $index => $user) {
        $stmt->execute([
            ':username' => $user[0],
            ':email' => $user[1],
            ':password' => password_hash($user[2], PASSWORD_DEFAULT),
            ':first_name' => $user[3],
            ':last_name' => $user[4],
            ':phone' => $user[5],
            ':is_admin' => (int)$user[6]
        ]);
        $userIds[$user[0]] = $pdo->lastInsertId(); // Store user ID by username
        echo "Created user: {$user[0]} (ID: {$userIds[$user[0]]})\n";
    }

    // Insert hotel owners (using actual user IDs)
    $hotelOwners = [
        ['hotel_owner1', 'Luxury Hotels Inc', '+1234567891', 'LH123456', '123 Business Ave, New York, NY'],
        ['hotel_owner2', 'Global Resorts Ltd', '+1234567892', 'GR789012', '456 Corporate Blvd, Miami, FL'],
        ['hotel_owner3', 'Asian Hospitality Group', '+1234567896', 'AH345678', '789 Eastern Ave, San Francisco, CA'],
        ['hotel_owner4', 'European Hotels Chain', '+1234567897', 'EH901234', '321 Western Rd, Chicago, IL']
    ];

    $stmt = $pdo->prepare("INSERT INTO hotel_owners (user_id, company_name, contact_phone, tax_number, business_address, status) 
                          VALUES (:user_id, :company_name, :contact_phone, :tax_number, :business_address, 'approved')");

    $ownerIds = []; // Track owner IDs
    foreach ($hotelOwners as $owner) {
        $userId = $userIds[$owner[0]]; // Get the actual user ID
        $stmt->execute([
            ':user_id' => $userId,
            ':company_name' => $owner[1],
            ':contact_phone' => $owner[2],
            ':tax_number' => $owner[3],
            ':business_address' => $owner[4]
        ]);
        $ownerId = $pdo->lastInsertId();
        $ownerIds[$owner[0]] = $ownerId;
        echo "Created hotel owner: {$owner[1]} (User ID: $userId, Owner ID: $ownerId)\n";
    }

    // Insert amenities
    $amenities = [
        ['WiFi', 'wifi', 'connectivity'],
        ['Swimming Pool', 'pool', 'recreation'],
        ['Gym', 'fitness', 'recreation'],
        ['Restaurant', 'restaurant', 'dining'],
        ['Parking', 'parking', 'facility'],
        ['Spa', 'spa', 'wellness'],
        ['Room Service', 'room-service', 'service'],
        ['Conference Room', 'meeting', 'business'],
        ['Beach Access', 'beach', 'recreation'],
        ['Bar/Lounge', 'bar', 'dining'],
        ['Kids Club', 'kids', 'recreation'],
        ['Tennis Court', 'tennis', 'sports'],
        ['Golf Course', 'golf', 'sports'],
        ['Airport Shuttle', 'shuttle', 'transport'],
        ['Pet Friendly', 'pet', 'service']
    ];

    $stmt = $pdo->prepare("INSERT INTO amenities (name, icon, category) VALUES (:name, :icon, :category)");
    
    $amenityIds = []; // Track amenity IDs
    foreach ($amenities as $index => $amenity) {
        $stmt->execute([
            ':name' => $amenity[0],
            ':icon' => $amenity[1],
            ':category' => $amenity[2]
        ]);
        $amenityIds[] = $pdo->lastInsertId();
        echo "Created amenity: {$amenity[0]} (ID: {$amenityIds[$index]})\n";
    }

    // Insert hotels (using actual owner IDs)
    $hotels = [
        ['hotel_owner1', 'Hotel Paradise', 'A luxury hotel with breathtaking views', '123 Ocean Avenue', 'Miami', 'USA', '33139', 25.7617, -80.1918, 5, 250.00],
        ['hotel_owner1', 'Mountain Escape', 'A peaceful mountain retreat', '456 Mountain Rd', 'Denver', 'USA', '80202', 39.7392, -104.9903, 4, 180.00],
        ['hotel_owner2', 'City Lights Hotel', 'Modern luxury in downtown', '789 Downtown Blvd', 'New York', 'USA', '10001', 40.7128, -74.0060, 5, 350.00],
        ['hotel_owner3', 'Seaside Resort', 'Beachfront luxury resort', '321 Beach Dr', 'San Diego', 'USA', '92101', 32.7157, -117.1611, 5, 400.00],
        ['hotel_owner3', 'Desert Oasis', 'Luxury in the desert', '567 Palm Springs Rd', 'Phoenix', 'USA', '85001', 33.4484, -112.0740, 4, 280.00],
        ['hotel_owner4', 'Alpine Lodge', 'Cozy mountain getaway', '890 Snow Valley', 'Aspen', 'USA', '81611', 39.1911, -106.8175, 4, 320.00],
        ['hotel_owner4', 'Urban Boutique', 'Stylish city hotel', '432 Downtown Ave', 'Chicago', 'USA', '60601', 41.8781, -87.6298, 4, 290.00]
    ];

    $stmt = $pdo->prepare("INSERT INTO hotels (owner_id, name, description, address, city, country, postal_code, 
                          latitude, longitude, star_rating, base_price) 
                          VALUES (:owner_id, :name, :description, :address, :city, :country, :postal_code, 
                          :latitude, :longitude, :star_rating, :base_price)");

    $hotelIds = []; // Track hotel IDs
    foreach ($hotels as $index => $hotel) {
        $ownerId = $ownerIds[$hotel[0]];
        $stmt->execute([
            ':owner_id' => $ownerId,
            ':name' => $hotel[1],
            ':description' => $hotel[2],
            ':address' => $hotel[3],
            ':city' => $hotel[4],
            ':country' => $hotel[5],
            ':postal_code' => $hotel[6],
            ':latitude' => $hotel[7],
            ':longitude' => $hotel[8],
            ':star_rating' => $hotel[9],
            ':base_price' => $hotel[10]
        ]);
        
        $hotelId = $pdo->lastInsertId();
        $hotelIds[] = $hotelId;
        echo "Created hotel: {$hotel[1]} (ID: $hotelId)\n";

        // Add 4 images for each hotel
            $stmtImage = $pdo->prepare("INSERT INTO hotel_images (hotel_id, image_url, is_primary) 
                                      VALUES (:hotel_id, :image_url, :is_primary)");
            
        for ($i = 0; $i < 4; $i++) {
                $stmtImage->execute([
                    ':hotel_id' => $hotelId,
                ':image_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRNk3EkamoduhvQHh2eiogSUxtB7xq9HbgQxQ&s',
                ':is_primary' => ($i === 0) ? 1 : 0
                ]);
            echo "Added image for hotel {$hotel[1]}\n";
        }
        
        // Add hotel amenities (using actual amenity IDs)
        $stmt2 = $pdo->prepare("INSERT INTO hotel_amenities (hotel_id, amenity_id) VALUES (:hotel_id, :amenity_id)");
        // Randomly select 5 amenities for each hotel
        $hotelAmenities = array_rand($amenityIds, 5);
        foreach ($hotelAmenities as $amenityIndex) {
            $stmt2->execute([
                ':hotel_id' => $hotelId,
                ':amenity_id' => $amenityIds[$amenityIndex]
            ]);
            echo "Added amenity {$amenities[$amenityIndex][0]} to hotel {$hotel[1]}\n";
        }
    }

    // Insert room types with proper hotel IDs
    $roomTypes = [
        [$hotelIds[0], 'Standard Room', 'Comfortable room with basic amenities', 200.00, 2, 'Queen', 30],
        [$hotelIds[0], 'Deluxe Suite', 'Spacious suite with separate living area', 350.00, 3, 'King', 45],
        [$hotelIds[1], 'Mountain View Room', 'Room with stunning mountain views', 250.00, 2, 'Queen', 35],
        [$hotelIds[2], 'Executive Suite', 'Luxury suite with city views', 450.00, 2, 'King', 50],
        [$hotelIds[3], 'Ocean View Suite', 'Luxurious suite with ocean views', 500.00, 4, 'King', 60],
        [$hotelIds[3], 'Beach Bungalow', 'Private bungalow steps from the beach', 600.00, 2, 'King', 55],
        [$hotelIds[4], 'Desert View Room', 'Room with panoramic desert views', 280.00, 2, 'Queen', 35],
        [$hotelIds[5], 'Ski Chalet Suite', 'Cozy suite with fireplace', 400.00, 4, 'King', 65],
        [$hotelIds[6], 'City Loft', 'Modern loft-style room', 320.00, 2, 'Queen', 40]
    ];

    $stmt = $pdo->prepare("INSERT INTO room_types (hotel_id, name, description, base_price, capacity, bed_type, room_size) 
                          VALUES (:hotel_id, :name, :description, :base_price, :capacity, :bed_type, :room_size)");

    $roomTypeIds = []; // Track room type IDs
    $roomNumberCounter = []; // Track room numbers per hotel
    
    foreach ($roomTypes as $type) {
        $hotelId = $type[0]; // This is now a valid hotel ID from $hotelIds array
        
        $stmt->execute([
            ':hotel_id' => $hotelId,
            ':name' => $type[1],
            ':description' => $type[2],
            ':base_price' => $type[3],
            ':capacity' => $type[4],
            ':bed_type' => $type[5],
            ':room_size' => $type[6]
        ]);

        $roomTypeId = $pdo->lastInsertId();
        $roomTypeIds[] = $roomTypeId;
        echo "Created room type: {$type[1]} for hotel ID: $hotelId (Room Type ID: $roomTypeId)\n";

        // Initialize counter for this hotel if not exists
        if (!isset($roomNumberCounter[$hotelId])) {
            $roomNumberCounter[$hotelId] = 1;
        }

        // Add rooms for each room type
        $stmt2 = $pdo->prepare("INSERT INTO rooms (hotel_id, room_type_id, room_number, floor) 
                               VALUES (:hotel_id, :room_type_id, :room_number, :floor)");
        
        // Create 5 rooms for each room type
        for ($i = 1; $i <= 5; $i++) {
            $floor = floor(($roomNumberCounter[$hotelId] - 1) / 10) + 1;
            $roomNumber = sprintf("%d%02d", $floor, $roomNumberCounter[$hotelId] % 100);
            
            $stmt2->execute([
                ':hotel_id' => $hotelId,
                ':room_type_id' => $roomTypeId,
                ':room_number' => $roomNumber,
                ':floor' => $floor
            ]);
            
            echo "Created room: Hotel $hotelId, Room $roomNumber (Floor $floor)\n";
            $roomNumberCounter[$hotelId]++;
        }

        // Add room type images
        $stmt3 = $pdo->prepare("INSERT INTO room_type_images (room_type_id, image_url, is_primary) 
                               VALUES (:room_type_id, :image_url, :is_primary)");
        
        // Add 4 images for each room type
        for ($i = 0; $i < 4; $i++) {
            $stmt3->execute([
                ':room_type_id' => $roomTypeId,
                ':image_url' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRNk3EkamoduhvQHh2eiogSUxtB7xq9HbgQxQ&s',
                ':is_primary' => ($i === 0) ? 1 : 0
            ]);
            echo "Added image for room type {$type[1]}\n";
        }
    }

    // Update bookings to use correct IDs
    $bookings = [
        [$userIds['john_doe'], $hotelIds[0], 1, '2024-03-15', '2024-03-20', 2, 1000.00, 'confirmed', 'paid'],
        [$userIds['admin_user'], $hotelIds[1], 6, '2024-03-18', '2024-03-22', 1, 1000.00, 'confirmed', 'paid'],
        [$userIds['sarah_smith'], $hotelIds[2], 11, '2024-04-01', '2024-04-05', 2, 1800.00, 'pending', 'pending'],
        [$userIds['emma_jones'], $hotelIds[3], 16, '2024-04-10', '2024-04-15', 2, 2500.00, 'confirmed', 'paid'],
        [$userIds['michael_brown'], $hotelIds[4], 21, '2024-05-01', '2024-05-05', 1, 1120.00, 'confirmed', 'paid'],
        [$userIds['lisa_wong'], $hotelIds[5], 26, '2024-05-15', '2024-05-20', 4, 2000.00, 'pending', 'pending'],
        [$userIds['john_doe'], $hotelIds[6], 31, '2024-06-01', '2024-06-05', 2, 1280.00, 'confirmed', 'paid']
    ];

    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, hotel_id, room_id, check_in, check_out, guests, 
                          total_price, status, payment_status) 
                          VALUES (:user_id, :hotel_id, :room_id, :check_in, :check_out, :guests, 
                          :total_price, :status, :payment_status)");

    foreach ($bookings as $booking) {
        $stmt->execute([
            ':user_id' => $booking[0],
            ':hotel_id' => $booking[1],
            ':room_id' => $booking[2],
            ':check_in' => $booking[3],
            ':check_out' => $booking[4],
            ':guests' => $booking[5],
            ':total_price' => $booking[6],
            ':status' => $booking[7],
            ':payment_status' => $booking[8]
        ]);

        $bookingId = $pdo->lastInsertId();

        // Add reviews for completed bookings
        if ($booking[7] === 'confirmed') {
            $stmt2 = $pdo->prepare("INSERT INTO reviews (booking_id, user_id, hotel_id, rating, comment) 
                                   VALUES (:booking_id, :user_id, :hotel_id, :rating, :comment)");
            
            $stmt2->execute([
                ':booking_id' => $bookingId,
                ':user_id' => $booking[0],
                ':hotel_id' => $booking[1],
                ':rating' => rand(4, 5),
                ':comment' => 'Great stay! Would recommend.'
            ]);
        }
    }

    // Add more reviews with varied ratings and detailed comments
    $reviews = [
        ['Excellent stay! The staff was very attentive and the room was spotless.', 5],
        ['Great location and amenities, but the wifi was a bit slow.', 4],
        ['Beautiful property with amazing views. Will definitely return!', 5],
        ['Good value for money, but the breakfast could be improved.', 3],
        ['Perfect for a business trip. Convenient location and great service.', 4],
        ['Absolutely loved the spa services and the pool area.', 5],
        ['Room was smaller than expected, but very comfortable.', 4]
    ];

    // Add reviews for completed bookings with varied feedback
    foreach ($bookings as $booking) {
        if ($booking[7] === 'confirmed') {
            $randomReview = $reviews[array_rand($reviews)];
            $stmt2 = $pdo->prepare("INSERT INTO reviews (booking_id, user_id, hotel_id, rating, comment) 
                                   VALUES (:booking_id, :user_id, :hotel_id, :rating, :comment)");
            
            $stmt2->execute([
                ':booking_id' => $bookingId,
                ':user_id' => $booking[0],
                ':hotel_id' => $booking[1],
                ':rating' => $randomReview[1],
                ':comment' => $randomReview[0]
            ]);
        }
    }

    // Add price history data
    $stmt = $pdo->prepare("INSERT INTO price_history (room_type_id, price, effective_date) 
                          VALUES (:room_type_id, :price, :effective_date)");

    foreach ($roomTypeIds as $roomTypeId) {
        // Add historical prices for the last 3 months
        for ($i = 1; $i <= 3; $i++) {
            $date = date('Y-m-d', strtotime("-$i month"));
            $basePrice = rand(150, 500);
            
            $stmt->execute([
                ':room_type_id' => $roomTypeId,
                ':price' => $basePrice,
                ':effective_date' => $date
            ]);
        }
    }

    $pdo->commit();
    echo "Seed data inserted successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    // Log the error details
    error_log("Seeding Error: " . $e->getMessage());
}
?>
