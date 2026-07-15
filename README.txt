Email Marketing System - PHP 8.x + MySQL

Pages:
- dashboard.php
- index.php audience management
- campaigns.php campaign management
- send.php send emails in max 5-person batches
- birthday.php birthday alerts and birthday offer email

Setup:
1. Copy the email_marketing_system folder into xampp/htdocs/.
2. Open phpMyAdmin.
3. Import database.sql.
4. Download PHPMailer from GitHub.
5. Place PHPMailer files like this:
   email_marketing_system/phpmailer/src/Exception.php
   email_marketing_system/phpmailer/src/PHPMailer.php
   email_marketing_system/phpmailer/src/SMTP.php
6. Open mailer.php and add your Gmail address and Gmail App Password.
7. Visit: http://localhost/email_marketing_system/dashboard.php

Gmail SMTP note:
Use a Gmail App Password. Normal Gmail password usually does not work.

Import formats:
CSV columns: name, email, phone, birthday, tags, status
JSON: array of objects using same field names.
