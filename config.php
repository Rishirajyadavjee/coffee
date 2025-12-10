<?php
session_start();

//  $host = 'sql201.infinityfree.com';
//  $dbname = 'if0_40149263_coffee_shop';
//  $username = 'if0_40149263'; // Change as needed
//  $password = 'mmdXl1csk57';     // Change as needed



$host = 'localhost';
$dbname = 'coffee_shop';
$username = 'root'; // Change as needed       
$password = '';     // Change as needed     

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}



class Database
{
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'coffee_shop';
    private $connection;

    public function __construct()
    {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->database};charset=utf8",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

// Helper functions
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function redirect($url)
{
    header("Location: $url");
    exit();
}
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    // Check if user is still active
    checkUserActive();
}

function checkUserActive()
{
    global $pdo;
    
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && !($user['is_active'] ?? 1)) {
            // User has been deactivated, log them out
            session_destroy();
            header('Location: login.php?message=Your account has been deactivated. Please contact support.');
            exit();
        }
    }
}

function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatDate($date)
{
    return date('M d, Y', strtotime($date));
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>


