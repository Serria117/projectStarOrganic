$(document).ready(function () {
    countCart(); //count the number of product in cart everytime the document is loaded to display the badge icon

    /*Event driven by click each "add to cart" button on the product page, 
    When user click on the button, a request will be sent to the server*/
    $(document).on('click', '.2cart',  function(e) {
        e.preventDefault();
        var productID = $(this).attr('data-id'); //The data-id attribute of the button contain the #id of the product
        var productName = $(this).attr('data-name'); //the data-name attribute of the button contain the #name of the product

        //Check if product is already added:
        //Select all the element with class 'item-name
        //Loop through the above selection (items), with each item compare its inner-text with the current product name
        //if match, alert the user and stop the event by returning.
        items = document.querySelectorAll('.item-name'); 
        for (item of items) {
            if (item.innerText == productName) {
                alert('This item is already in your cart!');
                return;
            }
        }
        //Send request by ajax method
        $.ajax({
            url: "cart1.php", //the file that proceed the request 
            method: "get", //the method of the request
            data: {
                id: productID, //the data sent
                name: productName,
            },
            success: function (data) { //callback function when request success
                $('.cart-items').append(data); //append the html data generated by the cart1.php to the element contain cart item
                updateCart(); //call function to calculate value of the cart
                countCart(); //call function to calculate products in cart
            }
        });

    });

    /*change item quantity in the cart array: 
    This function triggers on the event when user change the quantity input of each product in cart
    It should be triggered on document scale, since the 'cart-item' element which contain the 'quantity-input' is dymanically added after the page is loaded.
    */
    $(document).on('input', '.quantity-input', function () {
        updateCart(); //Call function to calculate value of the cart each time user change the quantity
        var productID = $(this).attr('data-id'); //The data-id attribute of the button contain the #id of the product
        var qtt = $(this).val(); //the new quantity of the product
        if(qtt <=0 ) {
            qtt = 1;
            $(this).val(1);
        }
        var sum = parseFloat($('#sum').text()); //the new total value in cart

        //Send all the above data to server
        $.ajax({
            url: 'changeCart.php', //the file that will update the cart array in a session[] variable
            method: 'get',
            data: {
                id: productID,
                qtt: qtt,
                sum: sum
            }, //no callback function needed, we only need to update the cart session array
        })
    })

    /*remove item from cart array*/
    $('.cart-items').on('click', '.remove', function () {
        var removeID = $(this).attr('data-id'); //get the id of the product to be removed from cart
        //send request
        $.ajax({
            url: 'changeCart.php', //this file will proceed the remove function
            method: 'get',
            data: {
                removeID: removeID
            },
            success: function () { //callback function: update cart value and count item
                updateCart();  
                countCart();
            }
        })
        $(this).closest('tr').remove();  //remove the element from the page
    })

    //function to update value in cart, include: subtotal of each product, total value
    function updateCart() {
        var sum = 0;
        var quantity;
        $('#cart-product > tbody > tr').each(function () {
            quantity = $(this).find('.quantity-input').val();
            var price = parseFloat($(this).find('.price').val());
            if(quantity<=0) {
                quantity = 1;
            }
            var subtotal = quantity * price;
            if (!isNaN(subtotal)) {
                sum += subtotal;
            }
            $(this).find('.subtotal').val(Number(subtotal).toFixed(2));
            $(this).find('.visible-subtotal').text(Number(subtotal).toFixed(2));
        })
        $('#sum').text(Number(sum).toFixed(2));
    }

    /*Prevent 'go to cart' if nothing in cart
    On event 'click', check the length of the collection 'cartItem', 
    if equal to zero then preven the default function of the button
    */
    $('.gocart').on('click', function (e) {
        var cartItem = document.querySelectorAll('.cart-row'); 
        if (cartItem.length == 0) {
            e.preventDefault(); 
            alert('Please add something to your cart first.');
            return;
        }
    })
    //Prevent 'check out' if nothing in cart
    $('#checkout').on('click', function (e) {
        var cartItem = document.querySelectorAll('.cart-row');
        if (cartItem.length == 0) {
            e.preventDefault();
            alert('Please add something to your cart first.');
            return;
        }
    })
    
    //count item currently in cart:
    function countCart(){
        var cartItem = document.querySelectorAll('.cart-row');
        var count = cartItem.length;
        $('#badge').text(count); //update the inner text of the badge to show the number of product in cart
        if(count == 0) { //if the count equal to zero then don't display the badge icon
            $('#badge').text('');
        }
    }
});