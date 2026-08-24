import { executeEntityAction, getCollectFormData } from '../templates/entityActionTemplate.js';
import { executeActionForCUDSelect } from '../templates/cudRowsInSelect.js';
import { showNotification } from "../modals/setting.js";
import { TypeMessage } from "../../src/constants/typeMessage.js";
import { Action } from '../../src/constants/actions.js';

(function () {

    async function saveProperty() {
        const propertyContainer = document.getElementById('propertyContainer');
        if (!propertyContainer) return;
        const input = propertyContainer.querySelector('#propertyName');
        const panel = propertyContainer.querySelector('.property-management');

        if (!input || !panel) return;

        const valueProp = input.value.trim();
        if (!valueProp) {
            showNotification(TypeMessage.notification, "Введите название свойства");
            return;
        }

        const type = document.getElementById('typeProperty').value.toUpperCase();
        const propertyId = document.getElementById('propertyId').value;

        const propertyData = new FormData();

        propertyData.append('typeProperty', PropertyTMC[type]);
        propertyData.append('valueProp', valueProp);
        propertyData.append('property_id', propertyId);


        //const userData = getCollectFormData(propertyData, Action.CREATE);
        //console.log(userData);
        const data = {
            statusEntity: Action.CREATE,
            typeProperty: PropertyTMC[type],
            valueProp: valueProp,
            property_id: propertyId
        };

        try {
            const result = await executeEntityAction({
                action: Action.CREATE,
                formData: data,
                url: "/src/BusinessLogic/addProperty.php",
                successMessage: `Свойство '${valueProp}' добавлено успешно`,
            });

            // Определяем поля для отображения в select
            //const displayFields = fields.map(field => field.name);
            //const displaySeparator = fields.length > 1 ? ', ' : '';


            console.log(result.resultEntity);
            
            executeActionForCUDSelect(Action.CREATE, result.resultEntity, 'typeTMCSelect', ['value'], '', true);


        }
        catch (error) {
            console.error("Ошибка:", error);
            showNotification(TypeMessage.error, error);
        }




        /*
            const response = await fetch('/src/BusinessLogic/addProperty.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            console.log(data);
        
            data.forEach(element => {
                console.log(`Тип элемента: ${element.Name}, ID: ${element.ID}`)
                console.log(`typeProperty: ${PropertyTMC[type]}`)
                addPropertySelect(PropertyTMC[type], element);
            });*/
    }

    // Явно делаем функцию глобальной
    window.saveProperty = saveProperty;

})();