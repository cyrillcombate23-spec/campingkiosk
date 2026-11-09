
<?php
require_once __DIR__ . "/db.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$sql = "SELECT id, site_name, location, description, image, price
        FROM camping_sites
        WHERE availability = 'Available'
        ORDER BY id ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Database Error: " . e($conn->error));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Camping Kiosk</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f8f5ea;
            font-family: 'Poppins', sans-serif;
            color: #222;
        }

        header {
            background: #174c35;
            color: white;
            text-align: center;
            padding: 25px 15px;
        }

        header h1 {
            margin: 0;
            font-size: 30px;
            font-weight: 700;
        }

        header p {
            margin: 8px 0 0;
        }

        .container {
            width: 94%;
            max-width: 1250px;
            margin: 35px auto;
        }

        .container h2 {
            color: #174c35;
            margin-bottom: 25px;
        }

        .sites {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 24px;
            align-items: stretch;
        }

        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .card img {
            display: block;
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .details {
            padding: 15px 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .details h3 {
            font-size: 19px;
            font-weight: 600;
            margin: 0 0 5px;
            color: #111;
        }

        .details .location {
            color: #555;
            margin: 0 0 8px;
            font-size: 15px;
        }

        .details .description {
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 20px;
        }

        .price {
            font-size: 20px;
            font-weight: 600;
            color: #111;
            margin-top: auto;
            margin-bottom: 20px;
        }

        .reserve-btn {
            display: block;
            width: 100%;
            background: #198754;
            color: white;
            text-decoration: none;
            text-align: center;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
        }

        .reserve-btn:hover {
            background: #146c43;
        }

        .empty-message {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 8px;
        }

        @media (max-width: 600px) {
            .sites {
                grid-template-columns: 1fr;
            }

            header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>Camping Kiosk</h1>
    <p>Explore and book your next camping adventure!</p>
</header>

<div class="container">

    <h2>Available Camping Sites</h2>

    <div class="sites">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($row = $result->fetch_assoc()): ?>

                <?php
                    $id = (int)$row["id"];
                    $name = e($row["site_name"]);
                    $location = e($row["location"]);
                    $description = e($row["description"]);

                    // Get image filename from database
                    $imageFile = trim(str_replace(
                        "\\",
                        "/",
                        $row["image"] ?? ""
                    ));

                    // Remove any existing folder path
                    $imageFile = basename($imageFile);

                    // Correct folder name
                    $image = "camping_images/" . rawurlencode($imageFile);

                    $price = (float)$row["price"];
                ?>

                <div class="card">

                    <img
                        src="<?php echo e($image); ?>"
                        alt="<?php echo $name; ?>"
                        onerror="this.onerror=null; this.alt='Image not found';"
                    >

                    <div class="details">

                        <h3><?php echo $name; ?></h3>

                        <p class="location">
                            <strong>Location:</strong>
                            <?php echo $location; ?>
                        </p>

                        <p class="description">
                            <?php echo $description; ?>
                        </p>

                        <p class="price">
                            ₱<?php echo number_format($price, 2); ?> / night
                        </p>

                        
                        <form action="insert.php" method="GET">
                            <input
                             type="hidden"
                             name="site_id"
                             value="<?php echo $id; ?>"
    >

                            <button type="submit" class="reserve-btn">
                             Reserve Now
                            </button>
                        </form>

                            </button>

                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p class="empty-message">
                No camping sites available.
            </p>

        <?php endif; ?>

    </div>
</div>

</body>
</html>
