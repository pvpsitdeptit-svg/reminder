<?php
require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Templates - FMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text"></i> CSV Templates
            </h1>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="row">
            <!-- Lecture Template -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="bi bi-book"></i> Lecture Timetable Template</h5>
                    </div>
                    <div class="card-body">
                        <h6>Required Columns:</h6>
                        <ul>
                            <li><strong>day:</strong> Day of week (e.g., Monday or Mon)</li>
                            <li><strong>time:</strong> Start time (HH:MM format, 24-hour)</li>
                            <li><strong>name:</strong> Faculty display name</li>
                            <li><strong>faculty_id:</strong> Faculty identification code</li>
                            <li><strong>faculty_email:</strong> Faculty email address</li>
                            <li><strong>subject:</strong> Subject name</li>
                            <li><strong>room:</strong> Classroom/room number</li>
                        </ul>
                        
                        <h6>Example Format:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>day</th>
                                        <th>time</th>
                                        <th>name</th>
                                        <th>faculty_id</th>
                                        <th>faculty_email</th>
                                        <th>subject</th>
                                        <th>room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Mon</td>
                                        <td>09:00</td>
                                        <td>Dr. Alice</td>
                                        <td>FAC001</td>
                                        <td>faculty001@university.edu</td>
                                        <td>Mathematics</td>
                                        <td>Room101</td>
                                    </tr>
                                    <tr>
                                        <td>Tue</td>
                                        <td>10:00</td>
                                        <td>Dr. Bob</td>
                                        <td>FAC002</td>
                                        <td>faculty002@university.edu</td>
                                        <td>Physics</td>
                                        <td>Room102</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="templates/lecture_template.csv" class="btn btn-success" download>
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invigilation Template -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="bi bi-clipboard-check"></i> Invigilation Duty Template</h5>
                    </div>
                    <div class="card-body">
                        <h6>Required Columns:</h6>
                        <ul>
                            <li><strong>date:</strong> Exam date (YYYY-MM-DD or DD/MM/YYYY)</li>
                            <li><strong>time:</strong> Exam start time (HH:MM format, 24-hour)</li>
                            <li><strong>faculty_id:</strong> Faculty identification code</li>
                            <li><strong>faculty_email:</strong> Faculty email address</li>
                            <li><strong>exam:</strong> Exam name/type</li>
                            <li><strong>room:</strong> Exam hall/room number</li>
                        </ul>
                        
                        <h6>Example Format:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>date</th>
                                        <th>time</th>
                                        <th>faculty_id</th>
                                        <th>faculty_email</th>
                                        <th>exam</th>
                                        <th>room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2024-01-20</td>
                                        <td>09:00</td>
                                        <td>FAC001</td>
                                        <td>faculty001@university.edu</td>
                                        <td>Mid-Term Mathematics</td>
                                        <td>ExamHall1</td>
                                    </tr>
                                    <tr>
                                        <td>2024-01-20</td>
                                        <td>14:00</td>
                                        <td>FAC002</td>
                                        <td>faculty002@university.edu</td>
                                        <td>Mid-Term Physics</td>
                                        <td>ExamHall2</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3">
                            <a href="templates/invigilation_template.csv" class="btn btn-info" download>
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Faculty Leave Master Template -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="bi bi-person-lines-fill"></i> Faculty Leave Master Template</h5>
                    </div>
                    <div class="card-body">
                        <h6>Required Columns:</h6>
                        <ul>
                            <li><strong>employee_id:</strong> Employee identification code</li>
                            <li><strong>name:</strong> Faculty name</li>
                            <li><strong>department:</strong> Department name/code</li>
                            <li><strong>faculty_email:</strong> Faculty email address (Unique)</li>
                            <li><strong>total_leaves:</strong> Total leaves</li>
                            <li><strong>cl:</strong> Casual Leave</li>
                            <li><strong>el:</strong> Earned Leave</li>
                            <li><strong>ml:</strong> Medical Leave</li>
                        </ul>

                        <h6>Example Format:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>employee_id</th>
                                        <th>name</th>
                                        <th>department</th>
                                        <th>faculty_email</th>
                                        <th>total_leaves</th>
                                        <th>cl</th>
                                        <th>el</th>
                                        <th>ml</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>EMP001</td>
                                        <td>Dr. Alice</td>
                                        <td>CSE</td>
                                        <td>alice@university.edu</td>
                                        <td>30</td>
                                        <td>10</td>
                                        <td>10</td>
                                        <td>10</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <a href="templates/faculty_leave_template.csv" class="btn btn-warning" download>
                                <i class="bi bi-download"></i> Download Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="bi bi-exclamation-triangle"></i> Important Notes</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>File Requirements:</h6>
                                <ul>
                                    <li>File must be in CSV format (.csv)</li>
                                    <li>Maximum file size: 5MB</li>
                                    <li>First row must contain column headers</li>
                                    <li>No empty rows in the middle of data</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Data Validation:</h6>
                                <ul>
                                    <li>Day must be a valid weekday (e.g., Monday or Mon)</li>
                                    <li>Time format: HH:MM (24-hour format)</li>
                                    <li>All fields are required</li>
                                    <li>Faculty ID should be consistent across files</li>
                                    <li>Schedules are generated dynamically from weekly templates</li>
                                    <li><strong>Half leaves are only available for CL (Casual Leave)</strong></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i>
                            <strong>Tip:</strong> Use the provided templates to ensure your CSV files are properly formatted.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>
