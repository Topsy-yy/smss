<?php
  session_start();
  require '../config.php';
  
  $_SESSION['selectedAppID'] = 0;
  $_SESSION['appList'] = NULL;

  // Check validity of the user
  $currentUserID = $_SESSION['currentUserID'];
  if($currentUserID == NULL){
    header("Location: ../index.php");
    exit(); // Prevents script from continuing execution if not logged in
  }

  // Connect to database
  $conn = getDbConnection();
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }

  // Fetch User Name
  $getName = "SELECT S.firstName, S.middleName, S.lastName FROM student S WHERE S.studentID = '".$_SESSION['currentUserID']."'";
  $nameResult = mysqli_query($conn, $getName);

  while($rows9 = mysqli_fetch_row($nameResult)){
      foreach ($rows9 as $key => $value){
          if($key == 0){ $_SESSION['currentUserName'] = $value; }
          if($key == 1){ $_SESSION['currentUserName'] .= " " . $value; }
          if($key == 2){ $_SESSION['currentUserName'] .= ". " . $value; }
      }
  }
  $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Scholarship</title>
    <style>
        /* ==========================================================================
           1. DESIGN TOKENS (VARIABLES) & RESET
           ========================================================================== */
        :root {
            --bg-primary: #f8fafc;
            --bg-surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --brand-primary: #2563eb;
            --brand-danger: #dc2626;
            --radius-sm: 6px;
            --radius-md: 12px;
            --transition-smooth: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --pad-xs: 0.5rem;
            --pad-sm: 1rem;
            --pad-md: 1.5rem;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-main);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ==========================================================================
           2. LAYOUT SHELL
           ========================================================================== */
        .app-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .app-content {
            flex: 1;
            padding: var(--pad-sm);
        }

        @media (min-width: 768px) {
            .app-content {
                padding: var(--pad-md);
                display: flex;
                justify-content: center;
                align-items: flex-start;
            }
        }

        /* ==========================================================================
           3. FORM & UPLOAD COMPONENTS
           ========================================================================== */
        .form-container {
            background: var(--bg-surface);
            padding: var(--pad-md);
            border-radius: var(--radius-md);
            border: 1px solid #e2e8f0;
            max-width: 650px;
            width: 100%;
            margin: 0 auto;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .form-container h2 {
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: var(--radius-md);
            padding: 1.5rem;
            text-align: center;
            background: var(--bg-primary);
            cursor: pointer;
            transition: var(--transition-smooth);
            position: relative;
        }

        .upload-zone:hover, .upload-zone:focus-within {
            border-color: var(--brand-primary);
            background: #eff6ff;
        }

        .upload-zone input[type="file"] {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        .upload-zone label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 400;
            cursor: pointer;
            display: block;
        }

        .upload-zone label strong {
            color: var(--brand-primary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem var(--pad-sm);
            font-size: 1rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .btn-primary {
            background-color: var(--brand-primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <main class="app-content">
            <div class="form-container">
                <h2>Upload Supporting Documents</h2>
                <p style="color: var(--text-muted); margin-bottom: var(--pad-md);">
                    Please submit the required academic and identification documents in <strong>PDF format</strong> (Max 8MB each).
                </p>

                <form action="../backend/userdocupload.php" method="post" enctype="multipart/form-data" id="applicationForm">

                    <div class="form-group">
                        <label>1. Student ID Copy</label>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            Upload a scanned copy of your valid university student ID. Both front and back sides collated into one PDF. <span style="color: var(--brand-danger);">*</span>
                        </p>
                        <div class="upload-zone">
                            <input type="file" name="file[]" id="studentid" accept=".pdf" onchange="return fileValidation('studentid')" required>
                            <label for="studentid"><strong>Click to upload</strong> or drag and drop your Student ID</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>2. Official Academic Transcript</label>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            Upload your most recent official university transcript, fee statement, or statement of results. <span style="color: var(--brand-danger);">*</span>
                        </p>
                        <div class="upload-zone">
                            <input type="file" name="file[]" id="transcript" accept=".pdf" onchange="return fileValidation('transcript')" required>
                            <label for="transcript"><strong>Click to upload</strong> or drag and drop your Transcript</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>3. Letter of Motivation / Recommendation</label>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            Upload a signed letter explaining your financial need and academic goals, or a recommendation from a faculty member. <span style="color: var(--brand-danger);">*</span>
                        </p>
                        <div class="upload-zone">
                            <input type="file" name="file[]" id="recommendation" accept=".pdf" onchange="return fileValidation('recommendation')" required>
                            <label for="recommendation"><strong>Click to upload</strong> or drag and drop your Letter</label>
                        </div>
                    </div>

                    <button type="submit" name="apply" class="btn btn-primary" style="width: 100%; margin-top: var(--pad-sm);">Submit Application</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function fileValidation(inputId) {
            const fileInput = document.getElementById(inputId);
            const file = fileInput.files[0];

            if (!file) return true;

            // Check file type
            if (file.type !== "application/pdf") {
                alert("Please upload PDF files only.");
                fileInput.value = ''; // Clear the input
                return false;
            }

            // Check file size (8MB limit)
            if (file.size > 8000000) { 
                alert("File size is too large. Maximum allowed size is 8MB.");
                fileInput.value = ''; // Clear the input
                return false;
            }

            // UI Enhancement: Update the label text to show the selected filename
            const label = fileInput.nextElementSibling;
            label.innerHTML = `<strong>File selected:</strong> ${file.name}`;
            return true;
        }
    </script>
</body>
</html>