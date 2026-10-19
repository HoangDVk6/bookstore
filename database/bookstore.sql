/* =====================================================================
   ĐỒ ÁN WEB: XÂY DỰNG WEBSITE BÁN SÁCH TRỰC TUYẾN
   Cơ sở dữ liệu: Microsoft SQL Server (tương thích SQL Server 2017 trở lên)
   Cách chạy: mở file trong SQL Server Management Studio (SSMS) -> Execute (F5).
   Script có thể chạy lại nhiều lần: sẽ xóa và tạo lại các bảng.
   Tài khoản mẫu (mật khẩu đã băm bằng password_hash của PHP):
     admin@bookstore.vn          / Admin@123   (Quản trị viên)
     khachhang1@example.com      / Khach@123   (Khách hàng)
     khachhang2@example.com      / Khach@123   (Khách hàng)
   ===================================================================== */
SET NOCOUNT ON;
GO
IF DB_ID(N'BookstoreDB') IS NULL
    CREATE DATABASE BookstoreDB COLLATE Vietnamese_CI_AI;   -- không phân biệt hoa/thường và dấu khi tìm kiếm
GO
USE BookstoreDB;
GO

-- 1. XÓA BẢNG CŨ (theo thứ tự khóa ngoại)
IF OBJECT_ID(N'dbo.OrderDetails', N'U') IS NOT NULL DROP TABLE dbo.OrderDetails;
IF OBJECT_ID(N'dbo.Orders',       N'U') IS NOT NULL DROP TABLE dbo.Orders;
IF OBJECT_ID(N'dbo.Books',        N'U') IS NOT NULL DROP TABLE dbo.Books;
IF OBJECT_ID(N'dbo.Categories',   N'U') IS NOT NULL DROP TABLE dbo.Categories;
IF OBJECT_ID(N'dbo.Users',        N'U') IS NOT NULL DROP TABLE dbo.Users;
GO

-- 2. TẠO BẢNG
CREATE TABLE dbo.Users (
    UserID       INT IDENTITY(1,1) NOT NULL,
    FullName     NVARCHAR(100) NOT NULL,
    Email        NVARCHAR(150) NOT NULL,
    PasswordHash NVARCHAR(255) NOT NULL,                 -- băm bằng password_hash() của PHP
    Phone        NVARCHAR(20)  NULL,
    Address      NVARCHAR(255) NULL,
    Role         VARCHAR(10)   NOT NULL CONSTRAINT DF_Users_Role DEFAULT 'customer',
    IsActive     BIT           NOT NULL CONSTRAINT DF_Users_IsActive DEFAULT 1,
    CreatedAt    DATETIME2(0)  NOT NULL CONSTRAINT DF_Users_CreatedAt DEFAULT SYSDATETIME(),
    CONSTRAINT PK_Users PRIMARY KEY (UserID),
    CONSTRAINT UQ_Users_Email UNIQUE (Email),
    CONSTRAINT CK_Users_Role CHECK (Role IN ('admin', 'customer'))
);

CREATE TABLE dbo.Categories (
    CategoryID   INT IDENTITY(1,1) NOT NULL,
    CategoryName NVARCHAR(100) NOT NULL,
    Description  NVARCHAR(500) NULL,
    CONSTRAINT PK_Categories PRIMARY KEY (CategoryID),
    CONSTRAINT UQ_Categories_Name UNIQUE (CategoryName)
);

CREATE TABLE dbo.Books (
    BookID       INT IDENTITY(1,1) NOT NULL,
    CategoryID   INT NOT NULL,
    Title        NVARCHAR(200) NOT NULL,
    Author       NVARCHAR(150) NOT NULL,
    Publisher    NVARCHAR(150) NULL,
    PublishYear  SMALLINT NULL,
    Price        DECIMAL(12,0) NOT NULL,                 -- đơn vị: đồng
    Stock        INT NOT NULL CONSTRAINT DF_Books_Stock DEFAULT 0,
    Description  NVARCHAR(MAX) NULL,
    ImagePath    NVARCHAR(255) NULL,                     -- đường dẫn tương đối so với thư mục gốc website
    IsActive     BIT NOT NULL CONSTRAINT DF_Books_IsActive DEFAULT 1,   -- 0 = ẩn khỏi cửa hàng
    CreatedAt    DATETIME2(0) NOT NULL CONSTRAINT DF_Books_CreatedAt DEFAULT SYSDATETIME(),
    CONSTRAINT PK_Books PRIMARY KEY (BookID),
    CONSTRAINT FK_Books_Categories FOREIGN KEY (CategoryID) REFERENCES dbo.Categories(CategoryID),
    CONSTRAINT CK_Books_Price CHECK (Price > 0),
    CONSTRAINT CK_Books_Stock CHECK (Stock >= 0),
    CONSTRAINT CK_Books_Year  CHECK (PublishYear IS NULL OR PublishYear BETWEEN 1000 AND 2100)
);
CREATE INDEX IX_Books_CategoryID ON dbo.Books(CategoryID);

CREATE TABLE dbo.Orders (
    OrderID         INT IDENTITY(1,1) NOT NULL,
    UserID          INT NOT NULL,
    ReceiverName    NVARCHAR(100) NOT NULL,
    Phone           NVARCHAR(20)  NOT NULL,
    ShippingAddress NVARCHAR(255) NOT NULL,
    Note            NVARCHAR(500) NULL,
    PaymentMethod   VARCHAR(20)   NOT NULL CONSTRAINT DF_Orders_Payment DEFAULT 'COD',
    ShippingFee     DECIMAL(12,0) NOT NULL CONSTRAINT DF_Orders_Fee DEFAULT 0,
    TotalAmount     DECIMAL(12,0) NOT NULL CONSTRAINT DF_Orders_Total DEFAULT 0,
    Status          VARCHAR(20)   NOT NULL CONSTRAINT DF_Orders_Status DEFAULT 'Pending',
    CreatedAt       DATETIME2(0)  NOT NULL CONSTRAINT DF_Orders_CreatedAt DEFAULT SYSDATETIME(),
    UpdatedAt       DATETIME2(0)  NULL,
    CONSTRAINT PK_Orders PRIMARY KEY (OrderID),
    CONSTRAINT FK_Orders_Users FOREIGN KEY (UserID) REFERENCES dbo.Users(UserID),
    CONSTRAINT CK_Orders_Status CHECK (Status IN ('Pending', 'Confirmed', 'Shipping', 'Completed', 'Cancelled')),
    CONSTRAINT CK_Orders_Fee CHECK (ShippingFee >= 0),
    CONSTRAINT CK_Orders_Total CHECK (TotalAmount >= 0)
);
CREATE INDEX IX_Orders_UserID ON dbo.Orders(UserID);

CREATE TABLE dbo.OrderDetails (
    OrderDetailID INT IDENTITY(1,1) NOT NULL,
    OrderID       INT NOT NULL,
    BookID        INT NOT NULL,
    Quantity      INT NOT NULL,
    UnitPrice     DECIMAL(12,0) NOT NULL,                -- giá tại thời điểm đặt hàng
    CONSTRAINT PK_OrderDetails PRIMARY KEY (OrderDetailID),
    CONSTRAINT FK_OrderDetails_Orders FOREIGN KEY (OrderID) REFERENCES dbo.Orders(OrderID),
    CONSTRAINT FK_OrderDetails_Books  FOREIGN KEY (BookID)  REFERENCES dbo.Books(BookID),
    CONSTRAINT UQ_OrderDetails_OrderBook UNIQUE (OrderID, BookID),
    CONSTRAINT CK_OrderDetails_Qty   CHECK (Quantity > 0),
    CONSTRAINT CK_OrderDetails_Price CHECK (UnitPrice >= 0)
);
CREATE INDEX IX_OrderDetails_BookID ON dbo.OrderDetails(BookID);
GO

-- 3. DỮ LIỆU MẪU
INSERT INTO dbo.Users (FullName, Email, PasswordHash, Phone, Address, Role) VALUES
(N'Quản trị viên', N'admin@bookstore.vn', N'$2y$10$ni77Av88QkQTvfn6u5NlMePohQEXUSrVglcPocHgL5V5VKCo1fkjO', N'0900000000', N'Hà Nội', 'admin'),
(N'Nguyễn Minh Anh', N'khachhang1@example.com', N'$2y$10$HoDiPQNrs0yh0WP/07zxbunOFPmm9lCac85E2ArxQ7h.qcp1jd1Y.', N'0901234567', N'12 Nguyễn Trãi, Thanh Xuân, Hà Nội', 'customer'),
(N'Trần Quốc Bảo', N'khachhang2@example.com', N'$2y$10$HoDiPQNrs0yh0WP/07zxbunOFPmm9lCac85E2ArxQ7h.qcp1jd1Y.', N'0912345678', N'45 Lê Lợi, Quận 1, TP. Hồ Chí Minh', 'customer');
GO

INSERT INTO dbo.Categories (CategoryName, Description) VALUES
(N'Văn học', N'Tiểu thuyết, truyện ngắn, thơ và các tác phẩm văn học trong nước, nước ngoài.'),
(N'Kinh tế - Kỹ năng', N'Sách quản trị, tài chính cá nhân, kỹ năng sống và phát triển bản thân.'),
(N'Khoa học - Công nghệ', N'Sách phổ biến khoa học, lập trình và công nghệ thông tin.'),
(N'Lịch sử - Xã hội', N'Lịch sử Việt Nam, lịch sử thế giới và các vấn đề xã hội.'),
(N'Thiếu nhi', N'Truyện và sách dành cho thiếu nhi, tuổi mới lớn.'),
(N'Ngoại ngữ', N'Giáo trình ngữ pháp, từ vựng và tài liệu học ngoại ngữ.');
GO

INSERT INTO dbo.Books (CategoryID, Title, Author, Publisher, PublishYear, Price, Stock, Description, ImagePath) VALUES
(1, N'Số đỏ', N'Vũ Trọng Phụng', N'Nhà xuất bản Văn học', 2019, 68000, 40, N'Tiểu thuyết trào phúng nổi tiếng của văn học hiện thực Việt Nam, phê phán xã hội thị thành đầu thế kỷ XX bằng tiếng cười châm biếm sắc sảo.', N'assets/images/books/book01.svg'),
(1, N'Truyện Kiều', N'Nguyễn Du', N'Nhà xuất bản Văn học', 2020, 75000, 35, N'Truyện thơ Nôm kinh điển kể về cuộc đời Thúy Kiều, tác phẩm tiêu biểu của văn học trung đại Việt Nam.', N'assets/images/books/book02.svg'),
(1, N'Tắt đèn', N'Ngô Tất Tố', N'Nhà xuất bản Văn học', 2018, 52000, 28, N'Tiểu thuyết hiện thực phản ánh đời sống người nông dân Việt Nam dưới ách sưu thuế trước năm 1945.', N'assets/images/books/book03.svg'),
(1, N'Nhà giả kim', N'Paulo Coelho', N'Nhà xuất bản Hội Nhà văn', 2020, 79000, 60, N'Hành trình đi tìm kho báu của chàng chăn cừu Santiago, một câu chuyện giàu tính biểu tượng về ước mơ và số phận.', N'assets/images/books/book04.svg'),
(2, N'Đắc nhân tâm', N'Dale Carnegie', N'Nhà xuất bản Tổng hợp TP.HCM', 2021, 86000, 80, N'Cuốn sách kinh điển về nghệ thuật giao tiếp, thuyết phục và xây dựng các mối quan hệ tốt đẹp.', N'assets/images/books/book05.svg'),
(2, N'Cha giàu cha nghèo', N'Robert T. Kiyosaki', N'Nhà xuất bản Trẻ', 2020, 109000, 55, N'Những bài học về tư duy tài chính cá nhân, tài sản và nợ qua so sánh hai người cha có quan điểm khác nhau.', N'assets/images/books/book06.svg'),
(2, N'Tư duy nhanh và chậm', N'Daniel Kahneman', N'Nhà xuất bản Thế giới', 2021, 189000, 3, N'Phân tích hai hệ thống tư duy của con người và những sai lệch nhận thức thường gặp khi ra quyết định.', N'assets/images/books/book07.svg'),
(3, N'Lược sử thời gian', N'Stephen Hawking', N'Nhà xuất bản Trẻ', 2019, 120000, 30, N'Giới thiệu các khái niệm vũ trụ học như hố đen, Vụ Nổ Lớn và bản chất của thời gian bằng ngôn ngữ phổ thông.', N'assets/images/books/book08.svg'),
(3, N'Vũ trụ trong vỏ hạt dẻ', N'Stephen Hawking', N'Nhà xuất bản Trẻ', 2018, 135000, 22, N'Tiếp nối cách trình bày dễ hiểu về vật lý hiện đại, từ thuyết tương đối đến lý thuyết dây.', N'assets/images/books/book09.svg'),
(3, N'Clean Code', N'Robert C. Martin', N'Pearson', 2008, 350000, 18, N'Cuốn sách về nguyên tắc và thực hành viết mã nguồn sạch, dễ đọc và dễ bảo trì cho lập trình viên.', N'assets/images/books/book10.svg'),
(3, N'The Pragmatic Programmer', N'Andrew Hunt, David Thomas', N'Addison-Wesley', 2019, 320000, 0, N'Tập hợp các kinh nghiệm thực tế giúp lập trình viên làm việc hiệu quả và linh hoạt hơn.', N'assets/images/books/book11.svg'),
(4, N'Sapiens: Lược sử loài người', N'Yuval Noah Harari', N'Nhà xuất bản Tri thức', 2020, 229000, 45, N'Nhìn lại lịch sử loài người từ thời nguyên thủy đến hiện đại qua các cuộc cách mạng nhận thức, nông nghiệp và khoa học.', N'assets/images/books/book12.svg'),
(4, N'Việt Nam sử lược', N'Trần Trọng Kim', N'Nhà xuất bản Văn học', 2019, 130000, 25, N'Bộ sử trình bày diễn tiến lịch sử Việt Nam từ thời dựng nước đến đầu thế kỷ XX.', N'assets/images/books/book13.svg'),
(4, N'Súng, vi trùng và thép', N'Jared Diamond', N'Nhà xuất bản Thế giới', 2018, 199000, 14, N'Lý giải vì sao các xã hội loài người phát triển không đồng đều, dựa trên địa lý, môi trường và sinh học.', N'assets/images/books/book14.svg'),
(5, N'Dế Mèn phiêu lưu ký', N'Tô Hoài', N'Nhà xuất bản Kim Đồng', 2021, 62000, 70, N'Cuộc phiêu lưu của chú Dế Mèn qua nhiều vùng đất, tác phẩm thiếu nhi quen thuộc của nhiều thế hệ độc giả.', N'assets/images/books/book15.svg'),
(5, N'Hoàng tử bé', N'Antoine de Saint-Exupéry', N'Nhà xuất bản Hội Nhà văn', 2020, 69000, 50, N'Câu chuyện triết lý nhẹ nhàng về tình bạn, tình yêu và cách nhìn thế giới của người lớn và trẻ em.', N'assets/images/books/book16.svg'),
(5, N'Totto-chan bên cửa sổ', N'Tetsuko Kuroyanagi', N'Nhà xuất bản Hội Nhà văn', 2019, 88000, 38, N'Hồi ký về cô bé Totto-chan và ngôi trường đặc biệt với phương pháp giáo dục đề cao sự tự do, tôn trọng trẻ em.', N'assets/images/books/book17.svg'),
(5, N'Tôi thấy hoa vàng trên cỏ xanh', N'Nguyễn Nhật Ánh', N'Nhà xuất bản Trẻ', 2018, 98000, 65, N'Những câu chuyện tuổi thơ ở vùng quê miền Trung, nhẹ nhàng, giàu cảm xúc về tình anh em và tình bạn.', N'assets/images/books/book18.svg'),
(6, N'English Grammar in Use', N'Raymond Murphy', N'Cambridge University Press', 2019, 210000, 33, N'Giáo trình ngữ pháp tiếng Anh trình độ trung cấp, trình bày từng điểm ngữ pháp kèm bài tập thực hành.', N'assets/images/books/book19.svg'),
(6, N'English Vocabulary in Use', N'Michael McCarthy, Felicity O''Dell', N'Cambridge University Press', 2017, 195000, 20, N'Giáo trình mở rộng vốn từ vựng tiếng Anh theo chủ đề, phù hợp người học trình độ trung cấp.', N'assets/images/books/book20.svg');
GO

-- Đơn hàng mẫu (dùng cho trang thống kê và lịch sử đơn hàng)
INSERT INTO dbo.Orders (UserID, ReceiverName, Phone, ShippingAddress, PaymentMethod, Status, CreatedAt) VALUES (2, N'Nguyễn Minh Anh', N'0901234567', N'12 Nguyễn Trãi, Thanh Xuân, Hà Nội', 'COD', 'Completed', DATEADD(DAY, -20, SYSDATETIME()));
INSERT INTO dbo.Orders (UserID, ReceiverName, Phone, ShippingAddress, PaymentMethod, Status, CreatedAt) VALUES (2, N'Nguyễn Minh Anh', N'0901234567', N'12 Nguyễn Trãi, Thanh Xuân, Hà Nội', 'COD', 'Shipping', DATEADD(DAY, -3, SYSDATETIME()));
INSERT INTO dbo.Orders (UserID, ReceiverName, Phone, ShippingAddress, PaymentMethod, Status, CreatedAt) VALUES (3, N'Trần Quốc Bảo', N'0912345678', N'45 Lê Lợi, Quận 1, TP. Hồ Chí Minh', 'COD', 'Completed', DATEADD(DAY, -12, SYSDATETIME()));
INSERT INTO dbo.Orders (UserID, ReceiverName, Phone, ShippingAddress, PaymentMethod, Status, CreatedAt) VALUES (3, N'Trần Quốc Bảo', N'0912345678', N'45 Lê Lợi, Quận 1, TP. Hồ Chí Minh', 'COD', 'Pending', DATEADD(DAY, -1, SYSDATETIME()));
INSERT INTO dbo.Orders (UserID, ReceiverName, Phone, ShippingAddress, PaymentMethod, Status, CreatedAt) VALUES (2, N'Nguyễn Minh Anh', N'0901234567', N'12 Nguyễn Trãi, Thanh Xuân, Hà Nội', 'COD', 'Cancelled', DATEADD(DAY, -8, SYSDATETIME()));
GO
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 1, BookID, 2, Price FROM dbo.Books WHERE BookID = 5;
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 1, BookID, 1, Price FROM dbo.Books WHERE BookID = 1;
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 2, BookID, 1, Price FROM dbo.Books WHERE BookID = 8;
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 2, BookID, 2, Price FROM dbo.Books WHERE BookID = 15;
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 3, BookID, 1, Price FROM dbo.Books WHERE BookID = 11;
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 4, BookID, 1, Price FROM dbo.Books WHERE BookID = 6;
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 4, BookID, 1, Price FROM dbo.Books WHERE BookID = 4;
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 4, BookID, 1, Price FROM dbo.Books WHERE BookID = 19;
INSERT INTO dbo.OrderDetails (OrderID, BookID, Quantity, UnitPrice) SELECT 5, BookID, 1, Price FROM dbo.Books WHERE BookID = 14;
GO
-- Tính phí vận chuyển (miễn phí từ 300.000đ, ngược lại 30.000đ) và tổng tiền cho các đơn mẫu
UPDATE o SET
    ShippingFee = CASE WHEN s.Subtotal >= 300000 THEN 0 ELSE 30000 END,
    TotalAmount = s.Subtotal + CASE WHEN s.Subtotal >= 300000 THEN 0 ELSE 30000 END
FROM dbo.Orders o
JOIN (SELECT OrderID, SUM(Quantity * UnitPrice) AS Subtotal FROM dbo.OrderDetails GROUP BY OrderID) s
  ON s.OrderID = o.OrderID;
GO

-- 4. KIỂM TRA NHANH
SELECT (SELECT COUNT(*) FROM dbo.Users)        AS Users,
       (SELECT COUNT(*) FROM dbo.Categories)   AS Categories,
       (SELECT COUNT(*) FROM dbo.Books)        AS Books,
       (SELECT COUNT(*) FROM dbo.Orders)       AS Orders,
       (SELECT COUNT(*) FROM dbo.OrderDetails) AS OrderDetails;
GO
