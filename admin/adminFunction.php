<?php
include 'database.php';
//admin login function
function adminLogin($userName, $password)
{
    $login = [];
    $conn = connect();
    $sql = "SELECT * FROM staff WHERE userName = ?";
    $stm = $conn->prepare($sql);
    $stm->bind_param("s", $userName);
    $stm->execute();
    $result = $stm->get_result();
    $stm->close();
    //check if username is valid:
    if ($result->num_rows === 1) {
        while ($rows = $result->fetch_assoc()) {
            //if username is valid, then check the password:
            if (password_verify($password, $rows['password']) === TRUE) {
                $login['userID'] = $rows['staffID'];
                $login['userName'] = $rows['userName'];
                $login['userRole'] = $rows['roleID'];
                $login['userEmail'] = $rows['email'];
            } else {
                $login['error'] = "Invalid username or password";
            }
            //if the password is correct, check the user status:
            if ($rows['status'] === 0) {
                $login['error'] = "Your account has been suspended by the administrator.";
            }
        }
    } else {
        $login['error'] = "Invalid username or password";
    }
    return $login;
}

function admin_AddProduct($name, $price, $detail, $category, $imgURL)
{
    $conn = connect();
    //find catergoryID:
    $sql = "SELECT categoryID FROM category WHERE categoryName = '$category'";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $categoryID = $row['categoryID'];
    }
    //Check if product name exist:
    $checkPName = $conn->query("SELECT productName FROM product WHERE productName = '$name'");
    if ($checkPName->num_rows > 0) {
        $error = "The product name has already existed in the database.";
        return $error;
    } else {
        //insert product:
        // $sql = "CALL addProduct('$name', '$price', '$detail', '$categoryID', '$imgURL')";
        $sql = "INSERT INTO product (productName, unitPrice, productDetail, categoryID, imgURL) VALUES (?,?,?,?,?)";
        $stm = $conn->prepare($sql);
        $stm->bind_param("sssis", $name, $price, $detail, $categoryID, $imgURL);
        if($stm->execute()){
            return TRUE;
        } else {
            $error = "Unable to add product due to database error.";
            return $error;
        }

    }
    $conn->close();
}

function admin_findImg($id)
{
    $conn = connect();
    $result = $conn->query("SELECT imgURL FROM product WHERE productID = '$id'");
    if ($result->num_rows > 0) {
        while ($r = $result->fetch_assoc()) {
            $img = $r['imgURL'];
        }
        return $img;
    } else return false;
    $conn->close();
}

function admin_removeProduct($pid)
{
    $conn = connect();
    $sql = "DELETE FROM product WHERE productID = '$pid'";
    $conn->query($sql);
    $conn->close();
}

function admin_displayProduct($search, $order)
{
    $conn = connect();
    $sql = "SELECT pd.imgURL, pd.productID, pd.productName, ct.categoryName, pd.productDetail, pd.unitPrice, pd.status, ct.unit
    FROM product as pd 
    INNER JOIN category as ct ON pd.categoryID = ct.categoryID 
    WHERE pd.productName LIKE CONCAT('%', '$search', '%') OR ct.categoryName LIKE CONCAT('%', '$search', '%')
    {$order}
    ";
    $list = $conn->query($sql);
    if ($list->num_rows > 0) {
        echo "<table style='table-layout:fixed' class='tbl table table-striped table-hover'>
                <tr class='head'>
                    <th style='width:15%'>Product Name</th>
                    <th>Category</th>
                    <th style='width:40%;'>Product detail</th>
                    <th>Unit price</th>
                    <th>Status</th>
                    <th style='width:15%'>Manage</th>
                </tr>
            ";
        while ($item = $list->fetch_assoc()) { ?>
            <tr>
                <td>
                    <p><img src="..\\<?= $item['imgURL'] ?>" alt="image" style="width:50%; height:50%"></p>
                    <p><b><?= $item['productName'] ?></b></p>
                </td>
                <td style=''><?= $item['categoryName'] ?></td>
                <td style='text-align:justify; padding-left:20px;font-size:16px'><?= $item['productDetail'] ?></td>
                <td style='font-size:15px'>$<?= $item['unitPrice'] ?>/<?= $item['unit'] ?></td>
                <td><?php if ($item['status'] == 1) {
                echo "Sale";
                    } else {
                        echo "Discontinued";
                    } ?></td>
                <td class="edit"><button class="item-list btn btn-success edit-product" data-bs-toggle="modal" data-id="<?= $item['productID'] ?>" data-bs-target="#editPanel">Update</button></td>
            </tr>

<?php   }
        echo "</table>";
    } else {
        echo "<table class='table'><tr><td><b>Product not found.</b></td></tr></table>";
    }
}

function admin_updateProduct($pid, $pname, $price, $category, $detail, $imgURL, $status)
{
    $conn = connect();
    $error = [];
    //find category ID:
    $category = $conn->real_escape_string($category);
    $find_cid = $conn->query("SELECT categoryID FROM category WHERE categoryName = '$category'");
    while ($row = $find_cid->fetch_assoc()) {
        $cid = $row['categoryID'];
    }

    //validate name:
    if (empty($pname)) {
        $error['name'] = "You must enter a product name.";
    } else {
        $sql_checkName = "SELECT productName FROM product WHERE productName = ? AND productID != ?";
        $stm = $conn->prepare($sql_checkName);
        $stm->bind_param("si", $pname, $pid);
        $stm->execute();
        $result = $stm->get_result();
        $stm->close();
        if ($result->num_rows > 0) {
            $error['name'] = 'The product name you entered has already existed in the database.';
        }
    }
    //validate price:
    if (empty($price)) {
        $error['price'] = 'You must enter unit price.';
    } elseif (preg_match('/^[+]?[0-9]*\.?[0-9]+$/', $price) == 0) {
        $error['price'] = 'Unit price must be positive decimal number.';
    }
    //validate detail:
    if (empty($detail)) {
        $error['detail'] = 'You must enter the product detail.';
    }

    //validation result:
    if (count($error) > 0) {
        return $error;
    } else {
        //if no image change:
        if (empty($imgURL)) {
            $sql = "UPDATE product SET productName = ?, unitPrice = ?, categoryID = ?, productDetail = ?, `status` = ? WHERE productID = ?";
            $stm = $conn->prepare($sql);
            $stm->bind_param("sdisii", $pname, $price, $cid, $detail, $status, $pid);
            $stm->execute();
            $stm->close();
        } else { //if change image:
            //unlink old image before insert new URL:
            $img = $conn->query("SELECT imgURL FROM product WHERE productID = '$pid'");
            while ($row = $img->fetch_assoc()) {
                $oldimgURL = $row['imgURL'];
            }
            unlink('../' . $oldimgURL);

            //update record:
            $sql = "UPDATE product SET productName = ?, unitPrice = ?, categoryID = ?, productDetail = ?, imgURL = ?, `status` = ? WHERE productID = ?";
            $stm = $conn->prepare($sql);
            $stm->bind_param("sdissii", $pname, $price, $cid, $detail, $imgURL, $status, $pid);
            $stm->execute();
            $stm->close();
        }
        return true;
    }
    $conn->close();
}

function admin_displayUser($search)
{
    $conn = connect();
    $sql = "SELECT s.staffID, s.userName, s.status, sr.roleName, sr.roleDetail, s.email
    FROM staff as s INNER JOIN staffrole as sr ON s.roleID = sr.roleID 
    WHERE userName LIKE CONCAT('%','$search','%') OR roleName LIKE CONCAT('%','$search','%')
    ORDER BY staffID";
    $list = $conn->query($sql);
    $list = $conn->query($sql);
    if ($list->num_rows > 0) {
        echo "<table class='tbl table table-striped table-hover'>
                <tr class='head'>
                    <th>ID</th>
                    <th>User Name</th>
                    <th>Account Status</th>
                    <th>Account Type</th>
                    <th>Email</th>
                    <th>Manage</th>
                </tr>
            ";
        while ($item = $list->fetch_assoc()) { ?>
            <tr>
                <td><?= $item['staffID'] ?></td>
                <td><?= $item['userName'] ?></td>
                <td><?= $item['status'] == 1 ? 'Active' : 'Suspended' ?></td>
                <td><?= $item['roleName'] ?></td>
                <td><?= $item['email'] ?></td>
                <td class="edit"><button class="item-list btn btn-success edit-user" data-bs-toggle="modal" data-id="<?= $item['staffID'] ?>" data-bs-target="#editPanel">Edit</button></td>
            </tr>

        <?php   }
        echo "</table>";
    } else {
        echo "<table class='table'><tr><td><b>Product not found.</b></td></tr></table>";
    }
    $conn->close();
}

function admin_addUser($uname, $email, $pass, $repass, $role)
{
    $conn = connect();
    $error = [];
    $result = '';
    //validate input data:
    //name:
    $uname = trim($uname);
    if (strlen($uname) < 2) { //first, a name must equal or longer than 2 characters.
        $error['name'] = 'User name must contain atleast 2 characters.';
    } elseif (preg_match('/^[A-Za-z0-9_-]*$/', $uname) === 0) {
        $error['name'] = 'User name can only contain alphanumeric and "-" and "_" characters.';
    } else {
        $uname = $conn->real_escape_string($uname);
        $checkName = $conn->query("SELECT * FROM staff WHERE userName = '$uname'");
        if ($checkName->num_rows > 0) {
            $error['name'] = 'The username you enter has already been taken.';
        }
    }
    //email:
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'The email address you enter is not valid.';
    } else {
        $checkMail = $conn->query("SELECT * FROM staff WHERE email = '$email'");
        if ($checkMail->num_rows > 0) {
            $error['mail'] = 'The email you entered has already been used.';
        }
    }
    //pass:
    if (strlen($pass) < 6) {
        $error['pass'] = 'Password must contain atleast 6 characters.';
    }
    if (strcmp($pass, $repass) != 0) {
        $error['pass'] = 'The second password you re-entered didn\'t match the first one.';
    }
    //role
    switch ($role) {
        case 'admin':
            $roleID = 1;
            break;
        case 'sale':
            $roleID = 2;
            break;
        default:
            $error['role'] = "You must select an account type.";
    }

    //after validate:
    if (count($error) == 0) {
        $pass = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO staff (userName, email, password, roleID) VALUES (?, ?, ?, ?)";
        $stm = $conn->prepare($sql);
        $stm->bind_param("sssi", $uname, $email, $pass, $roleID);
        if ($stm->execute()) {
            $result = TRUE;
        } else {
            $result = FALSE;
        }
        return $result;
    } else {
        return $error;
    }
    $conn->close();
}

function admin_updateUser($uid, $uname, $email, $pass, $repass, $role, $status)
{
    $conn = connect();
    $error = [];
    $result = '';
    //validate input data:
    //name:
    $uname = trim($uname);
    if (strlen($uname) < 2) { //first, a name must equal or longer than 2 characters.
        $error['name'] = 'User name must contain atleast 2 characters.';
    } elseif (preg_match('/^[A-Za-z0-9_-]*$/', $uname) === 0) {
        $error['name'] = 'User name can only contain alphanumeric and "-" and "_" characters.';
    } else {
        $uname = $conn->real_escape_string($uname);
        $checkName = $conn->query("SELECT * FROM staff WHERE userName = '$uname' AND staffID != '$uid'");
        if ($checkName->num_rows > 0) {
            $error['name'] = 'The username you enter has already been taken.';
        }
    }
    //email:
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['email'] = 'The email address you enter is not valid.';
    } else {
        $checkMail = $conn->query("SELECT * FROM staff WHERE email = '$email' AND staffID != '$uid'");
        if ($checkMail->num_rows > 0) {
            $error['mail'] = 'The email you entered has already been used.';
        }
    }
    //pass:
    if (!empty($pass) && strlen($pass) < 6) {
        $error['pass'] = 'Password must contain atleast 6 characters.';
    }
    if (!empty($pass) && !empty($repass) && strcmp($pass, $repass) != 0) {
        $error['pass'] = 'The second password you re-entered didn\'t match the first one.';
    }
    //role
    switch ($role) {
        case 'admin':
            $roleID = 1;
            break;
        case 'sale':
            $roleID = 2;
            break;
        default:
            $error['role'] = "You must select an account type.";
    }
    //Status:
    switch ($status) {
        case 'Active':
            $statusID = 1;
            break;
        case 'Suspend':
            $statusID = 0;
            break;
        default:
            $statusID = 1;
    }
    //after validation:
    if (count($error) === 0) {
        if (!empty($pass)) {
            $pass = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "UPDATE staff SET userName = ?, email = ?, password = ?, roleID = ?, status = ? WHERE staffID = ?";
            $stm = $conn->prepare($sql);
            $stm->bind_param("sssiii", $uname, $email, $pass, $roleID, $statusID, $uid);
            $stm->execute();
            $query = $stm->get_result();
            $stm->close();
        } else {
            $sql = "UPDATE staff SET userName = ?, email = ?, roleID = ?, status = ? WHERE staffID = ?";
            $stm = $conn->prepare($sql);
            $stm->bind_param("ssiii", $uname, $email, $roleID, $statusID, $uid);
            $stm->execute();
            $query = $stm->get_result();
            $stm->close();
        }

        return TRUE;
    } else {
        return $error;
    }
    $conn->close();
}

function admin_displayCategory()
{
    $conn = connect();
    $sql = "SELECT c.categoryID, c.categoryName, c.categoryDetail, c.unit, COUNT(p.categoryID) as 'Total product'
        FROM category as c LEFT JOIN product as p ON c.categoryID = p.categoryID
        GROUP BY c.categoryID
    ";
    $result = $conn->query($sql);
    echo "<table style='table-layout:fixed' class='tbl table table-striped table-hover'>
                <tr class='head'>
                    <th style='width:20px'>ID</th>
                    <th style='width:15%'>Category</th>
                    <th style='width:50%'>Description</th>
                    <th>Total product</th>
                    <th style='width:7%'>Unit</th>
                    <th>Manage</th>
                </tr>
            ";
    while ($cat = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$cat['categoryID']}</td>
                <td>{$cat['categoryName']}</td>
                <td style='text-align:justify'>{$cat['categoryDetail']}</td>
                <td>{$cat['Total product']}</td>
                <td>{$cat['unit']}</td>
                <td><button class='btn btn-success edit-ctg' data-bs-toggle='modal' data-bs-target='#editCtg' data-id='{$cat['categoryID']}' style='width:100px'>Edit</button></td>
            </tr>";
    }
    echo "</table>";
    $conn->close();
}

function admin_getCategoryName($id)
{
    $conn = connect();
    $result = $conn->query("SELECT categoryName FROM category WHERE categoryID = '$id'");
    if ($result->num_rows > 0) {
        foreach ($result as $value) {
            $name = $value['categoryName'];
        }
        return $name;
    }
}

function admin_addCategory($cname, $cunit, $detail)
{
    $conn = connect();
    $error = [];
    $sql = "INSERT INTO category (categoryName, unit, categoryDetail) VALUES (?, ?, ?)";
    //Validate name:
    if (empty($cname)) {
        $error['name'] = 'You must enter a name for the new category';
    }
    if (!preg_match('/^[a-zA-Z0-9-_]*$/', $cname)) {
        $error['name'] = 'Name must contain only alphanumeric character.';
    }
    $check_sql = "SELECT * FROM category WHERE categoryName = ?";
    $stm = $conn->prepare($check_sql);
    $stm->bind_param("s", $cname);
    $stm->execute();
    $checkName = $stm->get_result();
    if ($checkName->num_rows > 0) {
        $error['name'] = "The category name has already existed.";
    }
    //validate unit:
    if (empty($cunit)) {
        $error['unit'] = 'You must enter an unit count.';
    }
    if (!preg_match('/^[a-zA-Z0-9-_]*$/', $cunit)) {
        $error['unit'] = 'unit must contain only alphanumeric character.';
    }
    if (empty($detail)) {
        $error['detail'] = 'You must enter some description for the new category';
    }
    if (count($error) > 0) {
        return $error;
    } else {
        $stm = $conn->prepare($sql);
        $stm->bind_param("sss", $cname, $cunit, $detail);
        if ($stm->execute()) {
            return true;
        } else {
            $error['database'] = 'Could not append data into the database, try again.';
            return $error;
        }
    }
    $conn->close();
}

function admin_updateCategory($id, $name, $detail, $unit)
{
    $conn = connect();
    $error = [];
    if (empty($name)) {
        $error['name'] = "You must enter a name for the category";
    }
    $name = $conn->real_escape_string($name);
    $findName = $conn->query("SELECT * FROM category WHERE categoryName = '$name' AND categoryID != '$id'");
    if ($findName->num_rows > 0) {
        $error['name'] = "The category name you've just entered has already existed.";
    }
    if (!preg_match('/^[a-zA-Z0-9-_ ]*$/', $name)) {
        $error['name'] = "Name must contain only alphanumeric characters.";
    }
    if (empty($detail)) {
        $error['detail'] = "You must write something for the category detail.";
    }
    if (empty($unit)) {
        $error['unit'] = "You must enter the unit count for the product category.";
    }
    if (count($error) > 0) {
        return $error;
    } else {
        $sql = "UPDATE category SET categoryName = ?, categoryDetail = ?, unit = ? WHERE categoryID = ?";
        $stm = $conn->prepare($sql);
        $stm->bind_param("sssi", $name, $detail, $unit, $id);
        if ($stm->execute()) {
            return TRUE;
        } else {
            $error['db'] = "Unable to update the database.";
            return $error;
        }
    }

    $conn->close();
}

function totalCustomer()
{
    $conn = connect();
    $sql = "SELECT * FROM customers";
    $result = $conn->query($sql);
    $count = $result->num_rows;
    return $count;
    $conn->close();
}

function admin_DisplayCustomer()
{
    $conn = connect();
    $sql = "SELECT * FROM customers";
    $result = $conn->query($sql);
    echo "<table class='tbl table table-striped table-hover'>
                <tr class='head'>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Join Date</th>
                </tr>
    ";
    while ($row = $result->fetch_assoc()) {
        $date = date('d/m/Y H:m', strtotime($row['joinDate']));
        echo "<tr>
                  <td>{$row['customerID']}</td>
                  <td>{$row['customerName']}</td>
                  <td>{$row['customerEmail']}</td>
                  <td>{$row['customerPhone']}</td>
                  <td>{$row['customerID']}</td>
                  <td>{$date}</td>
            </tr>   
        ";
    }
    echo "</table>";
    $conn->close();
}

function admin_contact()
{
    $conn = connect();
    $sql = "SELECT * FROM contact_us";
    $result = $conn->query($sql);
    $html = '';
    if ($result->num_rows >= 0) {
        $html .= "<table class='tbl table table-striped table-hover'>
                <tr class='head'>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Respond</th>
                </tr>
        ";
        foreach ($result as $value) {
            $html .= "
            <tr>
                <td>{$value['first_name']} {$value['last_name']}</td>
                <td>{$value['email']}</td>
                <td>{$value['phone']}</td>
                <td>{$value['message']}</td>
                <td>{$value['datetime']}</td>
                <td><a class='btn btn-primary' href='mailto:{$value['email']}'>Respond</a></td>
            </tr>
            ";
        }
        $html .= "</table>";
    }
    $conn->close();
    echo $html;
}

function admin_countOrder($date)
{
    $conn = connect();
    $orderCount = 0;
    // $sql = "SELECT * FROM orders WHERE orderTime BETWEEN CONCAT('$date',' 00:00:00') AND CONCAT('$date',' 23:59:59')";
    $sql = "SELECT * FROM orders WHERE week(orderTime) = week(now())";
    $result = $conn->query($sql);
    if ($result->num_rows >= 0) {
        $orderCount = $result->num_rows;
    }
    return $orderCount;
    $conn->close();
}

function admin_saleValue($date)
{
    $conn = connect();
    $saleValue = 0;
    $sql = "SELECT SUM(orderValue) as 'sum' FROM orders WHERE orderStatus = 'success'";
    if (!empty($date)) {
        $sql = "SELECT SUM(orderValue) as 'sum' FROM orders WHERE orderStatus = 'success' AND orderTime BETWEEN CONCAT('$date',' 00:00:00') AND CONCAT('$date',' 23:59:59')";
    }
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $saleValue = $row['sum'];
    }
    return $saleValue;
    $conn->close();
}

function admin_displayOrder()
{
    $conn = connect();
    $sql = "SELECT * FROM orders as o
        INNER JOIN customers as c ON o.customerID = c.customerID
        LEFT JOIN staff as s on o.staffID = s.staffID
        LEFT JOIN orderdetail as od ON o.orderID = od.orderID
        INNER JOIN product as p ON od.productID = p.productID
        GROUP BY o.orderID
    ";
    $result = $conn->query($sql);
    if ($result->num_rows >= 0) {
        echo "<table class='tbl table table-striped table-hover'>
                <tr class='head'>
                    <th>ID</th>
                    <th>Date Time</th>
                    <th>Customer</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th>Staff assigned</th>
                    <th>Details</th>
                </tr>
            ";
        while ($order = $result->fetch_assoc()) {
            $date = date("d/m/Y H:i:s", strtotime($order['orderTime']));
            echo "
                <tr>
                    <td>{$order['orderID']}</td>
                    <td>{$date}</td>
                    <td>{$order['customerName']}</td>
                    <td>{$order['orderValue']}</td>
                    <td>{$order['orderStatus']}</td>
                    <td>{$order['userName']}</td>
                    <td><buton class='btn btn-success order-detail' data-bs-toggle='modal' data-id='{$order['orderID']}' data-bs-target='#process'>Manage</buton></td>
                </tr>
            ";
        }
        echo "</table>";
    }
    $conn->close();
}



function admin_updateGallery($img, $category)
{
    $conn = connect();
    $error = [];
    $cat = $conn->query("SELECT category FROM gallerycat");
    foreach ($cat as $value) {
        $catList[] = $value['category'];
    }

    if (!in_array($category, $catList)) {
        $error['cat'] = "Invalid input Gallery.";
    }
    if (empty($img)) {
        $error['img'] = "You must select an image file.";
    }
    $checkIMG = $conn->query("SELECT * FROM gallery WHERE imgURL = '$img'");
    if ($checkIMG->num_rows > 0) {
        $error['img'] = "The image file has already existed in the gallery, check your photo or change the file name and upload again.";
    }
    if (count($error) > 0) {
        return $error;
    } else {
        $sql = "INSERT INTO gallery (imgURL, category) VALUES ('$img', '$category')";
        if ($conn->query($sql)) {
            return true;
        } else {
            $error['query'] = 'Can not execute query.';
            return $error;
        }
    }
    $conn->close();
}

function admin_displayGallery($category)
{
    $conn = connect();
    if($category == ''){
        $sql = "SELECT * FROM gallery";
    } else {
        $sql = "SELECT * FROM gallery WHERE category = '$category' ORDER BY category";
    }
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return $result;
    } else return FALSE;
    $conn->close();
}
?>