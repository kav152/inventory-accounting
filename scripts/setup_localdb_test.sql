-- Local test DB so login/home work without remote SQL
IF DB_ID(N'ConstructionAccounting_new12') IS NULL
    CREATE DATABASE ConstructionAccounting_new12;
GO
USE ConstructionAccounting_new12;
GO

IF OBJECT_ID(N'dbo.[User]', N'U') IS NULL
CREATE TABLE dbo.[User] (
    IDUser INT IDENTITY(1,1) PRIMARY KEY,
    Surname NVARCHAR(100) NOT NULL,
    Name NVARCHAR(100) NOT NULL,
    Patronymic NVARCHAR(100) NULL,
    Password NVARCHAR(255) NOT NULL,
    Status INT NOT NULL DEFAULT 0,
    isActive BIT NOT NULL DEFAULT 1
);

IF OBJECT_ID(N'dbo.BrandTMC', N'U') IS NULL
CREATE TABLE dbo.BrandTMC (
    IDBrandTMC INT IDENTITY(1,1) PRIMARY KEY,
    NameBrand NVARCHAR(200) NULL
);

IF OBJECT_ID(N'dbo.ModelTMC', N'U') IS NULL
CREATE TABLE dbo.ModelTMC (
    IDModel INT IDENTITY(1,1) PRIMARY KEY,
    NameModel NVARCHAR(200) NULL
);

IF OBJECT_ID(N'dbo.Location', N'U') IS NULL
CREATE TABLE dbo.Location (
    IDLocation INT IDENTITY(1,1) PRIMARY KEY,
    NameLocation NVARCHAR(200) NULL,
    FormsJointStockCompanies NVARCHAR(200) NULL
);

IF OBJECT_ID(N'dbo.InventoryItem', N'U') IS NULL
CREATE TABLE dbo.InventoryItem (
    ID_TMC INT IDENTITY(1,1) PRIMARY KEY,
    NameTMC NVARCHAR(300) NOT NULL,
    Status INT NOT NULL DEFAULT 0,
    SerialNumber NVARCHAR(100) NULL,
    IDTypesTMC INT NULL,
    IDBrandTMC INT NULL,
    IDModel INT NULL,
    IDLocation INT NULL
);

IF OBJECT_ID(N'dbo.RegistrationInventoryItem', N'U') IS NULL
CREATE TABLE dbo.RegistrationInventoryItem (
    IDRegItem INT NOT NULL PRIMARY KEY,
    CurrentUser INT NULL
);

IF OBJECT_ID(N'dbo.Brigades', N'U') IS NULL
CREATE TABLE dbo.Brigades (
    IDBrigades INT IDENTITY(1,1) PRIMARY KEY,
    NameBrigades NVARCHAR(200) NULL,
    IDResponsibleIssuing INT NULL
);

IF OBJECT_ID(N'dbo.RepairItem', N'U') IS NULL
CREATE TABLE dbo.RepairItem (
    ID_Repair INT IDENTITY(1,1) PRIMARY KEY,
    ID_TMC INT NULL,
    IDLocation INT NULL,
    inBasket BIT NOT NULL DEFAULT 0,
    DateReturnService DATETIME NULL,
    InvoiceNumber NVARCHAR(100) NULL,
    UPD NVARCHAR(100) NULL,
    RepairCost DECIMAL(18,2) NULL,
    RepairDescription NVARCHAR(MAX) NULL
);

IF OBJECT_ID(N'dbo.CommentsHistory', N'U') IS NULL
CREATE TABLE dbo.CommentsHistory (
    IDComment INT IDENTITY(1,1) PRIMARY KEY,
    Comment NVARCHAR(MAX) NULL
);

IF OBJECT_ID(N'dbo.HistoryOperations', N'U') IS NULL
CREATE TABLE dbo.HistoryOperations (
    IDHistory INT IDENTITY(1,1) PRIMARY KEY,
    ID_TMC INT NULL,
    IDComment INT NULL,
    HistoryData DATETIME NULL
);

-- password for all: 123
DECLARE @hash NVARCHAR(255) = N'$2y$10$xD5yw/bIOaU.HUlsX67CtOJQjsTXF2mC2yZAS7VXjf3TWjduALabu';

IF NOT EXISTS (SELECT 1 FROM dbo.[User])
BEGIN
    INSERT INTO dbo.[User] (Surname, Name, Patronymic, Password, Status, isActive) VALUES
    (N'Большедворов', N'Константин', N'Викторович', @hash, 0, 1),
    (N'Ототюк', N'Андрей', N'Дмитриевич', @hash, 0, 1),
    (N'Кудрявцева', N'Людмила', N'Александровна', @hash, 1, 1),
    (N'Дубровская', N'Елена', N'Фёдоровна', @hash, 1, 1);
END
GO
