# KỊCH BẢN KIỂM THỬ

**Quan trọng:** cột "Kết quả" để trống. Bạn chạy từng ca trên máy của mình rồi ghi **Đạt** hoặc **Không đạt**. Chỉ đưa vào báo cáo những ca bạn đã thực sự chạy. Mã nguồn chưa được chạy thử với SQL Server thật trước khi giao cho bạn.

| Mã | Chức năng | Các bước thực hiện | Kết quả mong đợi | Kết quả |
|---|---|---|---|---|
| TC01 | Kết nối CSDL | Mở `check-connection.php` | Mọi dòng hiện OK | |
| TC02 | Trang chủ | Mở `/bookstore/` | Thấy banner, 6 danh mục, 4 sách bán chạy, 8 sách mới | |
| TC03 | Danh sách + phân trang | Mở Danh mục sách → Tất cả sách | 8 sách/trang, có 3 trang (20 sách), bấm trang 2 hoạt động | |
| TC04 | Lọc theo danh mục | Chọn danh mục "Thiếu nhi" | Chỉ hiện 4 cuốn thuộc Thiếu nhi | |
| TC05 | Tìm kiếm | Nhập "nguyen nhat anh" (không dấu) | Hiện sách của Nguyễn Nhật Ánh | |
| TC06 | Tìm kiếm không có kết quả | Nhập "zzzz" | Thông báo không tìm thấy sách | |
| TC07 | Chi tiết sách | Bấm vào một cuốn | Hiện bìa, tác giả, giá, tồn kho, mô tả | |
| TC08 | Sách hết hàng | Mở "The Pragmatic Programmer" | Hiện nhãn Hết hàng, nút Thêm vào giỏ bị vô hiệu | |
| TC09 | Đăng ký hợp lệ | Điền đầy đủ thông tin hợp lệ | Thông báo thành công, chuyển trang đăng nhập; trong bảng Users có dòng mới, PasswordHash bắt đầu bằng `$2y$` | |
| TC10 | Đăng ký sai | Email sai định dạng; mật khẩu 5 ký tự; nhập lại không khớp; email đã tồn tại | Mỗi trường hợp có thông báo lỗi tiếng Việt, không tạo tài khoản | |
| TC11 | Đăng nhập đúng | khachhang1@example.com / Khach@123 | Vào trang chủ, hiện tên người dùng | |
| TC12 | Đăng nhập sai | Sai mật khẩu | Thông báo "Email hoặc mật khẩu không đúng." | |
| TC13 | Đăng xuất | Bấm Đăng xuất | Về trang chủ, menu hiện Đăng nhập/Đăng ký | |
| TC14 | Thêm vào giỏ | Bấm Thêm vào giỏ ở 2 cuốn | Số trên biểu tượng giỏ tăng; thông báo thành công | |
| TC15 | Giới hạn tồn kho | Với "Tư duy nhanh và chậm" (tồn 3), nhập số lượng 10 rồi thêm | Số lượng bị giảm còn 3, có cảnh báo | |
| TC16 | Cập nhật / xóa giỏ | Đổi số lượng, bấm Cập nhật; bấm xóa một dòng | Tổng tiền đổi đúng; dòng bị xóa | |
| TC17 | Đặt hàng cần đăng nhập | Chưa đăng nhập, bấm Tiến hành đặt hàng | Chuyển sang trang đăng nhập | |
| TC18 | Đặt hàng thành công | Đăng nhập, điền thông tin hợp lệ, xác nhận | Thông báo mã đơn; giỏ rỗng; Orders và OrderDetails có dữ liệu; Books.Stock giảm đúng số lượng | |
| TC19 | Phí vận chuyển | Đơn < 300.000đ và đơn ≥ 300.000đ | Phí 30.000đ và Miễn phí | |
| TC20 | Đặt hàng sai thông tin | Số điện thoại "123" | Thông báo lỗi, không tạo đơn | |
| TC21 | Chống sửa giá | Dùng DevTools sửa giá/số lượng bất thường trên biểu mẫu | Đơn lấy giá từ CSDL; số lượng vượt tồn bị từ chối | |
| TC22 | Lịch sử đơn hàng | Menu → Đơn hàng của tôi → Xem chi tiết | Hiện đúng danh sách và chi tiết | |
| TC23 | Hủy đơn (khách) | Hủy đơn "Chờ xác nhận" | Đơn thành "Đã hủy", tồn kho được hoàn lại | |
| TC24 | Cập nhật tài khoản | Sửa họ tên, đổi mật khẩu | Lưu thành công, đăng nhập lại bằng mật khẩu mới được | |
| TC25 | Phân quyền khách | Đăng nhập khách hàng, mở `/bookstore/admin/index.php` | Bị chuyển về trang chủ kèm thông báo không có quyền | |
| TC26 | Phân quyền khách vãng lai | Chưa đăng nhập, mở `/admin/index.php` | Chuyển sang trang đăng nhập | |
| TC27 | Dashboard | Đăng nhập admin@bookstore.vn / Admin@123 | Hiện số sách, danh mục, đơn hàng, doanh thu (đơn Hoàn thành), danh sách sắp hết hàng | |
| TC28 | Thêm sách | Quản lý sách → Thêm, kèm ảnh JPG/PNG | Sách xuất hiện ở danh sách và cửa hàng | |
| TC29 | Kiểm tra dữ liệu sách | Giá âm, tên trống, tệp .txt làm ảnh | Báo lỗi, không lưu | |
| TC30 | Sửa sách | Đổi giá và tồn kho | Dữ liệu cập nhật đúng | |
| TC31 | Xóa / ẩn sách | Xóa sách mới thêm; xóa sách đã có trong đơn | Sách mới bị xóa; sách đã có đơn chuyển sang Đang ẩn kèm thông báo | |
| TC32 | Quản lý danh mục | Thêm, sửa; xóa danh mục đang có sách | Thêm/sửa được; xóa danh mục có sách bị từ chối | |
| TC33 | Quản lý khách hàng | Tìm khách; khóa tài khoản rồi thử đăng nhập | Tài khoản bị khóa không đăng nhập được | |
| TC34 | Quản lý đơn hàng | Lọc theo trạng thái, xem chi tiết | Hiện đúng đơn và chi tiết | |
| TC35 | Cập nhật trạng thái | Chờ xác nhận → Đã xác nhận → Đang giao → Hoàn thành | Trạng thái đổi đúng; đơn Hoàn thành không đổi tiếp được | |
| TC36 | Admin hủy đơn | Hủy một đơn đang giao | Tồn kho hoàn lại; trạng thái Đã hủy | |
| TC37 | Chống SQL Injection | Ô tìm kiếm nhập `' OR 1=1 --` | Không lỗi SQL, không lộ dữ liệu (coi như chuỗi tìm kiếm) | |
| TC38 | Chống XSS | Họ tên khi đăng ký: `<script>alert(1)</script>` | Hiển thị nguyên văn, không chạy script | |
| TC39 | Giao diện điện thoại | Thu nhỏ cửa sổ / chế độ responsive của trình duyệt | Menu thu gọn, lưới sách tự xuống dòng | |

Hướng dẫn xem dữ liệu để đối chiếu: trong SSMS chạy `SELECT * FROM Orders ORDER BY OrderID DESC; SELECT BookID, Title, Stock FROM Books;`
