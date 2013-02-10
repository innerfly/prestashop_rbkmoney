Install
1. Copy 'rbkmoney' folder in 'modules' dir;
2. Enable 'RBK Money' module at Modules > Payment > List of payment modules

Configure
3. Fill your shop ID and secret key at the module settings page (must be the same as on RBK Money merchant settings page).
4. Copy url http://YOUR_SITE.COM/modules/rbkmoney/validation.php and paste it in the 'Payment notification url' field  at the RBK Money merchant settings page

------------------------------------
In Russian:

Установка
1. Помещаем папку с модулем в директорию 'modules'
2. Включаем модуль в разделе Modules > Payment > List of payment modules

Настройка
3. На странице настройки модуля заполняем поля с ID вашего магазина и секрытным словом (идентичное указанному в настройках магазина в личном кабинете мерчанта)
4. Копируем адрес http://YOUR_SITE.COM/modules/rbkmoney/validation.php и вставляем в поле "Оповещение о платеже" в личном кабинете мерчанта.
5. В целях отладки можно включить логирование ответов от RBK Money - в этом случае они будут отображаться в логах (раздел меню Advanced Parameters > Logs). Там же отображаются ошибки - несовпадение хеша, и попытки посылать POST запросы с недопустимых IP.
