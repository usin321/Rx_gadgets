<?php
session_start();
include '../db/db.php';

if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit();
}

// Validate required parameters
if (!isset($_GET['id']) || !isset($_GET['action'])) {
  header("Location: orders.php?msg=invalid_action");
  exit();
}

$orderId = intval($_GET['id']);
$action = $_GET['action'];

// Supported actions and their corresponding status values
$statusMap = [
  'paid' => 'Paid',
  'ship' => 'To Ship',
  'receive' => 'To Receive',
  'complete' => 'Completed',
  'cancel' => 'Cancelled'
];

if (!array_key_exists($action, $statusMap)) {
  header("Location: orders.php?msg=invalid_action");
  exit();
}

// Fetch the current order
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
  header("Location: orders.php?msg=invalid_order");
  exit();
}

// Prevent changing from completed/cancelled to anything else
if (in_array($order['status'], ['Completed', 'Cancelled'])) {
  header("Location: orders.php?msg=already_final");
  exit();
}

// Update the order status
$newStatus = $statusMap[$action];
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $orderId);
$stmt->execute();
$stmt->close();

header("Location: orders.php?msg=$action");
exit();
