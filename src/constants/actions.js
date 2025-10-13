const Action = Object.freeze({
  CREATE: "create",
  CREATE_ANALOG: "create_analog",
  EDIT: "edit",
  DISTRIBUTE: "distribute",
  WORK_TMC: "work",


  RETURN_TMC: "returnTMC",
  SEND_SERVICE: "sendService",
  RETURN_SERVICE: "returnService",
  edit_write_off: "edit_write_off"
});

const ActionUrls = {
  [Action.CREATE]: "cardItem_modal.php",
  [Action.CREATE_ANALOG]: "cardItem_modal.php",
  [Action.EDIT]: "cardItem_modal.php",
  [Action.DISTRIBUTE]: "action_modal.php",
  [Action.WORK_TMC]: "action_modal.php",

  
  [Action.RETURN_TMC]: "action_modal.php",
  [Action.SEND_SERVICE]: "action_modal.php",
  [Action.RETURN_SERVICE]: "action_modal.php",
};