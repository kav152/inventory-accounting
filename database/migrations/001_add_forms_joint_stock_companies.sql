-- Юр. лицо хранится на локации, не на ТМЦ
IF NOT EXISTS (
    SELECT 1
    FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.Location')
      AND name = 'FormsJointStockCompanies'
)
BEGIN
    ALTER TABLE dbo.Location
        ADD FormsJointStockCompanies NVARCHAR(500) NULL;
END
GO
