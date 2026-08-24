console.log("Файл app_modal.js загружен!");

// Вызываем инициализацию при загрузке документа
/*document.addEventListener("DOMContentLoaded", function () {
  console.log("Событие DOMContentLoaded вызвано!"); // Проверка срабатывания события
  initializeSelects();
});*/

(function () {
  //setTimeout(initializeSelects, 100);

  // Инициализация авто-расширения для textarea
  const textareas = document.querySelectorAll(".auto-expand");
  textareas.forEach((textarea) => {
    autoResize(textarea); // Инициализация текущей высоты
    textarea.addEventListener("input", () => autoResize(textarea));
  });
/*
  async function initializeSelects() {
    const typeSelect = document.getElementById(
      PropertySelectID[PropertyTMC.TYPE_TMC]
    );
    const brandSelect = document.getElementById(
      PropertySelectID[PropertyTMC.BRAND]
    );
    const modelSelect = document.getElementById(
      PropertySelectID[PropertyTMC.MODEL]
    );

    const typeId = typeSelect.value;
    // Получаем данные из глобального объекта
    const brandId = window.cardItemData.brandId;
    const modelId = window.cardItemData.modelId;
    const statusItem = window.cardItemData.statusItem;
    //console.log("Статус действия: " + statusItem);
    //console.log("Ид типа - " + typeId + ", бренда - " + brandId + ", модели - " + modelId);

    if (typeId !== "0") {
      // Загружаем бренды для выбранного типа
      await handleSelectChange(
        { target: typeSelect },
        PropertyTMC.TYPE_TMC,
        PropertyTMC.BRAND
      );
      if (brandId !== "0") {
        brandSelect.value = brandId;
        document.getElementById("addBrandBtn").disabled = +this.value === 0;
        // Загружаем модели для выбранного бренда
        await handleSelectChange(
          { target: brandSelect },
          PropertyTMC.BRAND,
          PropertyTMC.MODEL
        );
        if (modelId !== "0") {
          modelSelect.value = modelId;
          document.getElementById("addModelBtn").disabled = +this.value === 0;
        }
      }
    }
  }*/

/*
  function openPropertyView(propertyTMC) {
    const propType = propertyTMC;
    let nameIdSelect;
    let previousSelect;

    switch (propertyTMC) {
      case PropertyTMC.TYPE_TMC:
        nameIdSelect = PropertySelectID[PropertyTMC.TYPE_TMC];
        break;
      case PropertyTMC.BRAND:
        nameIdSelect = PropertySelectID[PropertyTMC.TYPE_TMC];
        previousSelect = document.getElementById(
          PropertySelectID[PropertyTMC.TYPE_TMC]
        );
        break;
      case PropertyTMC.MODEL:
        nameIdSelect = PropertySelectID[PropertyTMC.BRAND];
        previousSelect = document.getElementById(
          PropertySelectID[PropertyTMC.BRAND]
        );
        break;
    }

    const propertyContainer = document.getElementById("propertyContainer");
    const mainContainer = document.getElementById("mainContainer");

    if (propertyContainer?.classList.contains("show")) {
      propertyContainer?.classList.remove("show");
      mainContainer?.classList.remove("expanded");
      propertyContainer?.classList.toggle("close");
      if (previousSelect != null) previousSelect.disabled = false;
    } else {
      if (previousSelect != null) previousSelect.disabled = true;
      propertyContainer?.classList.remove("close");
      propertyContainer?.classList.toggle("show");
      mainContainer?.classList.toggle("expanded");
      let url =
        "/src/View/" +
        `propertyTMC.php?type=${encodeURIComponent(propType)}&property_id=${
          document.getElementById(nameIdSelect).value
        }`;

      fetch(url)
        .then((response) => response.text())
        .then((html) => {
          propertyContainer.innerHTML = html;

          const scripts = propertyContainer.querySelectorAll("script");
          scripts.forEach((script) => {
            const newScript = document.createElement("script");

            // Копируем атрибуты (src, async и т.д.)
            Array.from(script.attributes).forEach((attr) => {
              newScript.setAttribute(attr.name, attr.value);
            });

            // Копируем содержимое скрипта
            newScript.textContent = script.textContent;

            // Вставляем в DOM для выполнения
            document.body.appendChild(newScript).remove();
          });
        });
    }
  }*/

  // Активация кнопок
/*  document
    .getElementById("typeTMCSelect")
    .addEventListener("change", function () {
      document.getElementById("addBrandBtn").disabled = +this.value === 0;
    });
  document
    .getElementById("brandSelect")
    .addEventListener("change", function () {
      document.getElementById("addModelBtn").disabled = +this.value === 0;
    });*/

  // Активация select
 /* async function handleSelectChange(event, currentType, nextType) {
    const selectedValue = Number(event.target.value);
    const nextSelect = document.getElementById(PropertySelectID[nextType]);

    if (selectedValue === 0) {
      nextSelect.disabled = true;
      nextSelect.innerHTML = `<option value="0">Выберите ${nextType}</option>`;
      return;
    }

    nextSelect.disabled = false;

    let url = "";
    switch (currentType) {
      case PropertyTMC.TYPE_TMC:
        url = `/src/BusinessLogic/getBrands.php?type_id=${selectedValue}`;
        break;
      case PropertyTMC.BRAND:
        url = `/src/BusinessLogic/getModels.php?type_id=${selectedValue}`;
        break;
    }

    try {
      const response = await fetch(url);
      const data = await response.json();
      nextSelect.innerHTML = `<option value="0">Выберите ${nextType}</option>`;

      data.forEach((item) => {
        nextSelect.add(new Option(item.Name, item.ID));
      });
    } catch (error) {
      console.error(`Ошибка загрузки ${nextType}:`, error);
      nextSelect.innerHTML = `<option value="0">Ошибка загрузки</option>`;
    }
  }*/

  function addPropertySelect(currentTypeProperty, property) {
    const currentSelect = document.getElementById(
      PropertySelectID[currentTypeProperty]
    );
    currentSelect.add(new Option(property.Name, property.ID));
    openPropertyView(PropertyTMC[currentTypeProperty.toUpperCase()]);
  }

  // Инициализация обработчиков событий
/*  document
    .getElementById(PropertySelectID[PropertyTMC.TYPE_TMC])
    .addEventListener("change", (e) => {
      handleSelectChange(e, PropertyTMC.TYPE_TMC, PropertyTMC.BRAND);
    });

  document
    .getElementById(PropertySelectID[PropertyTMC.BRAND])
    .addEventListener("change", (e) => {
      handleSelectChange(e, PropertyTMC.BRAND, PropertyTMC.MODEL);
    });*/
    
    document
        .getElementById("typeTMCSelect")
        .addEventListener("change", function () {
            console.log("Активация addBrandBtn");
            document.getElementById("addBrandBtn").disabled = +this.value === 0;
        });
    document
        .getElementById("brandSelect")
        .addEventListener("change", function () {
            document.getElementById("addModelBtn").disabled = +this.value === 0;
        });


  //window.openPropertyView = openPropertyView;
  window.addPropertySelect = addPropertySelect;
  window.saveModal = saveModal;

})();
