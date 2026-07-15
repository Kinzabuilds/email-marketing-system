<?php
session_start();
include 'config.php';
include 'mailer.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $to      = $_POST['to'];
    $subject = $_POST['subject'];
    $body    = $_POST['body'];

    $eventTitle = $_POST['event_title'];
    $eventDate  = $_POST['event_date'];
    $eventTime  = $_POST['event_time'];
    $eventLoc   = $_POST['event_location'];
    $eventDesc  = $_POST['event_description'];
    $hasEvent   = !empty($eventTitle) && !empty($eventDate);

    $mail = createMailer();

    try {
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br(htmlspecialchars($body));

        // Multiple Attachments
        if (isset($_FILES['attachments'])) {
            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['attachments']['error'][$key] === 0) {
                    $originalName = $_FILES['attachments']['name'][$key];
                    $mail->addAttachment($tmpName, $originalName);
                }
            }
        }

        // Calendar Event
        if ($hasEvent) {
            $startDateTime = $eventDate . ' ' . ($eventTime ?: '09:00');
            $start = date('Ymd\THis', strtotime($startDateTime));
            $end   = date('Ymd\THis', strtotime($startDateTime . ' +1 hour'));
            $uid   = uniqid() . '@myapp.local';

            $icsContent  = "BEGIN:VCALENDAR\r\n";
            $icsContent .= "VERSION:2.0\r\n";
            $icsContent .= "PRODID:-//MyApp//EN\r\n";
            $icsContent .= "CALSCALE:GREGORIAN\r\n";
            $icsContent .= "METHOD:REQUEST\r\n";
            $icsContent .= "BEGIN:VEVENT\r\n";
            $icsContent .= "UID:$uid\r\n";
            $icsContent .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            $icsContent .= "DTSTART:$start\r\n";
            $icsContent .= "DTEND:$end\r\n";
            $icsContent .= "SUMMARY:" . $eventTitle . "\r\n";
            $icsContent .= "LOCATION:" . $eventLoc . "\r\n";
            $icsContent .= "DESCRIPTION:" . str_replace("\n", "\\n", $eventDesc) . "\r\n";
            $icsContent .= "STATUS:CONFIRMED\r\n";
            $icsContent .= "END:VEVENT\r\n";
            $icsContent .= "END:VCALENDAR\r\n";

            $mail->addStringAttachment($icsContent, 'event.ics', 'base64', 'text/calendar');
        }

        $mail->send();
        $message = "✅ Email sent successfully" . ($hasEvent ? " with calendar event!" : "!");

    } catch (Exception $e) {
        $error = "❌ Email could not be sent. Error: " . $mail->ErrorInfo;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Send Email with Attachments</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; min-height: 100vh; }

        .top-bar {
            background: #4f46e5;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar h1 { font-size: 20px; }
        .top-bar a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 7px 15px; border-radius: 20px; font-size: 14px; }
        .top-bar a:hover { background: rgba(255,255,255,0.3); }

        .container { max-width: 650px; margin: 30px auto; padding: 0 20px; }

        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }

        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { color: #1f2937; margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; }

        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        input, textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
        }
        input:focus, textarea:focus { border-color: #4f46e5; }
        textarea { resize: vertical; }

        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }

        .file-area {
            border: 2px dashed #4f46e5;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #f5f3ff;
            cursor: pointer;
        }
        .file-area:hover { background: #ede9fe; }
        #fileList { margin-top: 10px; text-align: left; }
        .file-tag {
            display: inline-block;
            background: #e0e7ff;
            color: #4f46e5;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            margin: 3px;
        }

        .event-section {
            background: #fef9e7;
            border: 1.5px solid #fde68a;
            border-radius: 10px;
            padding: 18px;
            margin-top: 10px;
        }
        .event-section h4 { color: #92400e; margin-bottom: 12px; font-size: 15px; }

        .btn-send {
            width: 100%;
            padding: 13px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-send:hover { background: #4338ca; }
    </style>
</head>
<body>

<div class="top-bar">
    <h1>📧 Send Email</h1>
    <a href="profile.php">👤 Profile</a>
</div>

<div class="container">

    <?php if ($message): ?>
        <div class="success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="send_email.php" enctype="multipart/form-data">

        <div class="card">
            <h3>✉️ Email Details</h3>
            <div class="form-group">
                <label>To (Recipient Email)</label>
                <input type="email" name="to" placeholder="recipient@gmail.com" required>
            </div>
            <div class="form-group">
                <label>Subject</label>
                <input type="text" name="subject" placeholder="Email subject" required>
            </div>
            <div class="form-group">
                <label>Message Body</label>
                <textarea name="body" rows="5" placeholder="Write your message..." required></textarea>
            </div>
        </div>

        <div class="card">
            <h3>📎 Attachments (Multiple Files)</h3>
            <div class="file-area" onclick="document.getElementById('fileInput').click()">
                <p>📂 Click to select files</p>
                <p style="font-size:12px; color:#6b7280; margin-top:5px;">PDF, DOC, JPG, PNG, etc.</p>
            </div>
            <input type="file" id="fileInput" name="attachments[]" multiple style="display:none;" onchange="showFiles(this)">
            <div id="fileList"></div>
        </div>

        <div class="card">
            <h3>📅 Add Calendar Event (Optional)</h3>
            <div class="event-section">
                <h4>📌 Event Invite Details</h4>
                <div class="form-group">
                    <label>Event Title</label>
                    <input type="text" name="event_title" placeholder="e.g. Team Meeting">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="event_date">
                    </div>
                    <div class="form-group">
                        <label>Time</label>
                        <input type="time" name="event_time">
                    </div>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="event_location" placeholder="e.g. Zoom / Office">
                </div>
                <div class="form-group">
                    <label>Event Description</label>
                    <textarea name="event_description" rows="3" placeholder="Event details..."></textarea>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-send">📤 Send Email</button>

    </form>
</div>

<script>
function showFiles(input) {
    const list = document.getElementById('fileList');
    list.innerHTML = '';
    Array.from(input.files).forEach(file => {
        const tag = document.createElement('span');
        tag.className = 'file-tag';
        tag.textContent = '📄 ' + file.name;
        list.appendChild(tag);
    });
}
</script>

</body>
</html>