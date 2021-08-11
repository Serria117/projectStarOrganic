<?php
session_start();
require "admin//adminFunction.php";

if(isset($_SESSION['cart'])){
    $conn = connect();
    //Create order:
    if (isset($_POST['submit'])) {
        $dAdd = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
        $phone = $_POST['phone'];
        $orderValue = $_SESSION['total'];
        $cid = $_SESSION['cid'];
        $sql = "INSERT INTO orders (customerID, dAdd, phone, orderValue) VALUES (?,?,?,?)";
        $stm = $conn->prepare($sql);
        $stm->bind_param("sssd", $cid, $dAdd, $phone, $orderValue);
        $stm->execute();
        $oid = $conn->insert_id;
        echo "orderID = $oid<br>";
        //create order detail:
        $count = $_SESSION['count'];
        foreach ($_SESSION['cart'] as $value) {
            $sql = "INSERT INTO orderdetail (orderID, productID, orderDetailPrice, quantity)
            VALUES ('$oid', '{$value['id']}', '{$value['price']}','{$value['qtt']}')";
            $conn->query($sql);
        }
        
        $_SESSION['notification'] = "Dear {$_SESSION['name']}, Thank you for choosing our products. Our staff will contact you within 24 hours for confirmation and start processing your order. Best regard!";

        header ("location: index.php");
    }
}
