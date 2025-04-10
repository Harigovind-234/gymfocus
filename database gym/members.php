<?php
session_start();
require_once 'connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Members - Focus Gym</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" href="assets/css/templatemo-training-studio.css">
    
    <style>
        body {
            background-color: #f4f6f9;
        }

        .members-container {
            margin: 100px auto 30px;
            padding: 0 30px;
            max-width: 1400px;
            width: 100%;
        }

        .members-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
            width: 100%;
        }

        .members-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ed563b;
        }

        .members-title {
            color: #232d39;
            font-size: 24px;
            font-weight: 600;
        }

        .members-count {
            background: #ed563b;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
        }

        .members-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .members-table th {
            background-color: #232d39;
            color: white;
            padding: 15px;
            text-transform: uppercase;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .members-table tr {
            transition: all 0.3s ease;
        }

        .members-table td {
            padding: 15px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
        }

        .members-table tr:hover td {
            background-color: #f2f2f2;
            transform: scale(1.01);
        }

        .members-table td:first-child {
            border-left: 1px solid #dee2e6;
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .members-table td:last-child {
            border-right: 1px solid #dee2e6;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .view-btn {
            background-color: #ed563b;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .view-btn:hover {
            background-color: #f9735b;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-btn {
            background-color: #232d39;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background-color: #ed563b;
        }

        .header-area {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #232d39 !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .header-area .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .header-area .main-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .header-area .logo {
            color: #ed563b;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 0.5px;
        }

        .header-area .logo em {
            color: #fff;
            font-style: normal;
            font-weight: 300;
        }

        .header-area .nav {
            display: flex;
            align-items: center;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .header-area .nav li {
            margin-left: 25px;
        }

        .header-area .nav li a {
            text-decoration: none;
            text-transform: uppercase;
            font-size: 13px;
            font-weight: 500;
            color: #fff;
            transition: color 0.3s ease;
        }

        .header-area .nav li a:hover,
        .header-area .nav li a.active {
            color: #ed563b;
        }

        .header-area .nav .main-button a {
            display: inline-block;
            background-color: #ed563b;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .header-area .nav .main-button a:hover {
            background-color: #f9735b;
        }

        .member-name {
            font-weight: 500;
        }

        .search-input {
            font-size: 16px;
            padding: 10px 15px;
        }

        #noResults td {
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        /* Highlight matching results */
        .member-row:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s ease;
        }
        .header-area .nav .main-button {
          margin-left: 20px;
          display: flex;
          align-items: center;
        }

        .header-area .nav .main-button a {
          background-color: #ed563b;
          color: #fff !important;
          padding: 15px 30px !important;
          border-radius: 5px;
          font-weight: 600;
          font-size: 14px !important;
          text-transform: uppercase;
          transition: all 0.3s ease;
          display: inline-block;
          letter-spacing: 0.5px;
          line-height: 1.4;
          white-space: nowrap;
        }

        .header-area .nav .main-button a:hover {
          background-color: #f9735b;
          color: #fff !important;
          transform: translateY(-2px);
          box-shadow: 0 4px 15px rgba(237, 86, 59, 0.2);
        }

        /* Fix for mobile responsiveness */
        @media (max-width: 991px) {
          .header-area .nav .main-button a {
            padding: 12px 25px !important;
            font-size: 13px !important;
          }
        }

        @media (max-width: 1200px) {
            .members-container {
                padding: 0 20px;
            }
        }

        @media (max-width: 768px) {
            .members-container {
                padding: 0 15px;
                margin-top: 90px;
            }
            
            .members-card {
                padding: 15px;
            }
        }

    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-area header-sticky">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <nav class="main-nav">
                        <a href="admin.php" class="logo">Admin <em>Panel</em></a>
                        <ul class="nav">
                        <li><a href="admin.php">Home</a></li>
                    <li><a href="members.php">Members</a></li>
                    <li><a href="staff_management.php">Staff</a></li>
                    <li><a href="Payments_check.php">Payments</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li class="main-button"><a href="login2.php">Logout</a></li>                       
                 </ul>
                    </nav>
                </div>
            </div>
            
        </div>
    </header>

    <div class="members-container">
        <div class="members-card">
            <div class="members-header">
                <h2 class="members-title">Gym Members</h2>
                <?php
                $count_query = "SELECT COUNT(*) as count FROM login WHERE role = 'Member'";
                $count_result = mysqli_query($conn, $count_query);
                $count = mysqli_fetch_assoc($count_result)['count'];
                ?>
                <span class="members-count">Total Members: <?php echo $count; ?></span>
            </div>

            <div class="search-box">
                <input type="text" id="liveSearch" class="search-input" placeholder="Type first letter to search names...">
            </div>

            <table class="members-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Join Date</th>
                        <th>Mobile</th>
                        <th>Action</th>
                        
                    </tr>
                </thead>
                <tbody id="searchResults">
                    <?php
                    // Initial load of members
                    $initial_sql = "SELECT register.*, login.email 
                                  FROM register 
                                  INNER JOIN login ON register.user_id = login.user_id 
                                  WHERE login.role = 'Member' 
                                  ORDER BY register.full_name ASC"; // Changed to order by name
                    $initial_result = mysqli_query($conn, $initial_sql);
                    
                    if (mysqli_num_rows($initial_result) > 0) {
                        while ($row = mysqli_fetch_assoc($initial_result)) {
                            echo "<tr class='member-row'>";
                            echo "<td class='member-name'>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                            echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                            echo "<td>" . htmlspecialchars($row['mobile_no']) . "</td>";
                            echo "<td>";
                            echo "<a href='admin-profile-details.php?id=" . $row['user_id'] . "' class='view-btn'>View</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center;'>No members found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.0.min.js"></script>
    <script src="assets/js/popper.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
    document.getElementById('liveSearch').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const rows = document.getElementsByClassName('member-row');
        let found = false;

        for (let row of rows) {
            const name = row.querySelector('.member-name').textContent.toLowerCase();
            
            // Check if name starts with the search text
            if (name.startsWith(searchText)) {
                row.style.display = '';
                found = true;
            } else {
                row.style.display = 'none';
            }
        }

        // Show "No results found" message
        const noResultsRow = document.getElementById('noResults');
        if (!found && searchText !== '') {
            if (!noResultsRow) {
                const tbody = document.getElementById('searchResults');
                const newRow = document.createElement('tr');
                newRow.id = 'noResults';
                newRow.innerHTML = '<td colspan="6" style="text-align: center;">No names found starting with "' + searchText + '"</td>';
                tbody.appendChild(newRow);
            } else {
                noResultsRow.style.display = '';
                noResultsRow.querySelector('td').textContent = 'No names found starting with "' + searchText + '"';
            }
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    });

    // Clear search when input is empty
    document.getElementById('liveSearch').addEventListener('input', function() {
        if (this.value === '') {
            const rows = document.getElementsByClassName('member-row');
            for (let row of rows) {
                row.style.display = '';
            }
            const noResultsRow = document.getElementById('noResults');
            if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        }
    });
    </script>
</body>
</html> 