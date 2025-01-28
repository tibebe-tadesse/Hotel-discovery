class Auth {
    const ROLE_USER = 'user';
    const ROLE_HOTEL_OWNER = 'hotel_owner';
    const ROLE_ADMIN = 'admin';
    
    public static function hasRole($role) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check user role from database
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT 
                CASE 
                    WHEN is_admin = 1 THEN 'admin'
                    WHEN EXISTS (
                        SELECT 1 FROM hotel_owners 
                        WHERE user_id = users.id 
                        AND status = 'approved'
                    ) THEN 'hotel_owner'
                    ELSE 'user'
                END as role
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $userRole = $stmt->fetchColumn();
        
        return $userRole === $role;
    }
} 