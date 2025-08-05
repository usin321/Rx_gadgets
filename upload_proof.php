<?php
session_start();
include 'db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_FILES['proof'])) {
    $orderId = (int) $_POST['order_id'];

    $filename = uniqid() . "_" . basename($_FILES['proof']['name']);
    $targetPath = 'uploads/proofs/' . $filename;

    if (move_uploaded_file($_FILES['proof']['tmp_name'], $targetPath)) {
        $stmt = $conn->prepare("UPDATE orders SET payment_proof = ?, status = 'Paid' WHERE id = ?");
        $stmt->bind_param("si", $filename, $orderId);
        $stmt->execute();
        $stmt->close();
        header("Location: my_orders.php?msg=uploaded");
        exit();
    } else {
        header("Location: my_orders.php?msg=upload_failed");
        exit();
    }
}
?>
