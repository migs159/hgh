<?php
// Start session
session_start();

// Set include path
set_include_path(get_include_path() . PATH_SEPARATOR . 'D:/laragon/www/ecommerce2/app/config');

// Include the DatabaseConnect class
require_once 'DatabaseConnect.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'D:/laragon/www/ecommerce2/vendor/autoload.php';
 // Adjust this path to your project's autoloader

// Initialize variables
$name = $email = $password = $confirm_password = '';
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = htmlspecialchars(trim($_POST["email"]));
    $password = htmlspecialchars(trim($_POST["password"]));
    $confirm_password = htmlspecialchars(trim($_POST["confirm_password"]));

    // Validation
    if (empty($name)) $errors['name'] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Valid email is required.";
    if (empty($password) || strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters.";
    if ($password !== $confirm_password) $errors['confirm_password'] = "Passwords do not match.";
    if (!isset($_POST['terms'])) $errors['terms'] = "You must agree to the Terms of Service.";

    // If no errors, proceed
    if (empty($errors)) {
        try {
            $db = new DatabaseConnect();
            $conn = $db->connectDB();

            // Check for duplicate email
            $checkQuery = "SELECT email FROM users WHERE email = :email";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                $errors['email'] = "Email is already registered.";
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);

                if ($stmt->execute()) {
                    $_SESSION['success'] = "Registration successful! You can now log in.";

                    // Send welcome email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = getenv('SMTP_USER'); // Set environment variable
                        $mail->Password = getenv('SMTP_PASS'); // Set environment variable
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        $mail->setFrom('your-email@gmail.com', 'Steppify');
                        $mail->addAddress($email, $name);

                        $mail->isHTML(true);
                        $mail->Subject = 'Welcome to Steppify!';
                        $mail->Body = "<h1>Welcome to Steppify, $name!</h1><p>Thank you for registering. We're excited to have you join us.</p>";

                        $mail->send();
                    } catch (Exception $e) {
                        $errors['mail'] = "Registration successful, but email could not be sent. Error: " . htmlspecialchars($mail->ErrorInfo);
                    }
                } else {
                    $errors['db'] = "Failed to register. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $errors['db'] = "Database connection failed: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Steppify</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plaster&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .logo {
            font-family: 'Plaster', sans-serif;
            background: linear-gradient(45deg, #3490dc, #6574cd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-100">
<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>

<main class="flex-grow container mx-auto py-12 px-6">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-center mb-6">Sign Up</h2>

            <!-- Display success message -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Display errors -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Your Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Your Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Repeat Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="terms" class="form-checkbox">
                        <span class="ml-2 text-sm text-gray-700">I agree to the <a href="/terms-and-condition.php" class="text-blue-500 hover:underline">Terms of Service</a></span>
                    </label>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Register
                    </button>
                    <a href="login.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                        Already have an account?
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>

</body>
</html>
