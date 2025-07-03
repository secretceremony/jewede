<?php
// admin/get_order_items.php

// Ensure session is started if needed (though typically handled by header.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Adjust these paths based on your actual project structure.
// Given your structure, these paths look correct for a file in 'admin/'.
require_once __DIR__ . '/../includes/db_connect.php'; // This should connect to DB and provide $conn
require_once __DIR__ . '/../includes/functions.php';  // This should contain sanitize_input, execute_query, fetch_all

// Set the content type header for JSON response
header('Content-Type: application/json');

// Initialize the response array
$response = ['success' => false, 'items' => [], 'message' => 'An unknown error occurred.']; // Default message

// Check if order_id is provided in the GET request
if (isset($_GET['order_id'])) {
    $order_id = (int)sanitize_input($_GET['order_id']); // Sanitize and cast to integer

    // Prepare the SQL query to fetch order items
    // Using JOIN with 'products' table to get product name and image_url
    $sql = "SELECT oi.quantity, oi.price, p.name, p.image_url
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?";

    // Execute the query using your custom execute_query function
    // Assuming execute_query returns a mysqli_stmt object on success, or false/null on failure
    $stmt = execute_query($sql, 'i', [$order_id]);

    // Check if the query execution was successful
    if ($stmt) {
        // If successful, fetch all results
        $items = fetch_all($stmt);
        $stmt->close(); // Close the statement

        // Check if any items were found
        if ($items) {
            $response['success'] = true;
            $response['items'] = $items;
            $response['message'] = 'Order items fetched successfully.';
        } else {
            // No items found for the given order ID
            $response['message'] = 'No items found for this order.';
        }
    } else {
        // Query execution failed
        $response['message'] = 'Database query failed to fetch order items.';
        
        // Log the actual database error for debugging purposes.
        // The global $conn variable needs to be accessible here for $conn->error.
        // It's good practice to ensure $conn is passed to execute_query or is truly global.
        global $conn; // Declare $conn as global to access it if it's not already in scope

        if (isset($conn) && $conn instanceof mysqli) {
            error_log("SQL Error in get_order_items.php for order_id " . $order_id . ": " . $conn->error);
        } else {
            error_log("SQL Error in get_order_items.php for order_id " . $order_id . ": Database connection object not available or not a mysqli instance.");
        }
    }
} else {
    // order_id parameter is missing from the GET request
    $response['message'] = 'Order ID parameter is missing.';
    http_response_code(400); // Bad Request
}

// Output the JSON response
echo json_encode($response);

// You typically close the database connection at the very end of the script or
// in a shutdown function. If your 'db_connect.php' doesn't keep a persistent
// connection open, it's fine. If it does, ensure $conn is global.
// The placement here means it will close after the JSON is sent.
global $conn;
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}

exit; // Terminate script execution after sending JSON response
?>