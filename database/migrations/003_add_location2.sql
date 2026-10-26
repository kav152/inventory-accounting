-- Доп. адрес / локация 2
IF COL_LENGTH('dbo.Location', 'Location2') IS NULL
BEGIN
    ALTER TABLE dbo.Location ADD Location2 NVARCHAR(500) NULL;
END
GO
