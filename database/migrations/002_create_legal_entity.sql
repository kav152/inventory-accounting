-- Справочник юр. лиц (без привязки к локации)
IF OBJECT_ID(N'dbo.LegalEntity', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.LegalEntity (
        IDLegalEntity INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        NameLegalEntity NVARCHAR(500) NOT NULL,
        isActive BIT NOT NULL DEFAULT 1
    );
END
GO
