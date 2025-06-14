<?php
session_start();

// Include database connection
require_once 'db.php';

// Simulate real-time crypto prices (replace with API like CoinGecko in production)
$crypto_prices = [
    'BTC' => 65000.50,
    'ETH' => 3200.75,
    'USDT' => 1.00
];

// Handle signup
if (isset($_POST['signup'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $sql = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
    mysqli_query($conn, $sql);
    $_SESSION['user'] = $username;
    echo "<script>navigate('dashboard');</script>";
}

// Handle login
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user'] = $row['username'];
            echo "<script>navigate('dashboard');</script>";
        }
    }
}

// Handle buy/sell transactions
if (isset($_POST['trade'])) {
    $user = $_SESSION['user'];
    $crypto = mysqli_real_escape_string($conn, $_POST['crypto']);
    $amount = floatval($_POST['amount']);
    $type = $_POST['type'];
    $price = $crypto_prices[$crypto];
    $total = $amount * $price;
    
    $sql = "INSERT INTO transactions (username, crypto, amount, type, price, total) 
            VALUES ('$user', '$crypto', $amount, '$type', $price, $total)";
    mysqli_query($conn, $sql);
    
    // Update wallet
    $sql = "SELECT balance FROM wallets WHERE username='$user' AND crypto='$crypto'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $new_balance = $type == 'buy' ? $row['balance'] + $amount : $row['balance'] - $amount;
        $sql = "UPDATE wallets SET balance=$new_balance WHERE username='$user' AND crypto='$crypto'";
    } else {
        $sql = "INSERT INTO wallets (username, crypto, balance) VALUES ('$user', '$crypto', $amount)";
    }
    mysqli_query($conn, $sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coinbase Clone</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        body {
            background: #f5f6fa;
            color: #1a202c;
        }

        .navbar {
            background: #0b0e11;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .navbar h1 {
            font-size: 1.5rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 1rem;
            font-size: 1rem;
        }

        .nav-links a:hover {
            color: #3b82f6;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .section {
            display: none;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .section.active {
            display: block;
        }

        h2 {
            margin-bottom: 1rem;
            color: #1a202c;
        }

        .crypto-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .crypto-card {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .crypto-card h3 {
            margin-bottom: 0.5rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 400px;
        }

        input, select, button {
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 1rem;
        }

        button {
            background: #3b82f6;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #2563eb;
        }

        .chart-container {
            margin-top: 2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f7fafc;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links a {
                margin: 0 0.5rem;
            }

            .crypto-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Coinbase Clone</h1>
        <div class="nav-links">
            <a href="#" onclick="navigate('home')">Home</a>
            <a href="#" onclick="navigate('signup')">Sign Up</a>
            <a href="#" onclick="navigate('login')">Login</a>
            <a href="#" onclick="navigate('dashboard')">Dashboard</a>
            <a href="#" onclick="navigate('trade')">Trade</a>
            <a href="#" onclick="navigate('portfolio')">Portfolio</a>
        </div>
    </div>

    <div class="container">
        <!-- Home Section -->
        <div id="home" class="section active">
            <h2>Cryptocurrency Prices</h2>
            <div class="crypto-grid">
                <?php foreach ($crypto_prices as $crypto => $price): ?>
                    <div class="crypto-card">
                        <h3><?php echo $crypto; ?></h3>
                        <p>$<?php echo number_format($price, 2); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-container">
                <canvas id="priceChart"></canvas>
            </div>
        </div>

        <!-- Signup Section -->
        <div id="signup" class="section">
            <h2>Sign Up</h2>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="signup">Sign Up</button>
            </form>
        </div>

        <!-- Login Section -->
        <div id="login" class="section">
            <h2>Login</h2>
            <form method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Login</button>
            </form>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="section">
            <h2>Dashboard</h2>
            <?php if (isset($_SESSION['user'])): ?>
                <p>Welcome, <?php echo $_SESSION['user']; ?>!</p>
                <h3>Your Wallet</h3>
                <table>
                    <tr>
                        <th>Cryptocurrency</th>
                        <th>Balance</th>
                    </tr>
                    <?php
                    $user = $_SESSION['user'];
                    $sql = "SELECT * FROM wallets WHERE username='$user'";
                    $result = mysqli_query($conn, $sql);
                    while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['crypto']; ?></td>
                            <td><?php echo number_format($row['balance'], 4); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>Please log in to view your dashboard.</p>
            <?php endif; ?>
        </div>

        <!-- Trade Section -->
        <div id="trade" class="section">
            <h2>Buy/Sell Cryptocurrency</h2>
            <form method="POST">
                <select name="crypto">
                    <?php foreach ($crypto_prices as $crypto => $price): ?>
                        <option value="<?php echo $crypto; ?>"><?php echo $crypto; ?> ($<?php echo number_format($price, 2); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="amount" placeholder="Amount" step="0.0001" required>
                <select name="type">
                    <option value="buy">Buy</option>
                    <option value="sell">Sell</option>
                </select>
                <button type="submit" name="trade">Execute Trade</button>
            </form>
        </div>

        <!-- Portfolio Section -->
        <div id="portfolio" class="section">
            <h2>Portfolio & Transaction History</h2>
            <?php if (isset($_SESSION['user'])): ?>
                <h3>Transaction History</h3>
                <table>
                    <tr>
                        <th>Date</th>
                        <th>Cryptocurrency</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                    <?php
                    $user = $_SESSION['user'];
                    $sql = "SELECT * FROM transactions WHERE username='$user' ORDER BY date DESC";
                    $result = mysqli_query($conn, $sql);
                    while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['date']; ?></td>
                            <td><?php echo $row['crypto']; ?></td>
                            <td><?php echo ucfirst($row['type']); ?></td>
                            <td><?php echo number_format($row['amount'], 4); ?></td>
                            <td>$<?php echo number_format($row['price'], 2); ?></td>
                            <td>$<?php echo number_format($row['total'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>Please log in to view your portfolio.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Navigation function
        function navigate(section) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.getElementById(section).classList.add('active');
        }

        // Chart.js for price chart
        const ctx = document.getElementById('priceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'BTC Price',
                    data: [60000, 62000, 65000, 63000, 64000, 65000],
                    borderColor: '#3b82f6',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</body>
</html>
