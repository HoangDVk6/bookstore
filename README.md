# Website bán sách trực tuyến (PHP + SQL Server)

Đồ án môn Lập trình Web. Xem hướng dẫn chi tiết trong `docs/HUONG_DAN_CAI_DAT.md`.

1. Cài driver `pdo_sqlsrv` 5.12.x cho PHP 8.2 (XAMPP 8.2.12) và ODBC Driver 18.
2. Chạy `database/bookstore.sql` rồi `database/02_create_login.sql` trong SSMS.
3. Chép thư mục này vào `C:\xampp\htdocs\bookstore`, sửa `config/config.php`.
4. Mở `http://localhost/bookstore/check-connection.php`, sau đó `http://localhost/bookstore/`.

Tài khoản mẫu: `admin@bookstore.vn` / `Admin@123`; `khachhang1@example.com` / `Khach@123`.

Tình trạng: mã đã qua kiểm tra cú pháp PHP và T-SQL; **chưa được chạy thử với SQL Server thật**. Hãy chạy `docs/KICH_BAN_KIEM_THU.md`.
