
<?php
require_once __DIR__ . "/db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$error = "";
$success = false;
$booking = null;

// GET: Display reservation form
if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $site_id = filter_input(INPUT_GET, "site_id", FILTER_VALIDATE_INT);

    if (!$site_id || $site_id < 1) {
        die("Invalid camping site. <a href='index.php'>Go Back</a>");
    }

    $stmt = $conn->prepare(
        "SELECT id, site_name, location, description, image, price
         FROM camping_sites
         WHERE id = ? AND availability = 'Available'"
    );

    $stmt->bind_param("i", $site_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $site = $result->fetch_assoc();
    $stmt->close();

    if (!$site) {
        die("Camping site not found or unavailable. <a href='index.php'>Go Back</a>");
    }
}

// POST: Save reservation
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $site_id = filter_input(INPUT_POST, "site_id", FILTER_VALIDATE_INT);

    $customer_name = trim($_POST["customer_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $contact_number = trim($_POST["contact_number"] ?? "");
    $check_in = $_POST["check_in"] ?? "";
    $check_out = $_POST["check_out"] ?? "";
    $guests = filter_input(INPUT_POST, "guests", FILTER_VALIDATE_INT);

    $today = date("Y-m-d");

    if (
        !$site_id || $site_id < 1 ||
        $customer_name === "" ||
        strlen($customer_name) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 100 ||
        !preg_match('/^[0-9+\-\s]{7,20}$/', $contact_number) ||
        !$guests || $guests < 1 || $guests > 50 ||
        !$check_in || !$check_out ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_in) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_out)
    ) {
        $error = "Please complete all fields correctly.";
    } else {

        $start = DateTime::createFromFormat('!Y-m-d', $check_in);
        $end = DateTime::createFromFormat('!Y-m-d', $check_out);
        $current = new DateTime($today);

        if (
            !$start || !$end ||
            $start->format('Y-m-d') !== $check_in ||
            $end->format('Y-m-d') !== $check_out ||
            $start < $current ||
            $end <= $start
        ) {
            $error = "Please select valid check-in and check-out dates.";
        } else {

            $stmt = $conn->prepare(
                "SELECT id, site_name, location, price
                 FROM camping_sites
                 WHERE id = ? AND availability = 'Available'"
            );

            $stmt->bind_param("i", $site_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $site = $result->fetch_assoc();
            $stmt->close();

            if (!$site) {
                $error = "Camping site not found or unavailable.";
            } else {

                $total_nights = $start->diff($end)->days;
                $price = (float)$site["price"];
                $total_price = $price * $total_nights;

                $sql = "INSERT INTO reservations
                        (site_id, customer_name, email, contact_number,
                         check_in, check_out, guests, price_per_night,
                         total_nights, total_price)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    die("Database error: " . e($conn->error));
                }

                $stmt->bind_param(
                    "isssssidid",
                    $site_id,
                    $customer_name,
                    $email,
                    $contact_number,
                    $check_in,
                    $check_out,
                    $guests,
                    $price,
                    $total_nights,
                    $total_price
                );

                if ($stmt->execute()) {

                    $booking_id = $stmt->insert_id;

                    $booking = [
                        "id" => $booking_id,
                        "customer_name" => $customer_name,
                        "email" => $email,
                        "contact_number" => $contact_number,
                        "site_name" => $site["site_name"],
                        "check_in" => $check_in,
                        "check_out" => $check_out,
                        "guests" => $guests,
                        "price" => $price,
                        "nights" => $total_nights,
                        "total" => $total_price
                    ];

                    $success = true;

                } else {
                    $error = "Reservation failed: " . $stmt->error;
                }

                $stmt->close();
            }
        }
    }

    // Reload site information if reservation has an error
    if (!$success && $site_id && !isset($site)) {

        $stmt = $conn->prepare(
            "SELECT id, site_name, location, description, image, price
             FROM camping_sites WHERE id = ?"
        );

        $stmt->bind_param("i", $site_id);
        $stmt->execute();

        $site = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$success && empty($site)) {
    die("Camping site not found. <a href='index.php'>Go Back</a>");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Camping Reservation</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 25px 15px;
    background: #f8f5ea;
    font-family: 'Poppins', sans-serif;
    color: #222;
}

.container {
    max-width: 650px;
    margin: 20px auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0,0,0,.08);
}

h1 {
    text-align: center;
    color: #174c35;
    margin-top: 0;
}

.site-info {
    background: #f8f5ea;
    padding: 18px;
    border-radius: 10px;
    margin-bottom: 25px;
}

.site-info h3 {
    color: #174c35;
    margin: 0 0 8px;
}

label {
    display: block;
    font-weight: 600;
    margin: 14px 0 6px;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 7px;
    font-family: inherit;
    font-size: 14px;
}

.submit-btn {
    width: 100%;
    background: #198754;
    color: white;
    border: none;
    padding: 13px;
    border-radius: 7px;
    font-size: 16px;
    font-weight: 600;
    margin-top: 22px;
    cursor: pointer;
}

.submit-btn:hover {
    background: #146c43;
}

.back-btn {
    display: block;
    text-align: center;
    color: #174c35;
    text-decoration: none;
    margin-top: 15px;
}

.error {
    background: #ffe5e5;
    color: #a00000;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 15px;
}

.success {
    text-align: center;
}

.success h1 {
    color: #198754;
}

.receipt {
    background: #f8f5ea;
    padding: 20px;
    border-radius: 10px;
    text-align: left;
    margin: 20px 0;
}

.receipt p {
    margin: 10px 0;
}

.total {
    border-top: 1px solid #ccc;
    padding-top: 15px;
    font-size: 22px;
    font-weight: 700;
    color: #174c35;
}

@media(max-width: 500px) {
    .container {
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="container">

<?php if ($success): ?>

    <div class="success">

        <h1>✓ Reservation Successful!</h1>

        <p>Thank you for booking with Camping Kiosk!</p>

        <div class="receipt">

            <h3>Reservation Receipt</h3>

            <p><strong>Booking ID:</strong> #<?= (int)$booking["id"] ?></p>

            <p><strong>Customer:</strong> <?= e($booking["customer_name"]) ?></p>

            <p><strong>Email:</strong> <?= e($booking["email"]) ?></p>

            <p><strong>Contact:</strong> <?= e($booking["contact_number"]) ?></p>

            <p><strong>Camping Site:</strong> <?= e($booking["site_name"]) ?></p>

            <p><strong>Check-in:</strong> <?= e($booking["check_in"]) ?></p>

            <p><strong>Check-out:</strong> <?= e($booking["check_out"]) ?></p>

            <p><strong>Guests:</strong> <?= (int)$booking["guests"] ?></p>

            <p><strong>Price per night:</strong>
                ₱<?= number_format($booking["price"], 2) ?>
            </p>

            <p><strong>Total nights:</strong>
                <?= (int)$booking["nights"] ?>
            </p>

            <div class="total">
                Total: ₱<?= number_format($booking["total"], 2) ?>
            </div>

        </div>

        <button class="submit-btn" onclick="window.print()">
            Print Receipt
        </button>

        <a href="index.php" class="back-btn">Back to Camping Sites</a>

    </div>

<?php else: ?>

    <h1>Camping Reservation</h1>

    <div class="site-info">
        <h3><?= e($site["site_name"]) ?></h3>

        <p><strong>Location:</strong> <?= e($site["location"]) ?></p>

        <p><?= e($site["description"]) ?></p>

        <p><strong>Price:</strong>
            ₱<?= number_format((float)$site["price"], 2) ?> / night
        </p>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <form action="insert.php" method="POST">

        <input type="hidden" name="site_id"
               value="<?= (int)$site["id"] ?>">

        <label>Customer Name</label>
        <input type="text" name="customer_name"
               maxlength="100" required
               value="<?= e($_POST["customer_name"] ?? "") ?>">

        <label>Email Address</label>
        <input type="email" name="email"
               maxlength="100" required
               value="<?= e($_POST["email"] ?? "") ?>">

        <label>Contact Number</label>
        <input type="tel" name="contact_number"
               maxlength="20" required
               placeholder="09XXXXXXXXX"
               value="<?= e($_POST["contact_number"] ?? "") ?>">

        <label>Check-in Date</label>
        <input type="date" name="check_in"
               min="<?= date('Y-m-d') ?>"
               required
               value="<?= e($_POST["check_in"] ?? "") ?>">

        <label>Check-out Date</label>
        <input type="date" name="check_out"
               min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
               required
               value="<?= e($_POST["check_out"] ?? "") ?>">

        <label>Number of Guests</label>
        <input type="number" name="guests"
               min="1" max="50" value="<?= e($_POST["guests"] ?? "1") ?>"
               required>

        <button type="submit" class="submit-btn">
            Confirm Reservation
        </button>

    </form>

    <a href="index.php" class="back-btn">Back to Camping Sites</a>

<?php endif; ?>

</div>

</body>
</html>
