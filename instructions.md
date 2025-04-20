In this folder we are building a wordpress plugin called openai-chat.

- It should connect to the openai api
- I want a new menu option for it, where we can let the user enter settings like API-key, styling options etc.
    settings should be available to administrators only. 
    for styiling you can use wordpress defaults backend. include some simple styling options for frontend display in settings page
    Store the API-key in the wordpress options table, and include a test to verify they key. 
- Add a notice in wordpress backend if there is no api key, or the key is not valid and link to settingspage where user can add or change the api key.
- include language support for english and norwegian
- log errors to wordress errorlog, and to console when possible
- Frontend chat should be included on all pages. Minimized until user opens it