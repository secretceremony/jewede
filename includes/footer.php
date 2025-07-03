<?php // your_project_root/includes/footer.php ?>
    </main>
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-lg-2 mb-4">
                    <h5 class="mb-3 text-uppercase">Company</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Careers</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Locations</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-lg-2 mb-4">
                    <h5 class="mb-3 text-uppercase">Customer Care</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none">My Account</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Return My Order</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Help & FAQs</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-lg-2 mb-4">
                    <h5 class="mb-3 text-uppercase">Terms & Policies</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Terms Conditions</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-lg-3 mb-4">
                    <h5 class="mb-3 text-uppercase">Follow Us</h5>
                    <ul class="list-unstyled small">
                        <li><a href="#" class="text-white-50 text-decoration-none">Instagram</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Facebook</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">TikTok</a></li>
                        <li><a href="#" class="text-white-50 text-decoration-none">Pinterest</a></li>
                    </ul>
                </div>
                <div class="col-12 col-lg-3 mb-4 text-center text-lg-start">
                    <h5 class="mb-3 text-uppercase">Contact Us</h5>
                    <p class="small text-white-50">
                        Jl. Contoh Alamat No. 123<br>
                        Balikpapan, East Kalimantan, Indonesia<br>
                        Email: info@example.com<br>
                        Phone: +62 123 4567 890
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script src="https://cdn.datatables.net/2.0.8/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/js/all.min.js" integrity="sha512-u3fPA7V8qQkM+XiEFYHzfkiLIiM7VXPHEXoLiF4OhtJykBCkRVV7Ww1G3TeftoJFXwkNZf+J5z6n8rK5f/z4bQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="<?php echo $base_url; ?>assets/js/script.js"></script>

</body>
</html>
<?php
// Close the database connection if it was opened in db_connect.php
// Ensure $conn is in scope here.
if (isset($conn) && $conn instanceof mysqli) { // Check if $conn exists and is a mysqli object
    $conn->close();
}
?>