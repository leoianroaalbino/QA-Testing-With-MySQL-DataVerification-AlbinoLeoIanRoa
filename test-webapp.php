<?php
// Local MariaDB/MySQL connection settings
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($servername, $username, $password, $dbname);

$successMessage = "";
$errorMessage   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($conn->connect_error) {
        $errorMessage = "Error: could not connect to the database.";
    } else {
        // Capture all four variables from the form
        $FirstName   = trim($_POST["FirstName"] ?? "");
        $LastName    = trim($_POST["LastName"] ?? "");
        $GovPosition = trim($_POST["GovPosition"] ?? "");
        $Location    = trim($_POST["Location"] ?? "");

        // INTENTIONAL FLAW (for QA testing purposes):
        // The Location value is captured above, but it is deliberately left out
        // of both the INSERT statement and the bind_param call below. As a result,
        // the Location column in main_corrupt_politicians is never filled in
        // (it stays NULL), even though the app shows a success message.
        $stmt = $conn->prepare(
            "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)"
        );

        if ($stmt === false) {
            // Fallback error if the query fails to prepare
            $errorMessage = "Error: the query could not be prepared.";
        } else {
            $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition);
            if ($stmt->execute()) {
                $successMessage = "Success! The wish note has been sent.";
            } else {
                $errorMessage = "Error: the wish note could not be saved.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kara David's Wish Note</title>
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        padding: 20px;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f0f2f5;
        font-family: Arial, Helvetica, sans-serif;
        color: #243447;
    }
    .card {
        width: 100%;
        max-width: 460px;
        background: #ffffff;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
    }
    h2 {
        margin: 0 0 20px;
        text-align: center;
        color: #1e6fd9;
    }
    label {
        display: block;
        margin: 14px 0 6px;
        font-weight: bold;
        font-size: 14px;
    }
    input[type="text"] {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #c5cfdb;
        border-radius: 6px;
        font-size: 15px;
    }
    input[type="text"]:focus {
        outline: none;
        border-color: #1e6fd9;
        box-shadow: 0 0 0 3px rgba(30, 111, 217, 0.15);
    }
    button {
        border: none;
        border-radius: 6px;
        padding: 11px 18px;
        font-size: 15px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-primary {
        width: 100%;
        margin-top: 22px;
        background: #1e6fd9;
        color: #ffffff;
    }
    .btn-primary:hover { background: #1558ae; }
    .btn-cancel { background: #dde3ea; color: #243447; }
    .btn-cancel:hover { background: #c9d1db; }
    .btn-confirm { background: #1e6fd9; color: #ffffff; }
    .btn-confirm:hover { background: #1558ae; }
    .message {
        padding: 12px 14px;
        border-radius: 6px;
        margin-bottom: 18px;
        font-size: 14px;
    }
    .success { background: #e3f1ff; color: #12559f; border: 1px solid #a9d0f7; }
    .error   { background: #fdeaea; color: #a32424; border: 1px solid #f2b5b5; }
    .overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(20, 30, 45, 0.55);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .overlay.show { display: flex; }
    .modal {
        width: 100%;
        max-width: 400px;
        background: #ffffff;
        border-radius: 10px;
        padding: 24px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    }
    .modal h3 { margin: 0 0 14px; color: #1e6fd9; }
    .modal p { margin: 8px 0; font-size: 14px; word-wrap: break-word; }
    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
</style>
</head>
<body>
<div class="card">
    <h2>Kara David's Wish Note</h2>

    <?php if ($successMessage): ?>
        <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <form id="wishForm" method="POST" action="">
        <label for="FirstName">Target's First Name</label>
        <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

        <label for="LastName">Target's Last Name</label>
        <input type="text" id="LastName" name="LastName" maxlength="30" required>

        <label for="GovPosition">Target's Government Position</label>
        <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

        <label for="Location">Target's Location</label>
        <input type="text" id="Location" name="Location" maxlength="40">

        <button type="submit" class="btn-primary">Send Wish Note</button>
    </form>
</div>

<div class="overlay" id="verifyModal">
    <div class="modal">
        <h3>Verify Target Data</h3>
        <p><strong>First Name:</strong> <span id="mFirstName"></span></p>
        <p><strong>Last Name:</strong> <span id="mLastName"></span></p>
        <p><strong>Government Position:</strong> <span id="mGovPosition"></span></p>
        <p><strong>Location:</strong> <span id="mLocation"></span></p>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
            <button type="button" class="btn-confirm" id="confirmBtn">Confirm &amp; Send</button>
        </div>
    </div>
</div>

<script>
    var form = document.getElementById("wishForm");
    var modal = document.getElementById("verifyModal");

    // Intercept the submission and show the verification modal
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        document.getElementById("mFirstName").textContent = document.getElementById("FirstName").value;
        document.getElementById("mLastName").textContent = document.getElementById("LastName").value;
        document.getElementById("mGovPosition").textContent = document.getElementById("GovPosition").value;
        document.getElementById("mLocation").textContent = document.getElementById("Location").value;
        modal.classList.add("show");
    });

    // Cancel closes the modal
    document.getElementById("cancelBtn").addEventListener("click", function () {
        modal.classList.remove("show");
    });

    // Confirm bypasses the interception and sends the POST data
    document.getElementById("confirmBtn").addEventListener("click", function () {
        form.submit();
    });
</script>
</body>
</html>
