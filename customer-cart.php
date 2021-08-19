<?php
session_start();
require 'admin\\adminFunction.php';

if (!isset($_SESSION['login'])) {
    header("location: login.php");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- jquery cdn-->
    <script src="https://code.jquery.com/jquery-3.4.1.js"></script>

    <!-- import fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Tangerine">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <!-- Bootstrap-->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <!-- font awesome-->
    <link rel="stylesheet" href="vendor/fontawesome/css/all.css">
    <!-- swiper plugin-->
    <script src="vendor/swiper/swiper.min.js"></script>
    <link rel="stylesheet" href="vendor/swiper/swiper.min.css">
    <!--customer stylesheet-->
    <link rel="stylesheet" href="css/general.css">
    <link rel="stylesheet" href="css/product.css">
    <link rel="stylesheet" href="css/cart.css">
    <!-- customer javascript-->
    <script src="js/showMenuOnScroll.js"></script>
    <script src="js/toggleMenu.js"></script>
    <script src="js/DropMenu.js"></script>
    <script src="js/ScrollToTop.js"></script>
    <!-- <script src="js/customer-cart.js" async></script> -->
    
    <title>Cart</title>
</head>

<body>
    <div id="page">
        <div id="head">
            <div id="nav">
                <!-- for responsive menu-->
                <button class="hamburger">
                    <span></span>
                </button>

                <div id="menu">
                    <a href="index.php">Home</a>
                    <div class="dropdown-item">
                        <a href="#" id="drop" class="on"> Products
                            <span class='cheveron'></span>
                        </a>
                        <div class="subitem">
                            <?php
                            $conn = connect();
                            $prd = $conn->query("SELECT * FROM category WHERE status = 1");
                            while ($row = $prd->fetch_assoc()) {
                                echo "
                                    <a href='product.php?id={$row['categoryID']}#prd'>{$row['categoryName']}</a>
                                ";
                            }
                            $conn->close();
                            ?>
                        </div>
                    </div>
                    <a href="contact.php">Contact Us</a>
                    <a href="gallery.php">Gallery</a>
                    <?php if (isset($_SESSION['login']) && $_SESSION['login'] === true) { ?>
                        <a href='useraccount.php'><i class="fas fa-user"></i>Account</a>
                        <a href="logout.php"><i class="fas fa-power-off"></i>LogOut</a>
                    <?php } else { ?>
                        <a href="login.php#page-title">Login</a>
                    <?php } ?>
                </div>

                <div id="search-box">
                    <input type="text" name="search-text" placeholder="Type to search">
                    <a href="#" id="search-btn">
                        <i class="fas fa-search"></i>
                    </a>
                </div>
                <div id="logo">
                    <a href="index.php#about"><img src="images/logo.png" alt="logo"></a>
                </div>
            </div><!-- end nav-->


            <!-- 
            <div id="banner">
                <p class="welcome">Welcome to Star Organic farm</p>
                <img src="images/logo.png" alt="star-logo" id ="logo-img">
                <h1 class="intro">Lorem ipsum dolor sit amet consectetur</h1>
                <p class="slogan">Lorem ipsum dolor sit amet</p>
            </div> end banner -->

        </div>
        <!--end head div-->

        <div id="content">
            <!-- <div id="general-title">
                <h2>Cart <i class="fas fa-shopping-cart"></i></h2>
                <div id="page-location">
                    <a href="customer-cart.html">Cart</a>
                </div>
            </div> -->

            <div id="cart" class="container">
                <h2 class="cart-header">Cart <i class="fas fa-shopping-cart"></i></h2>
                <h4 style='text-align:center'>Please take a few seconds to review your cart before checkout!</h4>
                <span class="separator"></span>
                <!-- cart product-->
                <table class="table table-condensed" id="cart-product">
                    <thead>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </thead>
                    <tbody class="cart-items">
                        <?php
                        if (isset($_SESSION['customerCart'])) {
                            foreach ($_SESSION['customerCart'] as $item) : ?>
                                <tr class='cart-row'>
                                    <td class='item-img'>
                                        <img style="height: 60px;" src='<?= admin_findImg($item['id']) ?>' alt=''>
                                        <span class='item-name'><?= $item['name'] ?></span>
                                    </td>
                                    <td>
                                        <input type="hidden" class='price' value="<?= $item['price'] ?>">
                                        $<span class='item-price'><?= $item['price'] ?></span>
                                    </td>
                                    <td>
                                        <input style='max-width:50px' name='itemQuantity' type='number' min='1' step='1' value='<?= $item['qtt'] ?>' class='quantity-input' data-id='<?= $item['id'] ?>'>
                                    </td>
                                    <td>
                                        <input type='hidden' value='<?= $item['subtotal'] ?>' class='subtotal'>
                                        $<span class='visible-subtotal'><?= $item['subtotal'] ?></span>
                                    </td>
                                    <td>
                                        <button data-id='<?= $item['id'] ?>' class='remove btn btn-danger'>Remove</button>
                                    </td>
                                </tr>
                        <?php endforeach;
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td class="toltal-price" colspan="4">TOTAL: $<span id='sum'><?= isset($_SESSION['totalCart']) ? number_format($_SESSION['totalCart'], 2) : '' ?></span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <div style="text-align:center">
                    <a href="product.php#prd" style="width:120px" class="btn btn-primary">Keep shopping</a>
                    <a id='checkout' href="purchase.php" style="width:120px" class="btn btn-danger">Checkout</a>
                </div>
            </div><!-- /cart container-->
        </div>
        <!--end content div-->

        <div id="foot">
            <div id="copyrights">
                Copyright & copy <?= date("Y") ?>. All rights reserved by Brothers In Farm
            </div>
            <div id="social-media">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        <!--end foot div-->
    </div><!-- end page div-->
    <!-- <a href="#" class="UpToTop"><i class="fas fa-arrow-up"></i></a> -->

    <script>
        var swiper = new Swiper('.swiper-container', {
            spaceBetween: 30,
            centeredSlides: true,
            autoplay: {
                delay: 2500,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    </script>
    <script src="js/addtocart.js"></script>
</body>

</html>