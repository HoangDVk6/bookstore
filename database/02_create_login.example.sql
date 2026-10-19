
/*
    Tao tai khoan SQL Server cho website.
    Chay sau khi da tao database BookstoreDB.
    Hay thay YOUR_STRONG_PASSWORD bang mat khau tu chon.
*/
USE master;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.server_principals
    WHERE name = N'bookstore_user'
)
BEGIN
    CREATE LOGIN bookstore_user
    WITH PASSWORD = N'YOUR_STRONG_PASSWORD',
         CHECK_POLICY = ON;
END;
GO

USE BookstoreDB;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.database_principals
    WHERE name = N'bookstore_user'
)
BEGIN
    CREATE USER bookstore_user FOR LOGIN bookstore_user;
END;
GO

ALTER ROLE db_datareader ADD MEMBER bookstore_user;
ALTER ROLE db_datawriter ADD MEMBER bookstore_user;
GO
