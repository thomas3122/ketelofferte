<?php
declare(strict_types=1);

define('APP_NAME', env_value('APP_NAME', 'KetelOfferte24'));
define('APP_URL', rtrim((string) env_value('APP_URL', 'https://ketelofferte24.nl'), '/'));

define('ADMIN_EMAIL', env_value('ADMIN_EMAIL', 'admin@ketelofferte24.nl'));

define('ENVIRONMENT', env_value('APP_ENV', 'production'));

define('MAIL_FROM_EMAIL', env_value('MAIL_FROM_EMAIL', 'no-reply@ketelofferte24.nl'));
define('MAIL_FROM_NAME', env_value('MAIL_FROM_NAME', 'KetelOfferte24.nl'));

define('ADMIN_NOTIFICATION_EMAIL', env_value('ADMIN_NOTIFICATION_EMAIL', 'offerte@ketelofferte24.nl'));
