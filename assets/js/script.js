$(document).ready(function() {
    // --- Script for updating cart quantity on cart.php page ---
    $('.update-cart-qty').on('change', function() {
        let productId = $(this).data('product-id');
        let newQuantity = $(this).val();
        let maxStock = parseInt($(this).attr('max'));

        if (newQuantity < 1) {
            alert('Kuantitas minimal adalah 1.');
            $(this).val(1); // Reset to 1
            return;
        }

        if (newQuantity > maxStock) {
            alert('Kuantitas melebihi stok yang tersedia (' + maxStock + ').');
            $(this).val(maxStock); // Reset to max stock
            newQuantity = maxStock; // Use max stock for update
        }

        // Redirect for update, can be replaced with AJAX for a smoother experience
        window.location.href = 'cart.php?action=update&product_id=' + productId + '&qty=' + newQuantity;
    });

    // --- Admin Orders Page Specific Scripts ---

    // Initialize DataTables for the orders table if it exists
    if ($('#ordersTable').length) {
        $('#ordersTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "order": [[5, "desc"]] // Sort by 'Tanggal Pesan' column (index 5) descending
        });
    }

    // Populate data into Order Detail Modal and handle AJAX for order items
    // Using Bootstrap's 'show.bs.modal' event for the detail modal
    $('#orderDetailAdminModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget); // Button that triggered the modal

        // Extract info from data-* attributes of the clicked button
        const orderId = button.data('order-id');
        const userName = button.data('user-name');
        const userEmail = button.data('user-email');
        const totalPrice = button.data('total-price');
        const currentStatus = button.data('status');
        const createdAt = button.data('created-at');

        // Update the modal's display elements with order details
        $('#modalOrderId').text(orderId);
        $('#modalUserName').text(userName);
        $('#modalUserEmail').text(userEmail);
        $('#modalTotalPrice').text(parseFloat(totalPrice).toFixed(2)); // Format total price
        $('#modalCreatedAt').text(createdAt);

        // This input is for a form within the detail modal to update status.
        // It appears you've removed the form and submit button from this specific modal
        // in your latest HTML. If you re-add them, this line is necessary.
        // $('#detailModalOrderIdForStatusUpdate').val(orderId);

        // This select element is for updating status within the detail modal.
        // If the status update form/select is not in this modal anymore, this line is not needed here.
        // $('#modalStatusSelect').val(currentStatus);

        // Update the status badge display within the detail modal
        let statusBadge = $('#modalStatusBadge');
        statusBadge.text(currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1));
        statusBadge.removeClass().addClass('badge'); // Reset and apply new Bootstrap badge classes
        switch(currentStatus) {
            case 'pending': statusBadge.addClass('bg-warning text-dark'); break;
            case 'processing': statusBadge.addClass('bg-info text-white'); break;
            case 'completed': statusBadge.addClass('bg-success text-white'); break;
            case 'cancelled': statusBadge.addClass('bg-danger text-white'); break;
            default: statusBadge.addClass('bg-secondary text-white'); break;
        }

        // Prepare to load order items via AJAX
        const orderItemsList = $('#modalOrderItemsList');
        orderItemsList.html('<li class="list-group-item text-center text-muted">Memuat item...</li>'); // Show loading message

        // AJAX call to fetch order items
        // BASE_URL is prepended to ensure the correct absolute path to the PHP endpoint
        $.ajax({
            url: BASE_URL + 'admin/get_order_items.php', // Correct path: BASE_URL + 'admin/get_order_items.php'
            type: 'GET',
            data: { order_id: orderId },
            dataType: 'json', // Expecting a JSON response from the server
            success: function(response) {
                let itemsHtml = '';
                // Check if the AJAX response indicates success and contains items
                if (response.success && response.items.length > 0) {
                    response.items.forEach(function(item) {
                        // Construct the image URL. Assumes item.image_url is just the filename
                        // and that images are stored in your_project_root/assets/img/
                        const imageUrlPath = BASE_URL + 'assets/img/' + item.image_url;
                        itemsHtml += `
                            <li class="list-group-item d-flex align-items-center py-2">
                                <img src="${imageUrlPath}" alt="${item.name}" class="img-fluid rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    ${item.name} x ${item.quantity}
                                </div>
                                <span class="fw-bold">Rp${(item.price * item.quantity).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </li>
                        `;
                    });
                } else {
                    // Display message if no items are found or if the success flag is false
                    itemsHtml = `<li class="list-group-item text-center text-muted">${response.message || 'Tidak ada item ditemukan untuk pesanan ini.'}</li>`;
                }
                orderItemsList.html(itemsHtml); // Update the order items list in the modal
            },
            error: function(xhr, status, error) {
                // Log detailed error information for debugging purposes
                console.error("AJAX Error fetching order items:", {
                    status: status,
                    error: error,
                    responseText: xhr.responseText, // Provides the raw response from the server
                    xhr: xhr // The full XMLHttpRequest object
                });
                orderItemsList.html('<li class="list-group-item text-center text-danger">Gagal memuat item pesanan. Silakan coba lagi.</li>');
            }
        });
    });

    // Populate data into Edit Status Modal when "Status" button is clicked
    // This uses a delegated event handler for dynamically added buttons
    $(document).on('click', '.edit-status-btn', function() {
        var orderId = $(this).data('order-id');
        var currentStatus = $(this).data('current-status');

        $('#editStatusModalOrderId').text(orderId); // Display order ID in modal title
        $('#editStatusOrderId').val(orderId); // Set hidden input for form submission
        $('#newStatus').val(currentStatus); // Set the dropdown to the order's current status
    });

    // --- Admin Products Page Specific Scripts ---

    // Initialize DataTables for the products table if it exists
    if ($('#productsTable').length) {
        $('#productsTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]] // Options for number of rows per page
        });
    }

    // Populate data into Edit Product Modal when "Edit" button is clicked
    // This uses a delegated event handler for dynamically added buttons
    $(document).on('click', '.edit-btn', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var description = $(this).data('description');
        var price = $(this).data('price');
        var stock = $(this).data('stock');
        var imageUrl = $(this).data('image-url'); // This should be just the filename (e.g., "my_product.jpg")

        $('#editProductId').val(id);
        $('#editName').val(name);
        $('#editDescription').val(description);
        $('#editPrice').val(price);
        $('#editStock').val(stock);
        $('#editCurrentImageUrl').val(imageUrl); // Store just the filename for current image

        // Set the image preview source. Assumes BASE_URL is defined and images are in assets/img/
        $('#currentImagePreview img').attr('src', BASE_URL + 'assets/img/' + imageUrl);
    });

    // --- Admin Users Page Specific Scripts ---

    // Initialize DataTables for the users table if it exists
    if ($('#usersTable').length) {
        $('#usersTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "order": [[4, "desc"]] // Sort by 'Tanggal Daftar' column (index 4) descending
        });
    }
});