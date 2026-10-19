// JavaScript dùng chung cho khu vực khách hàng và quản trị
document.addEventListener('DOMContentLoaded', function () {
    // 1. Tự ẩn thông báo sau 5 giây
    document.querySelectorAll('.auto-dismiss').forEach(function (el) {
        setTimeout(function () {
            if (window.bootstrap) { bootstrap.Alert.getOrCreateInstance(el).close(); }
        }, 5000);
    });

    // 2. Hộp thoại xác nhận cho các nút có thuộc tính data-confirm
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (ev) {
            if (!confirm(el.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    });

    // 3. Nút tăng/giảm số lượng (.qty-box)
    document.querySelectorAll('.qty-box').forEach(function (box) {
        var input = box.querySelector('input[type=number]');
        var max = parseInt(input.getAttribute('max') || '999', 10);
        box.querySelectorAll('button[data-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var v = parseInt(input.value || '1', 10) + parseInt(btn.dataset.step, 10);
                input.value = Math.min(Math.max(v, 1), max);
            });
        });
    });

    // 4. Kiểm tra biểu mẫu phía trình duyệt (Bootstrap); máy chủ vẫn kiểm tra lại
    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            var pw = form.querySelector('[name=password]');
            var pw2 = form.querySelector('[name=password_confirm]');
            if (pw && pw2) {
                pw2.setCustomValidity(pw.value !== pw2.value ? 'Mật khẩu nhập lại không khớp' : '');
            }
            if (!form.checkValidity()) { ev.preventDefault(); ev.stopPropagation(); }
            form.classList.add('was-validated');
        });
    });
});
