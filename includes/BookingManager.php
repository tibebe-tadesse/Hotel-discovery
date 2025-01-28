class BookingManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createBooking($data) {
        // Check room availability
        if (!$this->isRoomAvailable($data['room_id'], $data['check_in'], $data['check_out'])) {
            throw new Exception("Room not available for selected dates");
        }
        
        $this->pdo->beginTransaction();
        
        try {
            // Create booking record
            $stmt = $this->pdo->prepare("
                INSERT INTO bookings (
                    user_id, hotel_id, room_id, check_in, check_out,
                    guests, total_price, status, trx_no
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $data['hotel_id'],
                $data['room_id'],
                $data['check_in'],
                $data['check_out'],
                $data['guests'],
                $data['total_price'],
                'TRX-' . uniqid()
            ]);
            
            // Update room status
            $this->updateRoomStatus($data['room_id'], 'occupied');
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    private function isRoomAvailable($roomId, $checkIn, $checkOut) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE room_id = ?
            AND status IN ('confirmed', 'pending')
            AND (
                (check_in BETWEEN ? AND ?) OR
                (check_out BETWEEN ? AND ?) OR
                (check_in <= ? AND check_out >= ?)
            )
        ");
        
        $stmt->execute([
            $roomId, 
            $checkIn, $checkOut,
            $checkIn, $checkOut,
            $checkIn, $checkOut
        ]);
        
        return $stmt->fetchColumn() === 0;
    }
} 