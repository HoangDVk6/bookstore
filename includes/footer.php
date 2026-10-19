</main>
<footer class="site-footer mt-5">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-5">
                <h5><?= e(config('app.name')) ?></h5>
                <p class="mb-0">Website bán sách trực tuyến được xây dựng cho đồ án môn Lập trình Web bằng PHP và Microsoft SQL Server. Dữ liệu sách trong website là dữ liệu mẫu phục vụ học tập.</p>
            </div>
            <div class="col-6 col-md-3">
                <h6>Liên kết</h6>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?= e(url('index.php')) ?>">Trang chủ</a></li>
                    <li><a href="<?= e(url('books.php')) ?>">Tất cả sách</a></li>
                    <li><a href="<?= e(url('cart.php')) ?>">Giỏ hàng</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h6>Chính sách</h6>
                <ul class="list-unstyled mb-0">
                    <li>Thanh toán khi nhận hàng (COD)</li>
                    <li>Miễn phí vận chuyển từ <?= money(config('app.free_ship_from')) ?></li>
                    <li>Hủy đơn khi đơn đang chờ xác nhận</li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="small text-center">© <?= date('Y') ?> <?= e(config('app.name')) ?> - Đồ án môn Lập trình Web</div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
