export const PropertyTMC = Object.freeze({
  TYPE_TMC: "type_tmc",
  BRAND: "brand",
  MODEL: "model",
});


const PropertyName = {
  [PropertyTMC.TYPE_TMC]: "Добавить тип ТМС",
  [PropertyTMC.BRAND]: "Добавить бренд",
  [PropertyTMC.MODEL]: "Добавить модель",
};

export const PropertySelectID = {
  [PropertyTMC.TYPE_TMC]: "typeTMCSelect",
  [PropertyTMC.BRAND]: "brandSelect",
  [PropertyTMC.MODEL]: "modelSelect",
};

// Для глобального использования (если нужно)
if (typeof window !== 'undefined') {
    window.PropertyTMC = PropertyTMC;
    window.PropertySelectID = PropertySelectID;
}
