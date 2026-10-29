-- Контакты локации / сервисного центра (Phone, Contacts, Email)
IF COL_LENGTH('dbo.Location', 'Phone') IS NULL
BEGIN
    ALTER TABLE dbo.Location ADD Phone NVARCHAR(100) NULL;
END
GO

IF COL_LENGTH('dbo.Location', 'Contacts') IS NULL
BEGIN
    ALTER TABLE dbo.Location ADD Contacts NVARCHAR(500) NULL;
END
GO

IF COL_LENGTH('dbo.Location', 'Email') IS NULL
BEGIN
    ALTER TABLE dbo.Location ADD Email NVARCHAR(255) NULL;
END
GO

IF COL_LENGTH('dbo.Location', 'Location2') IS NULL
BEGIN
    ALTER TABLE dbo.Location ADD Location2 NVARCHAR(500) NULL;
END
GO

IF COL_LENGTH('dbo.Location', 'FormsJointStockCompanies') IS NULL
BEGIN
    ALTER TABLE dbo.Location ADD FormsJointStockCompanies NVARCHAR(500) NULL;
END
GO
