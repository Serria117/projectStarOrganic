            <?php
            $conn = connect();
            $catList = $conn->query("SELECT category FROM gallerycat");
            $conn->close();
            ?>
            <div class="content">
                <h2>Gallery</h2>
                <div class="gallery add" id="addgallery">
                    <h4>Add to Gallery</h4>
                    <form action="updateGallery.php" method="post" enctype="multipart/form-data">
                        <div class="input-group mb-1">
                            <span class="input-group-text">Category:</span>
                            <select class="form-select" name="category" id="ctg" style="max-width:20%">
                                <option value="select...">select...</option>
                                <?php
                                foreach ($catList as $value) {
                                    echo "<option value='{$value['category']}'>{$value['category']}</option>";
                                }
                                ?>
                            </select>
                            
                            <input style="max-width:30%" type="file" class="form-control" id="customFile" name="picture" accept="image/*" />
                        </div>
                        <!-- image upload -->
                        <!-- submit -->
                        <div class="submit input-group mb3">
                            <input class="btn-add btn btn-primary" type="submit" value="Add" name="add" id="add">
                            <button class="btn-add btn btn-danger" type="reset" id="reset">Reset</button>
                        </div>
                    </form>
                </div>
                <hr>

                <div class="gallery list">
                    <div class="row" style="padding: 5px;">
                        <div class="col">
                            <div class="input-group">
                                <span class="input-group-text">Display:</span>
                                <a href="admin_panel.php?page=gallery&view=All" style="width:100px" class="btn btn-secondary">All</a>
                                <?php foreach ($catList as $value) : ?>
                                    <a href="admin_panel.php?page=gallery&view=<?= $value['category'] ?>" style="width:100px" class="btn btn-outline-secondary"><?= $value['category'] ?></a>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="display">
                        <!-- display the gallery list from database -->
                        <?php
                        if (isset($_GET['view']) && $_GET['view'] !== "All") {
                            $result = admin_displayGallery($_GET['view']);
                        } else {
                            $result = admin_displayGallery('');
                        }
                        if ($result !== FALSE) { ?>
                            <div class='row'>
                                <?php foreach ($result as $value) : ?>
                                    <div class='col-md-3 col-sm-2' style="padding:5px;">
                                        <div style="border: none; background: #dbdbdb; padding: 5px; position: relative">
                                            <img style="width:95%; display: block; margin: 0 auto;" src="../<?= $value['imgURL'] ?>" alt="image">
                                            <div style="position: absolute; top: 70%; right:5%; display:flex; z-index:1" class="gl-edit">
                                                <a href="deleteGallery.php?pic=<?= $value['id'] ?>"  class="btn btn-warning" onclick="return confirm('Do you want to remove this item?')"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        <?php } else {
                            echo "<h2>No image in the gallery</h2>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            <!-- display message -->
            <?php
            if (isset($_SESSION['error'])) {
                echo "<script>alert('{$_SESSION['error']}')</script>";
                unset($_SESSION['error']);
            }

            if (isset($_SESSION['errUpdate'])) {
                echo "<script>alert('Update FAILED! Please check the following error(s):\\n";
                foreach ($_SESSION['errUpdate'] as $value) {
                    echo " - " . $value . "\\n";
                }
                echo "')</script>";
                unset($_SESSION['errUpdate']);
            }

            if (isset($_SESSION['success'])) {
                echo "<script>alert('{$_SESSION['success']}')</script>";
                unset($_SESSION['success']);
            }
            ?>
            </body>

            </html>