# HƯỚNG DẪN CÀI ĐẶT VÀ CHẠY WEBSITE (XAMPP 8.2.12 + SQL Server + VS Code)

Môi trường của bạn: **xampp-windows-x64-8.2.12-0-VS16** → PHP **8.2.12**, 64-bit, **Thread Safe (TS)**, biên dịch bằng VS16.

> Lưu ý quan trọng: XAMPP **không có sẵn** driver SQL Server. Phải cài thêm theo Bước 2.

## Bước 1. Kiểm tra phiên bản đang có

1. PHP: mở CMD, chạy `C:\xampp\php\php.exe -v` → phải thấy `PHP 8.2.12`. Chạy `C:\xampp\php\php.exe -i | findstr /i "Thread Architecture"` → `Thread Safety => enabled`, `Architecture => x64`.
2. SQL Server: mở SSMS, chạy `SELECT @@VERSION;` và ghi lại phiên bản (đưa vào báo cáo). Ghi lại **tên máy chủ** hiện ở hộp Connect (ví dụ `localhost\SQLEXPRESS`).
3. Tài liệu Microsoft ghi driver PHP hiện hành hỗ trợ SQL Server 2017 trở lên. Nếu bạn dùng bản cũ hơn, hãy kiểm tra thêm bảng "support matrix" của Microsoft.

## Bước 2. Cài driver SQL Server cho PHP 8.2

Bảng tương thích (theo trang System requirements của Microsoft Drivers for PHP for SQL Server):

| Driver | PHP hỗ trợ |
|---|---|
| 5.13 | 8.3, 8.4, 8.5 (**không** có PHP 8.2) |
| **5.12** | 8.1, 8.2, 8.3 ← **dùng bản này** |

1. Cài **Microsoft ODBC Driver 18 for SQL Server** (x64) (hoặc ODBC Driver 17). Tải từ trang của Microsoft.
2. Tải **Microsoft Drivers for PHP for SQL Server phiên bản 5.12.x** (trang GitHub `Microsoft/msphpsql`, mục Releases; hoặc trang Download của Microsoft). Giải nén.
3. Vào thư mục `x64` (hoặc `ts_x64`) của gói giải nén, chép file **`php_pdo_sqlsrv_82_ts_x64.dll`** vào `C:\xampp\php\ext\`.
   (`ts` = Thread Safe, `82` = PHP 8.2, `x64` = 64-bit. Phải khớp đủ ba thông tin này.)
4. Mở `C:\xampp\php\php.ini`, thêm vào nhóm các dòng `extension=...`:
   ```ini
   extension=php_pdo_sqlsrv_82_ts_x64.dll
   ```
   Đồng thời kiểm tra hai dòng sau **không** có dấu `;` ở đầu: `extension=mbstring` và `extension=fileinfo`.
5. Mở XAMPP Control Panel → **Stop** rồi **Start** lại Apache. Nếu Apache lỗi khi nạp DLL, cài thêm *Microsoft Visual C++ Redistributable x64* bản mới nhất rồi thử lại.

## Bước 3. Chuẩn bị SQL Server

1. **Bật đăng nhập SQL Server Authentication**: SSMS → chuột phải tên máy chủ → *Properties* → *Security* → chọn *SQL Server and Windows Authentication mode* → OK → khởi động lại dịch vụ SQL Server.
2. Nếu dùng instance có tên (`localhost\SQLEXPRESS`) hãy bảo đảm dịch vụ **SQL Server Browser** đang chạy (Services.msc). Nếu vẫn không kết nối được, bật TCP/IP trong *SQL Server Configuration Manager* → *SQL Server Network Configuration* → *Protocols for SQLEXPRESS* → *TCP/IP: Enabled*, rồi khởi động lại dịch vụ.
3. Mở `database/bookstore.sql` trong SSMS → **Execute (F5)**. Kết quả cuối cùng hiện số dòng: Users 3, Categories 6, Books 20, Orders 5, OrderDetails 9.
4. Mở `database/02_create_login.sql` → **Execute**. Script tạo tài khoản `bookstore_user` / `Bookstore@123` (khớp `config/config.php`).

## Bước 4. Đưa mã nguồn vào Apache

1. Giải nén thư mục `bookstore` vào `C:\xampp\htdocs\` (kết quả: `C:\xampp\htdocs\bookstore\index.php`).
2. Mở `config/config.php` và sửa `server` cho đúng tên máy chủ SQL Server của bạn (Bước 1). Giữ `base_url = '/bookstore'`.
3. Mở trình duyệt: **http://localhost/bookstore/check-connection.php**. Tất cả các dòng phải hiện **OK** (PHP, pdo_sqlsrv, mbstring, fileinfo, thư mục ảnh, kết nối SQL Server, 5 bảng). Chụp ảnh trang này đưa vào Chương I.
4. Mở **http://localhost/bookstore/** để dùng website. Website tải Bootstrap, font và biểu tượng từ CDN nên máy cần có Internet.

Tài khoản mẫu:

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Quản trị viên | admin@bookstore.vn | Admin@123 |
| Khách hàng | khachhang1@example.com | Khach@123 |
| Khách hàng | khachhang2@example.com | Khach@123 |

## Bước 5. Làm việc với Visual Studio Code

1. *File → Open Folder* → chọn `C:\xampp\htdocs\bookstore`.
2. Cài extension **PHP Intelephense** (gợi ý mã, báo lỗi cú pháp).
3. Sửa mã trong VS Code, lưu file, tải lại trang trên trình duyệt (Apache đọc trực tiếp từ `htdocs`, không cần khởi động lại).
4. **Chạy bằng terminal của VS Code (không cần Apache):** mở terminal (**Ctrl+`**) và gõ
   ```
   C:\xampp\php\php.exe -S localhost:8000 -t .
   ```
   Terminal in ra dòng `Development Server (http://localhost:8000) started`; giữ **Ctrl** rồi bấm vào liên kết đó để mở trang. Dừng bằng **Ctrl+C**. Cách nhanh nhất: **bấm đúp file `start-server.bat`** trong thư mục dự án, cửa sổ đen hiện ra và trình duyệt tự mở website (đóng cửa sổ đen là tắt web). Cũng có thể bấm **Ctrl+Shift+B** trong VS Code (đã cấu hình sẵn trong `.vscode/tasks.json`). Khi dùng cách này, `php.ini` của XAMPP vẫn phải nạp `pdo_sqlsrv` (Bước 2); `base_url` được nhận biết tự động.

## Xử lý lỗi thường gặp

| Thông báo | Nguyên nhân và cách xử lý |
|---|---|
| `could not find driver` | Chưa nạp `pdo_sqlsrv`: kiểm tra tên DLL (82, ts, x64), đường dẫn `ext`, đã khởi động lại Apache; xem `phpinfo()` mục pdo_sqlsrv. |
| Apache không khởi động sau khi thêm extension | DLL sai phiên bản (NTS thay vì TS, hoặc 83 thay vì 82). Xóa dòng vừa thêm, chọn đúng DLL. |
| `Login failed for user 'bookstore_user'` | Chưa bật SQL Server Authentication hoặc chưa chạy `02_create_login.sql`; kiểm tra mật khẩu trong `config.php`. |
| `Named Pipes Provider / server was not found` | Sai tên máy chủ, SQL Server Browser chưa chạy, hoặc TCP/IP chưa bật. Thử `localhost,1433` nếu SQL Server dùng cổng cố định. |
| `certificate chain was issued by an authority that is not trusted` | Mã đã đặt `TrustServerCertificate=1`; nếu vẫn lỗi, kiểm tra đã cài ODBC Driver 17/18. |
| Cannot open database "BookstoreDB" | Chưa chạy `bookstore.sql`, hoặc user chưa được cấp quyền (chạy lại `02_create_login.sql`). |
| Không tải được ảnh bìa trong trang quản trị | Bật `extension=fileinfo`; kiểm tra thư mục `assets/images/books` ghi được. |
| Giao diện mất định dạng | Máy không có Internet nên không tải được Bootstrap/font từ CDN. |

## Xuất biểu đồ cho báo cáo

Các biểu đồ đã được xuất sẵn thành ảnh PNG trong `docs/diagrams/` (kèm file nguồn `.dot`). Nếu muốn chỉnh sửa: cài Graphviz, sửa file `.dot` rồi chạy `dot -Tpng -Gdpi=170 ten-file.dot -o ten-file.png`.
